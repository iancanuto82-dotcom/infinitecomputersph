<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Support\SpreadsheetReader;
use App\Support\AuditLogger;
use App\Support\PublicMedia;
use App\Support\PublicCatalogCache;
use Illuminate\Support\Collection;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->integer('category');
        $stockFilter = (string) $request->query('stock', 'all');

        $categories = Category::query()
            ->with('parent:id,name')
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get();

        $productsQuery = Product::query()
            ->with(['category:id,name,parent_id', 'category.parent:id,name'])
            ->latest();

        if ($search !== '') {
            $productsQuery->where(function (Builder $query) use ($search): void {
                $this->applyProductCatalogSearch($query, $search);
                $query->orWhereHas('category', function (Builder $categoryQuery) use ($search): void {
                    $categoryQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($categoryId) {
            $productsQuery->whereIn('category_id', $this->selectedCategoryIds($categories, $categoryId));
        }

        if ($stockFilter === 'in_stock') {
            $productsQuery->where('stock', '>', 0);
        } elseif ($stockFilter === 'out_of_stock') {
            $productsQuery->where('stock', '<=', 0);
        }

        $products = $productsQuery
            ->paginate(25)
            ->withQueryString();

        $costCalculations = (array) $request->session()->get('product_cost_calculations', []);
        $latestImportRevertLog = $this->latestRevertableImportLog((int) ($request->user()?->id ?? 0));

        return view('admin.products.index', compact('products', 'categories', 'search', 'categoryId', 'stockFilter', 'costCalculations', 'latestImportRevertLog'));
    }

    public function create(Request $request)
    {
        $categories = Category::query()
            ->with('parent:id,name')
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get();
        $indexQuery = $this->productIndexQuery($request);

        return view('admin.products.create', compact('categories', 'indexQuery'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->productRules());
        $payload = $this->extractProductPayload($validated);
        $payload['name'] = trim((string) $payload['name']);

        $existingProduct = Product::query()
            ->where('category_id', $payload['category_id'])
            ->where('name', $payload['name'])
            ->get()
            ->first(fn (Product $candidate) => $candidate->name === $payload['name']);

        if ($existingProduct) {
            $stockBefore = (int) $existingProduct->stock;
            $initialStockBefore = (int) ($existingProduct->initial_stock ?? 0);

            $calculation = DB::transaction(function () use ($existingProduct, $payload): ?array {
                $incomingStock = max(0, (int) ($payload['stock'] ?? 0));
                $currentStock = max(0, (int) $existingProduct->stock);
                $currentInitialStock = max(0, (int) ($existingProduct->initial_stock ?? 0));
                $newStock = $currentStock + $incomingStock;

                $updatePayload = [
                    'stock' => $existingProduct->stock,
                    'initial_stock' => $currentInitialStock,
                    'cost_price' => $existingProduct->cost_price,
                ];

                if ($incomingStock > 0) {
                    $updatePayload['stock'] = $newStock;
                    $updatePayload['initial_stock'] = $currentInitialStock + $incomingStock;

                    if ($payload['cost_price'] !== null) {
                        $incomingCostCents = $this->moneyToCents((float) $payload['cost_price']);
                        $currentCostCents = $existingProduct->cost_price !== null
                            ? $this->moneyToCents((float) $existingProduct->cost_price)
                            : null;

                        $averageCostCents = $currentStock === 0 || $currentCostCents === null
                            ? $incomingCostCents
                            : (int) round(($currentCostCents + $incomingCostCents) / 2);

                        $updatePayload['cost_price'] = number_format($averageCostCents / 100, 2, '.', '');

                        $existingProduct->update($updatePayload);

                        return [
                            'title' => 'Average cost calculation',
                            'formula' => $currentStock === 0 || $currentCostCents === null
                                ? 'No previous cost. New average cost = incoming cost'
                                : '(old_cost + incoming_cost) / 2',
                            'old_cost' => $currentCostCents !== null ? number_format($currentCostCents / 100, 2, '.', '') : null,
                            'incoming_cost' => number_format($incomingCostCents / 100, 2, '.', ''),
                            'result_cost' => number_format($averageCostCents / 100, 2, '.', ''),
                            'old_stock' => $currentStock,
                            'incoming_stock' => $incomingStock,
                            'new_stock' => $newStock,
                            'at' => now()->format('M d, Y h:i A'),
                        ];
                    }
                }

                $existingProduct->update($updatePayload);
                return null;
            });

            if (is_array($calculation)) {
                $this->storeCostCalculation($request, (int) $existingProduct->id, $calculation);
            }

            $existingProduct->refresh();
            AuditLogger::record(
                $request,
                'stock_in',
                'product',
                (int) $existingProduct->id,
                (string) $existingProduct->name,
                'Stock added to existing product.',
                [
                    'stock_before' => $stockBefore,
                    'stock_after' => (int) $existingProduct->stock,
                    'initial_stock_before' => $initialStockBefore,
                    'initial_stock_after' => (int) ($existingProduct->initial_stock ?? 0),
                    'incoming_stock' => max(0, (int) ($payload['stock'] ?? 0)),
                ]
            );

            $this->forgetPublicCatalogCaches();

            return redirect()
                ->route('admin.products.index', $this->productIndexQuery($request))
                ->with('status', 'Stock-in complete. Quantity and average cost updated.');
        }

        if ($request->hasFile('image')) {
            $payload['image_path'] = $request->file('image')->store('products', PublicMedia::diskName());
        }

        $product = Product::create($payload);

        AuditLogger::record(
            $request,
            'created',
            'product',
            (int) $product->id,
            (string) $product->name,
            'Created product.',
            [
                'category_id' => (int) $product->category_id,
                'price' => (float) $product->price,
                'stock' => (int) $product->stock,
            ]
        );

        $this->forgetPublicCatalogCaches();

        return redirect()->route('admin.products.index', $this->productIndexQuery($request));
    }

    public function edit(Request $request, Product $product)
    {
        $categories = Category::query()
            ->with('parent:id,name')
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get();
        $indexQuery = $this->productIndexQuery($request);

        return view('admin.products.edit', compact('product', 'categories', 'indexQuery'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate($this->productRules());
        $payload = $this->extractProductPayload($validated);
        $before = $product->only(['name', 'category_id', 'price', 'cost_price', 'initial_stock', 'stock', 'is_active']);

        if ($request->boolean('remove_image') && $product->image_path) {
            Storage::disk(PublicMedia::diskName())->delete($product->image_path);
            $payload['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk(PublicMedia::diskName())->delete($product->image_path);
            }

            $payload['image_path'] = $request->file('image')->store('products', PublicMedia::diskName());
        }

        $product->update($payload);

        AuditLogger::record(
            $request,
            'updated',
            'product',
            (int) $product->id,
            (string) $product->name,
            'Updated product.',
            [
                'before' => $before,
                'after' => $product->only(['name', 'category_id', 'price', 'cost_price', 'initial_stock', 'stock', 'is_active']),
            ]
        );

        $this->forgetPublicCatalogCaches();

        return redirect()->route('admin.products.index', $this->productIndexQuery($request));
    }

    public function destroy(Product $product)
    {
        $name = (string) $product->name;
        $categoryId = (int) $product->category_id;
        $stock = (int) $product->stock;
        $deletedSnapshot = $product->only([
            'name',
            'description',
            'image_path',
            'image_url',
            'price',
            'cost_price',
            'initial_stock',
            'stock',
            'category_id',
            'is_active',
        ]);

        if ($product->image_path) {
            Storage::disk(PublicMedia::diskName())->delete($product->image_path);
        }

        $product->delete();

        AuditLogger::record(
            request(),
            'deleted',
            'product',
            (int) $product->id,
            $name,
            'Deleted product.',
            [
                'category_id' => $categoryId,
                'stock' => $stock,
                'deleted_snapshot' => $deletedSnapshot,
            ]
        );

        $this->forgetPublicCatalogCaches();

        return back();
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'distinct', 'exists:products,id'],
        ]);

        $productIds = collect($validated['product_ids'])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->get();

        $deletedCount = 0;

        foreach ($products as $product) {
            $productId = (int) $product->id;
            $name = (string) $product->name;
            $categoryId = (int) $product->category_id;
            $stock = (int) $product->stock;
            $deletedSnapshot = $product->only([
                'name',
                'description',
                'image_path',
                'image_url',
                'price',
                'cost_price',
                'initial_stock',
                'stock',
                'category_id',
                'is_active',
            ]);

            if ($product->image_path) {
                Storage::disk(PublicMedia::diskName())->delete($product->image_path);
            }

            $product->delete();

            AuditLogger::record(
                $request,
                'deleted',
                'product',
                $productId,
                $name,
                'Deleted product via bulk action.',
                [
                    'category_id' => $categoryId,
                    'stock' => $stock,
                    'bulk' => true,
                    'deleted_snapshot' => $deletedSnapshot,
                ]
            );

            $deletedCount++;
        }

        if ($deletedCount === 0) {
            return back()->with('status', 'No matching products were found to delete.');
        }

        $this->forgetPublicCatalogCaches();

        return back()->with('status', $deletedCount.' product(s) deleted.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:5120', 'mimes:csv,txt,xlsx'],
        ]);

        $file = $request->file('file');
        if (! $file) {
            throw ValidationException::withMessages(['file' => 'No file uploaded.']);
        }

        $extension = Str::lower($file->getClientOriginalExtension());

        try {
            $rows = SpreadsheetReader::readRows($file->getRealPath(), $extension);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['file' => 'Unable to read file. Please upload a valid CSV or XLSX.']);
        }

        if (count($rows) === 0) {
            throw ValidationException::withMessages(['file' => 'The file appears to be empty.']);
        }

        $header = array_shift($rows);
        $columnMap = $this->mapImportColumns($header);

        foreach (['name', 'price', 'stock'] as $required) {
            if (! array_key_exists($required, $columnMap)) {
                throw ValidationException::withMessages([
                    'file' => 'Missing required column: '.$required.'. Expected columns include: CATEGORY, Sub Category, Product Name, Cost per Unit, Price per Unit, Current Qty. in Stock.',
                ]);
            }
        }
        if (! array_key_exists('category', $columnMap) && ! array_key_exists('sub_category', $columnMap)) {
            throw ValidationException::withMessages([
                'file' => 'Missing required column: category. Expected columns include: CATEGORY, Sub Category, Product Name, Cost per Unit, Price per Unit, Current Qty. in Stock.',
            ]);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $calculationUpdates = [];
        $revertCreatedProductIds = [];
        $revertUpdatedProducts = [];

        DB::transaction(function () use ($rows, $columnMap, &$created, &$updated, &$skipped, &$calculationUpdates, &$revertCreatedProductIds, &$revertUpdatedProducts) {
            foreach ($rows as $row) {
                $name = trim((string) ($row[$columnMap['name']] ?? ''));
                $categoryName = array_key_exists('category', $columnMap)
                    ? trim((string) ($row[$columnMap['category']] ?? ''))
                    : '';
                $subCategoryName = array_key_exists('sub_category', $columnMap)
                    ? trim((string) ($row[$columnMap['sub_category']] ?? ''))
                    : '';
                $priceRaw = (string) ($row[$columnMap['price']] ?? '');
                $costRaw = array_key_exists('cost_price', $columnMap) ? (string) ($row[$columnMap['cost_price']] ?? '') : '';
                $stockRaw = (string) ($row[$columnMap['stock']] ?? '');
                $mainCategoryName = $categoryName;

                if ($name === '' || ($mainCategoryName === '' && $subCategoryName === '') || trim($priceRaw) === '' || trim($stockRaw) === '') {
                    $skipped++;
                    continue;
                }

                $price = $this->parseMoney($priceRaw);
                $costPrice = trim($costRaw) === '' ? null : $this->parseMoney($costRaw);
                $stock = $this->parseInt($stockRaw);

                $mainCategory = null;
                if ($mainCategoryName !== '') {
                    $mainCategory = Category::query()->firstOrCreate(
                        ['name' => $mainCategoryName, 'parent_id' => null],
                        ['parent_id' => null]
                    );
                }

                $category = null;
                if ($subCategoryName !== '') {
                    $category = Category::query()->firstOrCreate(
                        ['name' => $subCategoryName, 'parent_id' => $mainCategory?->id],
                        ['parent_id' => $mainCategory?->id]
                    );
                } else {
                    $category = $mainCategory;
                }

                if (! $category) {
                    $skipped++;
                    continue;
                }

                $product = Product::query()
                    ->where('category_id', $category->id)
                    ->where('name', $name)
                    ->get()
                    ->first(fn (Product $candidate) => $candidate->name === $name);

                $payload = [
                    'price' => $price,
                    'cost_price' => $costPrice,
                    'stock' => $stock,
                ];

                if ($product) {
                    $before = $product->only(['price', 'cost_price', 'initial_stock', 'stock']);
                    $incomingStock = max(0, $stock);
                    $currentStock = max(0, (int) $product->stock);
                    $currentInitialStock = max(0, (int) ($product->initial_stock ?? 0));
                    $newStock = $currentStock + $incomingStock;

                    $incomingCostCents = $costPrice !== null
                        ? $this->moneyToCents($costPrice)
                        : null;

                    if ($incomingStock > 0) {
                        $payload['stock'] = $newStock;
                        $payload['initial_stock'] = $currentInitialStock + $incomingStock;

                        if ($incomingCostCents !== null) {
                            $currentCostCents = $product->cost_price !== null
                                ? $this->moneyToCents((float) $product->cost_price)
                                : null;

                            $averageCostCents = $currentStock === 0 || $currentCostCents === null
                                ? $incomingCostCents
                                : (int) round(($currentCostCents + $incomingCostCents) / 2);

                            $payload['cost_price'] = number_format($averageCostCents / 100, 2, '.', '');

                            $calculationUpdates[(int) $product->id] = [
                                'title' => 'Average cost calculation',
                                'formula' => $currentStock === 0 || $currentCostCents === null
                                    ? 'No previous cost. New average cost = incoming cost'
                                    : '(old_cost + incoming_cost) / 2',
                                'old_cost' => $currentCostCents !== null ? number_format($currentCostCents / 100, 2, '.', '') : null,
                                'incoming_cost' => number_format($incomingCostCents / 100, 2, '.', ''),
                                'result_cost' => number_format($averageCostCents / 100, 2, '.', ''),
                                'old_stock' => $currentStock,
                                'incoming_stock' => $incomingStock,
                                'new_stock' => $newStock,
                                'at' => now()->format('M d, Y h:i A'),
                            ];
                        } else {
                            $payload['cost_price'] = $product->cost_price;
                        }
                    } else {
                        $payload['stock'] = $product->stock;
                        $payload['initial_stock'] = $currentInitialStock;
                        $payload['cost_price'] = $product->cost_price;
                    }

                    $product->update($payload);
                    $revertUpdatedProducts[] = [
                        'id' => (int) $product->id,
                        'before' => $before,
                    ];
                    $updated++;
                } else {
                    $createdProduct = Product::create([
                        'name' => $name,
                        'category_id' => $category->id,
                        'is_active' => true,
                        'initial_stock' => max(0, $stock),
                        ...$payload,
                    ]);
                    $revertCreatedProductIds[] = (int) $createdProduct->id;
                    $created++;
                }
            }
        });

        foreach ($calculationUpdates as $productId => $details) {
            $this->storeCostCalculation($request, (int) $productId, (array) $details);
        }

        AuditLogger::record(
            $request,
            'imported',
            'product',
            null,
            null,
            'Imported product file.',
            [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'file_name' => (string) ($request->file('file')?->getClientOriginalName() ?? ''),
                'revert' => [
                    'created_product_ids' => array_values(array_unique(array_map(fn ($id) => (int) $id, $revertCreatedProductIds))),
                    'updated_products' => $revertUpdatedProducts,
                ],
            ]
        );

        $this->forgetPublicCatalogCaches();

        return redirect()
            ->route('admin.products.index', $this->productIndexQuery($request))
            ->with('status', "Import complete: {$created} created, {$updated} updated, {$skipped} skipped.");
    }

    public function revertImport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'audit_log_id' => ['required', 'integer', 'min:1'],
        ]);

        $userId = (int) ($request->user()?->id ?? 0);
        if ($userId <= 0) {
            return back()->with('error', 'Unable to verify your account for import revert.');
        }

        $importLog = AuditLog::query()
            ->whereKey((int) $validated['audit_log_id'])
            ->where('action', 'imported')
            ->where('target_type', 'product')
            ->where('user_id', $userId)
            ->first();

        if (! $importLog) {
            return back()->with('error', 'Import history record was not found.');
        }

        $meta = (array) ($importLog->meta ?? []);
        if (isset($meta['reverted_at']) && trim((string) $meta['reverted_at']) !== '') {
            return back()->with('error', 'This import has already been reverted.');
        }

        $revertData = $this->extractImportRevertData($meta);
        if ($revertData === null) {
            return back()->with('error', 'Missing revert metadata for this import.');
        }

        $deletedCreated = 0;
        $missingCreated = 0;
        $restoredUpdated = 0;
        $missingUpdated = 0;

        try {
            DB::transaction(function () use (
                $request,
                $importLog,
                $meta,
                $revertData,
                &$deletedCreated,
                &$missingCreated,
                &$restoredUpdated,
                &$missingUpdated
            ): void {
                $createdProductIds = $revertData['created_product_ids'];
                if ($createdProductIds !== []) {
                    $createdProducts = Product::query()
                        ->whereIn('id', $createdProductIds)
                        ->get()
                        ->keyBy('id');

                    foreach ($createdProductIds as $productId) {
                        $createdProduct = $createdProducts->get($productId);
                        if (! $createdProduct) {
                            $missingCreated++;
                            continue;
                        }

                        $createdProduct->delete();
                        $deletedCreated++;
                    }
                }

                $updatedProducts = $revertData['updated_products'];
                foreach ($updatedProducts as $row) {
                    $productId = (int) ($row['id'] ?? 0);
                    $before = is_array($row['before'] ?? null) ? (array) $row['before'] : [];
                    if ($productId <= 0 || $before === []) {
                        $missingUpdated++;
                        continue;
                    }

                    $product = Product::query()->find($productId);
                    if (! $product) {
                        $missingUpdated++;
                        continue;
                    }

                    $payload = $this->normalizeImportedProductBeforePayload($before);
                    if ($payload === []) {
                        $missingUpdated++;
                        continue;
                    }

                    $product->update($payload);
                    $restoredUpdated++;
                }

                if ($deletedCreated === 0 && $restoredUpdated === 0) {
                    throw new \RuntimeException('No imported products were reverted. Records may already be gone.');
                }

                $updatedMeta = $meta;
                $updatedMeta['reverted_at'] = now()->toIso8601String();
                $updatedMeta['reverted_by'] = (int) ($request->user()?->id ?? 0);
                $updatedMeta['revert_details'] = [
                    'deleted_created_products' => $deletedCreated,
                    'restored_updated_products' => $restoredUpdated,
                    'missing_created_products' => $missingCreated,
                    'missing_updated_products' => $missingUpdated,
                ];
                $importLog->meta = $updatedMeta;
                $importLog->save();

                AuditLogger::record(
                    $request,
                    'reverted',
                    'product',
                    null,
                    null,
                    sprintf('Reverted product import from history log #%d.', (int) $importLog->id),
                    [
                        'source_audit_log_id' => (int) $importLog->id,
                        'source_action' => 'imported',
                        'source_target_type' => 'product',
                        'details' => $updatedMeta['revert_details'],
                    ]
                );
            });
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (\Throwable) {
            return back()->with('error', 'Unable to revert imported changes.');
        }

        $this->forgetPublicCatalogCaches();

        return redirect()
            ->route('admin.products.index', $this->productIndexQuery($request))
            ->with(
                'status',
                "Import reverted: {$deletedCreated} created removed, {$restoredUpdated} updated restored."
                ." ({$missingCreated} created already missing, {$missingUpdated} updated missing.)"
            );
    }

    private function forgetPublicCatalogCaches(): void
    {
        PublicCatalogCache::forgetAll();
    }

    /**
     * @return array<string, string>
     */
    private function productIndexQuery(Request $request): array
    {
        $query = [];

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query['search'] = $search;
        }

        $category = trim((string) $request->query('category', ''));
        if ($category !== '') {
            $query['category'] = $category;
        }

        $stock = trim((string) $request->query('stock', ''));
        if ($stock !== '' && $stock !== 'all') {
            $query['stock'] = $stock;
        }

        $page = trim((string) $request->query('page', ''));
        if ($page !== '') {
            $query['page'] = $page;
        }

        return $query;
    }

    /**
     * @param array<int, string> $header
     * @return array<string, int>
     */
    private function mapImportColumns(array $header): array
    {
        $map = [];

        foreach ($header as $idx => $value) {
            $key = preg_replace('/[^a-z0-9]/', '', Str::lower((string) $value)) ?? '';

            if (in_array($key, ['productname', 'product', 'name'], true)) {
                $map['name'] = $idx;
            } elseif (in_array($key, ['category', 'categoryname'], true)) {
                $map['category'] = $idx;
            } elseif (in_array($key, ['subcategory', 'subcat', 'subcategories', 'productsubcategory'], true)) {
                $map['sub_category'] = $idx;
            } elseif (in_array($key, ['price', 'sellingprice', 'saleprice', 'priceperunit', 'unitprice'], true)) {
                $map['price'] = $idx;
            } elseif (in_array($key, ['costprice', 'cost', 'costingprice', 'costperunit', 'unitcost'], true)) {
                $map['cost_price'] = $idx;
            } elseif (in_array($key, ['stock', 'qty', 'quantity', 'currentqtyinstock', 'currentqty', 'qtyinstock', 'currentstock', 'stockqty'], true)) {
                $map['stock'] = $idx;
            }
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $meta
     * @return array{created_product_ids: array<int, int>, updated_products: array<int, array{id: int, before: array<string, mixed>}>}|null
     */
    private function extractImportRevertData(array $meta): ?array
    {
        $revert = $meta['revert'] ?? null;
        if (! is_array($revert)) {
            return null;
        }

        $createdProductIds = [];
        foreach ((array) ($revert['created_product_ids'] ?? []) as $id) {
            $productId = (int) $id;
            if ($productId > 0) {
                $createdProductIds[] = $productId;
            }
        }
        $createdProductIds = array_values(array_unique($createdProductIds));

        $updatedProducts = [];
        foreach ((array) ($revert['updated_products'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $productId = (int) ($row['id'] ?? 0);
            $before = is_array($row['before'] ?? null) ? (array) $row['before'] : null;
            if ($productId <= 0 || $before === null) {
                continue;
            }

            $updatedProducts[] = [
                'id' => $productId,
                'before' => $before,
            ];
        }

        return [
            'created_product_ids' => $createdProductIds,
            'updated_products' => $updatedProducts,
        ];
    }

    /**
     * @param array<string, mixed> $before
     * @return array<string, mixed>
     */
    private function normalizeImportedProductBeforePayload(array $before): array
    {
        $payload = [];

        if (array_key_exists('price', $before) && is_numeric($before['price'])) {
            $payload['price'] = round((float) $before['price'], 2);
        }

        if (array_key_exists('cost_price', $before)) {
            $payload['cost_price'] = $before['cost_price'] === null || $before['cost_price'] === ''
                ? null
                : round((float) $before['cost_price'], 2);
        }

        if (array_key_exists('initial_stock', $before)) {
            $payload['initial_stock'] = max(0, (int) $before['initial_stock']);
        }

        if (array_key_exists('stock', $before)) {
            $payload['stock'] = (int) $before['stock'];
        }

        return $payload;
    }

    private function latestRevertableImportLog(int $userId): ?AuditLog
    {
        if ($userId <= 0) {
            return null;
        }

        $importLogs = AuditLog::query()
            ->where('action', 'imported')
            ->where('target_type', 'product')
            ->where('user_id', $userId)
            ->latest('id')
            ->limit(20)
            ->get();

        foreach ($importLogs as $importLog) {
            $meta = (array) ($importLog->meta ?? []);
            if (isset($meta['reverted_at']) && trim((string) $meta['reverted_at']) !== '') {
                continue;
            }

            $revertData = $this->extractImportRevertData($meta);
            if ($revertData === null) {
                continue;
            }

            if ($revertData['created_product_ids'] === [] && $revertData['updated_products'] === []) {
                continue;
            }

            return $importLog;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function productRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'initial_stock' => ['nullable', 'integer', 'min:0'],
            'stock' => ['required', 'integer'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function extractProductPayload(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'price' => $validated['price'],
            'cost_price' => $validated['cost_price'] ?? null,
            'initial_stock' => isset($validated['initial_stock'])
                ? max(0, (int) $validated['initial_stock'])
                : max(0, (int) $validated['stock']),
            'stock' => $validated['stock'],
            'category_id' => $validated['category_id'],
            'description' => $validated['description'] ?? null,
            'image_url' => isset($validated['image_url']) ? trim((string) $validated['image_url']) : null,
        ];
    }

    private function parseMoney(string $value): float
    {
        $clean = preg_replace('/[^\d\.\-]/', '', str_replace(',', '', trim($value))) ?? '';
        if ($clean === '' || $clean === '-' || $clean === '.') {
            return 0.0;
        }
        return (float) $clean;
    }

    private function parseInt(string $value): int
    {
        $clean = preg_replace('/[^\d\-]/', '', trim($value)) ?? '';
        if ($clean === '' || $clean === '-') {
            return 0;
        }
        return (int) $clean;
    }

    private function moneyToCents(float $value): int
    {
        return (int) round($value * 100);
    }

    private function applyProductCatalogSearch(Builder $query, string $search): void
    {
        $trimmedSearch = trim($search);
        if ($trimmedSearch === '') {
            return;
        }

        $booleanQuery = $this->toBooleanFullTextQuery($trimmedSearch);
        if ($this->canUseProductFullTextSearch() && $booleanQuery !== '') {
            $query->whereRaw(
                'MATCH(name, description) AGAINST (? IN BOOLEAN MODE)',
                [$booleanQuery]
            );

            return;
        }

        $query->where(function (Builder $builder) use ($trimmedSearch): void {
            $builder->where('name', 'like', "%{$trimmedSearch}%")
                ->orWhere('description', 'like', "%{$trimmedSearch}%");
        });
    }

    private function canUseProductFullTextSearch(): bool
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return false;
        }

        static $hasMatchingFullTextIndex = null;
        if ($hasMatchingFullTextIndex !== null) {
            return $hasMatchingFullTextIndex;
        }

        try {
            $indexes = DB::select(
                "SELECT index_name, GROUP_CONCAT(column_name ORDER BY seq_in_index SEPARATOR ',') AS cols
                 FROM information_schema.statistics
                 WHERE table_schema = DATABASE()
                   AND table_name = ?
                   AND index_type = 'FULLTEXT'
                 GROUP BY index_name",
                ['products']
            );

            $hasMatchingFullTextIndex = collect($indexes)->contains(function ($index): bool {
                return Str::lower((string) ($index->cols ?? '')) === 'name,description';
            });
        } catch (\Throwable) {
            $hasMatchingFullTextIndex = false;
        }

        return $hasMatchingFullTextIndex;
    }

    private function toBooleanFullTextQuery(string $search): string
    {
        $normalized = preg_replace('/[^\pL\pN]+/u', ' ', Str::lower(trim($search))) ?? '';
        $terms = preg_split('/\s+/', trim($normalized)) ?: [];

        return collect($terms)
            ->filter(fn (string $term): bool => mb_strlen($term) >= 3)
            ->map(fn (string $term): string => $term.'*')
            ->values()
            ->implode(' ');
    }

    /**
     * @return array<int, int>
     */
    private function selectedCategoryIds(Collection $categories, int $categoryId): array
    {
        $childrenByParent = $categories->groupBy(
            fn (Category $category): string => (string) ($category->parent_id ?? '')
        );

        $selectedIds = [];
        $queue = [(int) $categoryId];

        while (! empty($queue)) {
            $currentId = (int) array_shift($queue);
            if (in_array($currentId, $selectedIds, true)) {
                continue;
            }

            $selectedIds[] = $currentId;

            $childrenByParent
                ->get((string) $currentId, collect())
                ->each(function (Category $child) use (&$queue, &$selectedIds): void {
                    $childId = (int) $child->id;
                    if (! in_array($childId, $selectedIds, true)) {
                        $queue[] = $childId;
                    }
                });
        }

        return $selectedIds;
    }

    /**
     * @param array<string, mixed> $details
     */
    private function storeCostCalculation(Request $request, int $productId, array $details): void
    {
        $all = (array) $request->session()->get('product_cost_calculations', []);
        $all[(string) $productId] = $details;
        $request->session()->put('product_cost_calculations', $all);
    }
}
