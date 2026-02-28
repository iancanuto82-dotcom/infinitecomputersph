<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Product;
use App\Models\ReplacementWarrantyItem;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReplacementWarrantyController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $selectedType = $this->normalizeTypeFilter((string) $request->query('type', ''));

        $query = ReplacementWarrantyItem::query()
            ->with([
                'product:id,name',
                'processor:id,name,first_name,last_name',
                'expense:id,title,amount,spent_at',
            ]);

        if ($selectedType !== '') {
            $query->where('type', $selectedType);
        }

        if ($search !== '') {
            $query->where(function ($inner) use ($search): void {
                $inner->where('product_name', 'like', '%'.$search.'%')
                    ->orWhere('notes', 'like', '%'.$search.'%');
            });
        }

        $items = $query
            ->orderByDesc('processed_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $totalWriteOff = (float) ReplacementWarrantyItem::query()->sum('total_cost');
        $currentMonthWriteOff = (float) ReplacementWarrantyItem::query()
            ->whereBetween('processed_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('total_cost');
        $totalUnits = (int) ReplacementWarrantyItem::query()->sum('quantity');

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'stock', 'cost_price']);

        return view('admin.replacements.index', [
            'items' => $items,
            'products' => $products,
            'selectedType' => $selectedType,
            'search' => $search,
            'totalWriteOff' => $totalWriteOff,
            'currentMonthWriteOff' => $currentMonthWriteOff,
            'totalUnits' => $totalUnits,
        ]);
    }

    public function inventory(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $selectedType = $this->normalizeTypeFilter((string) $request->query('type', ''));
        $inventoryData = $this->buildRmaInventoryData($search, $selectedType);

        return view('admin.replacements.inventory', [
            'search' => $search,
            'selectedType' => $selectedType,
            'rmaInventory' => $inventoryData['rmaInventory'],
            'rmaInventoryUnits' => $inventoryData['rmaInventoryUnits'],
            'rmaInventoryValue' => $inventoryData['rmaInventoryValue'],
            'rmaInventoryProductCount' => $inventoryData['rmaInventoryProductCount'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'type' => ['required', 'in:replacement,warranty'],
            'quantity' => ['required', 'integer', 'min:1'],
            'processed_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $quantity = (int) $validated['quantity'];
        $type = (string) $validated['type'];
        $processedAt = Carbon::parse($validated['processed_at']);
        $notes = isset($validated['notes']) ? trim((string) $validated['notes']) : null;

        $record = null;

        DB::transaction(function () use ($request, $validated, $quantity, $type, $processedAt, $notes, &$record): void {
            $product = Product::query()
                ->whereKey((int) $validated['product_id'])
                ->lockForUpdate()
                ->first();

            if (! $product) {
                throw ValidationException::withMessages([
                    'product_id' => 'Selected product was not found.',
                ]);
            }

            $currentStock = (int) $product->stock;
            if ($currentStock < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Not enough stock for {$product->name}. Available: {$currentStock}.",
                ]);
            }

            if ($product->cost_price === null) {
                throw ValidationException::withMessages([
                    'product_id' => "Product {$product->name} has no cost price yet.",
                ]);
            }

            $unitCost = round((float) $product->cost_price, 2);
            $totalCost = round($unitCost * $quantity, 2);

            $product->decrement('stock', $quantity);

            $record = ReplacementWarrantyItem::query()->create([
                'processed_by' => (int) ($request->user()?->id ?? 0) ?: null,
                'product_id' => (int) $product->id,
                'product_name' => (string) $product->name,
                'type' => $type,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'processed_at' => $processedAt,
                'notes' => $notes,
            ]);

            $expenseTitlePrefix = $type === 'warranty' ? 'Warranty' : 'Replacement';
            $expense = Expense::query()->create([
                'created_by' => (int) ($request->user()?->id ?? 0) ?: null,
                'spent_at' => $processedAt,
                'title' => sprintf('%s write-off: %s x%d', $expenseTitlePrefix, (string) $product->name, $quantity),
                'category' => 'Replacement/Warranty',
                'amount' => $totalCost,
                'notes' => $notes,
            ]);

            $record->expense_id = (int) $expense->id;
            $record->save();

            AuditLogger::record(
                $request,
                'created',
                'replacement_item',
                (int) $record->id,
                (string) $product->name,
                'Recorded replacement/warranty write-off.',
                [
                    'product_id' => (int) $product->id,
                    'type' => $type,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'stock_before' => $currentStock,
                    'stock_after' => $currentStock - $quantity,
                    'expense_id' => (int) $expense->id,
                ]
            );

            AuditLogger::record(
                $request,
                'created',
                'expense',
                (int) $expense->id,
                (string) $expense->title,
                'Recorded expense from replacement/warranty write-off.',
                [
                    'amount' => (float) $expense->amount,
                    'spent_at' => optional($expense->spent_at)->toIso8601String(),
                    'source_replacement_item_id' => (int) $record->id,
                ]
            );
        });

        return redirect()
            ->route('admin.replacements.index')
            ->with('status', 'Replacement/warranty item recorded and written off as expense.');
    }

    private function normalizeTypeFilter(string $type): string
    {
        $value = trim($type);
        return in_array($value, ['replacement', 'warranty'], true) ? $value : '';
    }

    /**
     * @return array{
     *   rmaInventory: \Illuminate\Support\Collection<int, object>,
     *   rmaInventoryUnits: int,
     *   rmaInventoryValue: float,
     *   rmaInventoryProductCount: int
     * }
     */
    private function buildRmaInventoryData(string $search, string $selectedType): array
    {
        $rmaInventoryQuery = ReplacementWarrantyItem::query()
            ->select([
                'product_id',
                'product_name',
                'type',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total_cost) as total_cost'),
                DB::raw('MAX(processed_at) as last_processed_at'),
            ])
            ->groupBy('product_id', 'product_name', 'type');

        if ($selectedType !== '') {
            $rmaInventoryQuery->where('type', $selectedType);
        }

        if ($search !== '') {
            $rmaInventoryQuery->where('product_name', 'like', '%'.$search.'%');
        }

        $rmaInventory = $rmaInventoryQuery
            ->orderByDesc(DB::raw('SUM(quantity)'))
            ->limit(500)
            ->get();

        $inventoryProductIds = $rmaInventory
            ->pluck('product_id')
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $currentStocksByProductId = Product::query()
            ->whereIn('id', $inventoryProductIds)
            ->pluck('stock', 'id');

        $rmaInventory = $rmaInventory->map(function ($row) use ($currentStocksByProductId) {
            $productId = $row->product_id !== null ? (int) $row->product_id : null;
            $row->current_stock = $productId !== null && $currentStocksByProductId->has($productId)
                ? (int) $currentStocksByProductId->get($productId)
                : null;
            return $row;
        });

        $rmaInventoryUnits = (int) $rmaInventory->sum(fn ($row) => (int) ($row->total_quantity ?? 0));
        $rmaInventoryValue = (float) $rmaInventory->sum(fn ($row) => (float) ($row->total_cost ?? 0));
        $rmaInventoryProductCount = (int) $rmaInventory
            ->map(fn ($row) => trim((string) ($row->product_name ?? '')))
            ->filter(fn ($name) => $name !== '')
            ->unique()
            ->count();

        return [
            'rmaInventory' => $rmaInventory,
            'rmaInventoryUnits' => $rmaInventoryUnits,
            'rmaInventoryValue' => $rmaInventoryValue,
            'rmaInventoryProductCount' => $rmaInventoryProductCount,
        ];
    }
}
