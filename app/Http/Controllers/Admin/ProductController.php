<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Support\SpreadsheetReader;
use App\Support\AuditLogger;
use Illuminate\Support\Collection;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->integer('category');
        $stockFilter = (string) $request->query('stock', 'all');

        $categories = Category::query()
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get();

        $productsQuery = Product::query()
            ->with('category')
            ->latest();

        if ($search !== '') {
            $productsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($categoryQuery) use ($search) {
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

        return view('admin.products.index', compact('products', 'categories', 'search', 'categoryId', 'stockFilter', 'costCalculations'));
    }

    public function create(Request $request)
    {
        $categories = Category::all();
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

            return redirect()
                ->route('admin.products.index', $this->productIndexQuery($request))
                ->with('status', 'Stock-in complete. Quantity and average cost updated.');
        }

        if ($request->hasFile('image')) {
            $payload['image_path'] = $request->file('image')->store('products', 'public');
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

        return redirect()->route('admin.products.index', $this->productIndexQuery($request));
    }

    public function edit(Request $request, Product $product)
    {
        $categories = Category::all();
        $indexQuery = $this->productIndexQuery($request);

        return view('admin.products.edit', compact('product', 'categories', 'indexQuery'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate($this->productRules());
        $payload = $this->extractProductPayload($validated);
        $before = $product->only(['name', 'category_id', 'price', 'cost_price', 'initial_stock', 'stock', 'is_active']);

        if ($request->boolean('remove_image') && $product->image_path) {
            Storage::disk('public')->delete($product->image_path);
            $payload['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $payload['image_path'] = $request->file('image')->store('products', 'public');
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

        return redirect()->route('admin.products.index', $this->productIndexQuery($request));
    }

    public function destroy(Product $product)
    {
        $name = (string) $product->name;
        $categoryId = (int) $product->category_id;
        $stock = (int) $product->stock;

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
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
            ]
        );

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

            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
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
                ]
            );

            $deletedCount++;
        }

        if ($deletedCount === 0) {
            return back()->with('status', 'No matching products were found to delete.');
        }

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

        DB::transaction(function () use ($rows, $columnMap, &$created, &$updated, &$skipped, &$calculationUpdates) {
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
                    $updated++;
                } else {
                    Product::create([
                        'name' => $name,
                        'category_id' => $category->id,
                        'is_active' => true,
                        'initial_stock' => max(0, $stock),
                        ...$payload,
                    ]);
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
            ]
        );

        $this->forgetPublicCatalogCaches();

        return redirect()
            ->route('admin.products.index', $this->productIndexQuery($request))
            ->with('status', "Import complete: {$created} created, {$updated} updated, {$skipped} skipped.");
    }

    private function forgetPublicCatalogCaches(): void
    {
        Cache::forget('public_categories_list_v1');
        Cache::forget('public_categories_list_v2');
        Cache::forget('public_landing_data_v1');
        Cache::forget('public_landing_data_v2');
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
