<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Support\AuditLogger;
use App\Support\SalePaymentMode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SalesController extends Controller
{
    public function index(): View
    {
        $year = request()->string('year')->toString();
        $month = request()->string('month')->toString();
        $search = trim(request()->string('q')->toString());

        $selectedYear = $year !== '' && $year !== 'all' ? (int) $year : null;
        $selectedMonthNumber = $month !== '' && $month !== 'all' ? (int) $month : null;

        $salesQuery = Sale::query()->with('items');

        if ($selectedYear) {
            $salesQuery->whereYear('sold_at', $selectedYear);
        }

        if ($selectedMonthNumber && $selectedMonthNumber >= 1 && $selectedMonthNumber <= 12) {
            $salesQuery->whereMonth('sold_at', $selectedMonthNumber);
        }

        if ($search !== '') {
            $salesQuery->where(function ($query) use ($search): void {
                $query->where('invoice_no', 'like', '%'.$search.'%')
                    ->orWhere('customer_name', 'like', '%'.$search.'%');
            });
        }

        $sales = $salesQuery->orderByDesc('sold_at')->paginate(20)->withQueryString();

        $totalRevenue = (float) Sale::query()->sum('grand_total');
        $totalExpenses = (float) Expense::query()->sum('amount');
        $totalSalesCount = (int) Sale::query()->count();
        $paidRevenue = (float) Sale::query()->sum('amount_paid');
        $outstanding = (float) Sale::query()->selectRaw(
            "coalesce(sum(case
                when cancelled_at is not null or refunded_at is not null or payment_status = 'paid' then 0
                when grand_total > coalesce(amount_paid, 0) then grand_total - coalesce(amount_paid, 0)
                else 0
            end), 0) as v"
        )->value('v');

        $currentMonthStart = now()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();
        $currentMonthRevenue = (float) Sale::query()->whereBetween('sold_at', [$currentMonthStart, $currentMonthEnd])->sum('grand_total');
        $currentMonthExpenses = (float) Expense::query()
            ->whereBetween('spent_at', [$currentMonthStart, $currentMonthEnd])
            ->sum('amount');
        $currentMonthNetSales = $currentMonthRevenue - $currentMonthExpenses;
        $currentMonthSalesCount = (int) Sale::query()->whereBetween('sold_at', [$currentMonthStart, $currentMonthEnd])->count();

        $monthExpression = $this->monthExpression('sold_at');
        $monthlyBreakdown = Sale::query()
            ->selectRaw($monthExpression.' as month')
            ->selectRaw('count(*) as sales_count')
            ->selectRaw('sum(grand_total) as revenue')
            ->groupByRaw($monthExpression)
            ->orderByDesc('month')
            ->limit(12)
            ->get();

        $yearExpression = $this->yearExpression('sold_at');
        $years = Sale::query()
            ->selectRaw($yearExpression.' as year')
            ->groupByRaw($yearExpression)
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($v) => (int) $v)
            ->values();

        return view('admin.sales.index', [
            'sales' => $sales,
            'monthlyBreakdown' => $monthlyBreakdown,
            'years' => $years,
            'selectedYear' => $selectedYear,
            'selectedMonthNumber' => $selectedMonthNumber,
            'search' => $search,
            'totalRevenue' => $totalRevenue,
            'totalExpenses' => $totalExpenses,
            'totalSalesCount' => $totalSalesCount,
            'paidRevenue' => $paidRevenue,
            'outstanding' => $outstanding,
            'currentMonthRevenue' => $currentMonthRevenue,
            'currentMonthExpenses' => $currentMonthExpenses,
            'currentMonthNetSales' => $currentMonthNetSales,
            'currentMonthSalesCount' => $currentMonthSalesCount,
        ]);
    }

    public function create(): View
    {
        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'stock']);

        return view('admin.sales.create', [
            'products' => $products,
            'paymentModeGroups' => SalePaymentMode::groups(),
            'defaultPaymentMode' => SalePaymentMode::DEFAULT,
            'posSurchargeRate' => SalePaymentMode::posSurchargeRate(),
            'posSurchargeModes' => SalePaymentMode::posSurchargeModes(),
        ]);
    }

    public function show(Sale $sale): View
    {
        $sale->load([
            'items',
            'payments' => fn ($query) => $query->orderByDesc('paid_at')->orderByDesc('id'),
            'payments.recorder:id,name,first_name,last_name',
        ]);

        $paidTotal = (float) $sale->payments->where('is_refund', false)->sum('amount');
        $refundTotal = (float) $sale->payments->where('is_refund', true)->sum('amount');
        $netPaid = max(0, $paidTotal - $refundTotal);
        $remaining = max(0, (float) $sale->grand_total - $netPaid);

        return view('admin.sales.show', [
            'sale' => $sale,
            'paymentSummary' => [
                'paid_total' => $paidTotal,
                'refund_total' => $refundTotal,
                'net_paid' => $netPaid,
                'remaining' => $remaining,
            ],
            'hasLegacyPaymentSnapshot' => (float) ($sale->amount_paid ?? 0) > 0 && $sale->payments->isEmpty(),
            'paymentModeGroups' => SalePaymentMode::groups(),
        ]);
    }

    public function downloadReceipt(Sale $sale): Response
    {
        $sale->loadMissing(['items', 'payments' => fn ($query) => $query->orderByDesc('paid_at')->orderByDesc('id')]);

        $invoiceNo = trim((string) ($sale->invoice_no ?: 'SALE-'.$sale->id));
        $safeInvoiceNo = preg_replace('/[^A-Za-z0-9\-_]+/', '-', $invoiceNo) ?: 'SALE-'.$sale->id;

        return Pdf::loadView('admin.sales.receipt-pdf', [
            'sale' => $sale,
            'storeName' => (string) config('app.name', 'Infinite Computers'),
        ])->setPaper('a4')->stream("receipt-{$safeInvoiceNo}.pdf");
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sold_at' => ['required', 'date'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_contact' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['required', 'in:unpaid,partial,paid'],
            'payment_mode' => ['nullable', Rule::in(SalePaymentMode::selectableValues())],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'sale_status' => ['required', 'in:processing,completed'],
            'deduct_stock' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ], [
            'items.min' => 'Add at least one item.',
        ]);

        $deductStock = (bool) ($validated['deduct_stock'] ?? true);
        $items = collect($validated['items'])->map(fn ($row) => [
            'product_id' => (int) $row['product_id'],
            'qty' => max(1, (int) $row['qty']),
            'unit_price' => max(0, (float) $row['unit_price']),
        ])->values();
        $productIds = $items->pluck('product_id')->unique()->values()->all();

        return DB::transaction(function () use ($request, $validated, $deductStock, $items, $productIds) {
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->lockForUpdate()
                ->get(['id', 'name', 'stock'])
                ->keyBy('id');

            $subtotal = 0.0;
            $saleItems = [];
            $qtyByProductId = [];

            foreach ($items as $row) {
                $product = $products->get($row['product_id']);
                if (! $product) {
                    continue;
                }

                $qtyByProductId[$row['product_id']] = ($qtyByProductId[$row['product_id']] ?? 0) + $row['qty'];
                $lineTotal = $row['qty'] * $row['unit_price'];
                $subtotal += $lineTotal;

                $saleItems[] = [
                    'product_id' => (int) $product->id,
                    'product_name' => (string) $product->name,
                    'unit_price' => round($row['unit_price'], 2),
                    'qty' => (int) $row['qty'],
                    'line_total' => round($lineTotal, 2),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (count($saleItems) === 0) {
                throw ValidationException::withMessages(['items' => 'Add at least one valid item.']);
            }

            if ($deductStock) {
                foreach ($qtyByProductId as $productId => $qty) {
                    $product = $products->get($productId);
                    $availableStock = (int) ($product?->stock ?? 0);
                    if ($qty > $availableStock) {
                        throw ValidationException::withMessages([
                            'items' => "Not enough stock for {$product->name}. Available: {$availableStock}.",
                        ]);
                    }
                }
            }

            $discount = min($subtotal, max(0, (float) ($validated['discount'] ?? 0)));
            $baseGrandTotal = max(0, $subtotal - $discount);

            $paymentStatus = (string) ($validated['payment_status'] ?? 'paid');
            $paymentMode = (string) ($validated['payment_mode'] ?? SalePaymentMode::DEFAULT);
            $grandTotal = SalePaymentMode::applyPosSurcharge($baseGrandTotal, $paymentMode);
            $amountPaidInput = max(0, (float) ($validated['amount_paid'] ?? 0));
            $amountPaid = match ($paymentStatus) {
                'unpaid' => 0.0,
                'paid' => $grandTotal,
                default => min($grandTotal, $amountPaidInput),
            };

            if ($paymentStatus === 'partial' && ! ($amountPaid > 0 && $amountPaid < $grandTotal)) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'For partial payments, amount paid must be greater than 0 and less than the grand total.',
                ]);
            }

            $soldAt = Carbon::parse($validated['sold_at']);
            $sale = $this->createSaleWithSafeInvoice([
                'created_by' => $request->user()?->id,
                'sold_at' => $soldAt,
                'customer_name' => $validated['customer_name'],
                'customer_contact' => $validated['customer_contact'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'subtotal' => round($subtotal, 2),
                'discount' => round($discount, 2),
                'grand_total' => round($grandTotal, 2),
                'payment_status' => $paymentStatus,
                'amount_paid' => round($amountPaid, 2),
                'payment_mode' => $paymentMode,
                'sale_status' => (string) $validated['sale_status'],
            ], $soldAt);

            foreach ($saleItems as &$saleItem) {
                $saleItem['sale_id'] = $sale->id;
            }
            unset($saleItem);
            SaleItem::query()->insert($saleItems);

            if ($deductStock) {
                foreach ($qtyByProductId as $productId => $qty) {
                    Product::query()->whereKey($productId)->decrement('stock', (int) $qty);
                }
            }

            if ($amountPaid > 0) {
                $this->recordPayment($sale, [
                    'recorded_by' => $request->user()?->id,
                    'amount' => round($amountPaid, 2),
                    'is_refund' => false,
                    'method' => $paymentMode,
                    'notes' => 'Initial payment recorded during sale creation.',
                    'paid_at' => $soldAt,
                ]);
            }

            $this->syncSalePaymentSnapshot($sale);

            AuditLogger::record($request, 'created', 'sale', (int) $sale->id, (string) $sale->invoice_no, 'Recorded sale.', [
                'customer_name' => (string) $sale->customer_name,
                'grand_total' => (float) $sale->grand_total,
                'item_count' => count($saleItems),
                'deduct_stock' => $deductStock,
                'initial_paid' => (float) $amountPaid,
                'payment_mode' => $paymentMode,
            ]);

            return redirect()->route('admin.sales')->with('status', 'Sale recorded successfully.');
        });
    }

    public function storeFromQuotation(Request $request, Quotation $quotation): RedirectResponse
    {
        if ($quotation->sale_id) {
            $sale = Sale::query()->find($quotation->sale_id);
            $month = optional($sale?->sold_at)->format('Y-m') ?: now()->format('Y-m');
            return redirect()->route('admin.sales', ['month' => $month])->with('status', 'This quotation is already added to sales.');
        }

        $validated = $request->validate(['deduct_stock' => ['nullable', 'boolean']]);
        $deductStock = (bool) ($validated['deduct_stock'] ?? true);
        $rawItems = is_array($quotation->items) ? $quotation->items : [];

        if (trim((string) $quotation->customer_name) === '') {
            throw ValidationException::withMessages(['customer_name' => 'Customer name is required before adding to sales.']);
        }
        if (count($rawItems) === 0) {
            throw ValidationException::withMessages(['items' => 'This quotation has no items.']);
        }

        $items = collect($rawItems)->map(fn ($row) => [
            'product_id' => isset($row['product_id']) ? (int) $row['product_id'] : null,
            'product_name' => (string) ($row['product_name'] ?? ''),
            'qty' => max(1, (int) ($row['qty'] ?? 0)),
            'unit_price' => max(0, (float) ($row['unit_price'] ?? 0)),
        ])->filter(fn ($row) => ! empty($row['product_id']) && $row['product_name'] !== '')->values();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'This quotation has no valid items.']);
        }

        $productIds = $items->pluck('product_id')->unique()->values()->all();

        return DB::transaction(function () use ($request, $quotation, $deductStock, $items, $productIds) {
            $products = Product::query()->whereIn('id', $productIds)->lockForUpdate()->get(['id', 'name', 'stock'])->keyBy('id');

            $itemsSubtotal = 0.0;
            $saleItems = [];
            $qtyByProductId = [];

            foreach ($items as $row) {
                $product = $products->get((int) $row['product_id']);
                if (! $product) {
                    if ($deductStock) {
                        throw ValidationException::withMessages(['items' => "Product not found for item: {$row['product_name']}."]);
                    }
                    continue;
                }

                $qtyByProductId[(int) $product->id] = ($qtyByProductId[(int) $product->id] ?? 0) + (int) $row['qty'];
                $lineTotal = ((int) $row['qty']) * ((float) $row['unit_price']);
                $itemsSubtotal += $lineTotal;

                $saleItems[] = [
                    'product_id' => (int) $product->id,
                    'product_name' => (string) $row['product_name'],
                    'unit_price' => round((float) $row['unit_price'], 2),
                    'qty' => (int) $row['qty'],
                    'line_total' => round($lineTotal, 2),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (count($saleItems) === 0) {
                throw ValidationException::withMessages(['items' => 'This quotation has no items that can be recorded as sales.']);
            }

            if ($deductStock) {
                foreach ($qtyByProductId as $productId => $qty) {
                    $product = $products->get($productId);
                    $availableStock = (int) ($product?->stock ?? 0);
                    if ($qty > $availableStock) {
                        throw ValidationException::withMessages(['items' => "Not enough stock for {$product->name}. Available: {$availableStock}."]);
                    }
                }
            }

            $laborFee = max(0, (float) $quotation->labor_fee);
            if ($laborFee > 0) {
                $saleItems[] = [
                    'product_id' => null,
                    'product_name' => 'Labor / Service Fee',
                    'unit_price' => round($laborFee, 2),
                    'qty' => 1,
                    'line_total' => round($laborFee, 2),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $computedBase = $itemsSubtotal + $laborFee;
            $discount = min($computedBase, max(0, (float) $quotation->discount_amount));
            $grandTotal = max(0, $computedBase - $discount);

            $notes = trim((string) $quotation->notes);
            $conversionNote = "Converted from quotation #{$quotation->id}. Labor fee: ".number_format($laborFee, 2).'.';
            $mergedNotes = trim($notes !== '' ? ($notes."\n\n".$conversionNote) : $conversionNote);

            $sale = $this->createSaleWithSafeInvoice([
                'created_by' => $request->user()?->id,
                'sold_at' => now(),
                'customer_name' => $quotation->customer_name,
                'customer_contact' => $quotation->customer_contact,
                'notes' => $mergedNotes,
                'subtotal' => round($computedBase, 2),
                'discount' => round($discount, 2),
                'grand_total' => round($grandTotal, 2),
                'payment_status' => 'paid',
                'amount_paid' => round($grandTotal, 2),
                'payment_mode' => SalePaymentMode::DEFAULT,
                'sale_status' => 'completed',
            ], now());

            foreach ($saleItems as &$saleItem) {
                $saleItem['sale_id'] = $sale->id;
            }
            unset($saleItem);
            SaleItem::query()->insert($saleItems);

            if ($deductStock) {
                foreach ($qtyByProductId as $productId => $qty) {
                    Product::query()->whereKey($productId)->decrement('stock', (int) $qty);
                }
            }

            if ($grandTotal > 0) {
                $this->recordPayment($sale, [
                    'recorded_by' => $request->user()?->id,
                    'amount' => round($grandTotal, 2),
                    'is_refund' => false,
                    'method' => SalePaymentMode::DEFAULT,
                    'notes' => 'Initial payment recorded during quotation conversion.',
                    'paid_at' => now(),
                ]);
            }

            $this->syncSalePaymentSnapshot($sale);
            $quotation->update(['sale_id' => $sale->id]);

            AuditLogger::record($request, 'converted_from_quotation', 'sale', (int) $sale->id, (string) $sale->invoice_no, 'Converted quotation to sale.', [
                'quotation_id' => (int) $quotation->id,
                'quotation_name' => (string) ($quotation->quotation_name ?? ''),
                'grand_total' => (float) $sale->grand_total,
                'deduct_stock' => $deductStock,
            ]);

            return redirect()->route('admin.sales')->with('status', $deductStock ? 'Added to sales and deducted stock.' : 'Added to sales.');
        });
    }

    public function updatePayment(Request $request, Sale $sale): RedirectResponse
    {
        $validated = $request->validate([
            'payment_status' => ['required', 'in:unpaid,partial,paid'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
        ]);

        return DB::transaction(function () use ($request, $sale, $validated) {
            $sale = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();
            $this->ensurePaymentHistorySeeded($sale, $request->user()?->id);

            if ($sale->cancelled_at || $sale->refunded_at || (string) $sale->payment_status === 'paid') {
                return back()->withErrors([
                    'payment_status' => 'Payment status is locked for paid, cancelled, or refunded sales.',
                ]);
            }

            $beforeStatus = (string) ($sale->payment_status ?? 'unpaid');
            $beforeAmountPaid = (float) ($sale->amount_paid ?? 0);
            $snapshot = $this->paymentSnapshot($sale);

            $grandTotal = (float) $sale->grand_total;
            $paymentStatus = (string) $validated['payment_status'];
            $amountPaidInput = max(0, (float) ($validated['amount_paid'] ?? 0));
            $target = match ($paymentStatus) {
                'unpaid' => 0.0,
                'paid' => $grandTotal,
                default => min($grandTotal, $amountPaidInput),
            };

            if ($paymentStatus === 'partial' && ! ($target > 0 && $target < $grandTotal)) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'For partial payments, amount paid must be greater than 0 and less than the grand total.',
                ]);
            }

            $delta = round($target - (float) $snapshot['net_paid_clamped'], 2);
            if (abs($delta) >= 0.01) {
                $this->recordPayment($sale, [
                    'recorded_by' => $request->user()?->id,
                    'amount' => abs($delta),
                    'is_refund' => $delta < 0,
                    'method' => 'adjustment',
                    'notes' => 'Manual payment status adjustment.',
                    'paid_at' => now(),
                ]);
            }

            $this->syncSalePaymentSnapshot($sale);

            AuditLogger::record($request, 'payment_updated', 'sale', (int) $sale->id, (string) $sale->invoice_no, 'Updated sale payment status.', [
                'before_status' => $beforeStatus,
                'after_status' => (string) $sale->payment_status,
                'before_amount_paid' => $beforeAmountPaid,
                'after_amount_paid' => (float) $sale->amount_paid,
                'delta' => $delta,
            ]);

            return back()->with('status', abs($delta) >= 0.01 ? 'Payment status updated.' : 'Payment is already up to date.');
        });
    }

    public function updateStatus(Request $request, Sale $sale): RedirectResponse
    {
        $validated = $request->validate([
            'sale_status' => ['required', 'in:processing,completed'],
        ]);

        if ($sale->cancelled_at || $sale->refunded_at || (string) $sale->sale_status === 'completed') {
            return back()->withErrors([
                'sale_status' => 'Sale status is locked for completed, cancelled, or refunded sales.',
            ]);
        }

        $beforeStatus = (string) ($sale->sale_status ?? 'processing');
        $sale->update(['sale_status' => (string) $validated['sale_status']]);

        AuditLogger::record($request, 'status_updated', 'sale', (int) $sale->id, (string) $sale->invoice_no, 'Updated sale status.', [
            'before_status' => $beforeStatus,
            'after_status' => (string) $sale->sale_status,
        ]);

        return back()->with('status', 'Sale status updated.');
    }

    public function addPayment(Request $request, Sale $sale): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', 'max:100', Rule::in(SalePaymentMode::selectableValues())],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'paid_at' => ['nullable', 'date'],
        ]);

        return DB::transaction(function () use ($request, $sale, $validated) {
            $sale = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();
            $this->ensurePaymentHistorySeeded($sale, $request->user()?->id);

            $amount = round((float) $validated['amount'], 2);
            $beforeAmountPaid = (float) ($sale->amount_paid ?? 0);

            $this->recordPayment($sale, [
                'recorded_by' => $request->user()?->id,
                'amount' => $amount,
                'is_refund' => false,
                'method' => $validated['method'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'paid_at' => isset($validated['paid_at']) ? Carbon::parse($validated['paid_at']) : now(),
            ]);

            $this->syncSalePaymentSnapshot($sale);

            AuditLogger::record($request, 'payment_recorded', 'sale', (int) $sale->id, (string) $sale->invoice_no, 'Recorded additional payment.', [
                'amount' => $amount,
                'before_amount_paid' => $beforeAmountPaid,
                'after_amount_paid' => (float) $sale->amount_paid,
            ]);

            return back()->with('status', 'Payment recorded.');
        });
    }

    public function refund(Request $request, Sale $sale): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', 'max:100', Rule::in(SalePaymentMode::selectableValues())],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'paid_at' => ['nullable', 'date'],
            'restock_items' => ['nullable', 'boolean'],
        ]);

        return DB::transaction(function () use ($request, $sale, $validated) {
            $sale = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();
            $this->ensurePaymentHistorySeeded($sale, $request->user()?->id);

            $snapshot = $this->paymentSnapshot($sale);
            $currentNetPaid = (float) $snapshot['net_paid_clamped'];
            if ($currentNetPaid <= 0) {
                throw ValidationException::withMessages(['amount' => 'There is no paid amount left to refund.']);
            }

            $refundAmount = round((float) $validated['amount'], 2);
            if ($refundAmount > $currentNetPaid) {
                throw ValidationException::withMessages(['amount' => 'Refund amount cannot exceed currently paid amount.']);
            }

            $beforeAmountPaid = (float) ($sale->amount_paid ?? 0);
            $this->recordPayment($sale, [
                'recorded_by' => $request->user()?->id,
                'amount' => $refundAmount,
                'is_refund' => true,
                'method' => $validated['method'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'paid_at' => isset($validated['paid_at']) ? Carbon::parse($validated['paid_at']) : now(),
            ]);

            $restockRequested = (bool) ($validated['restock_items'] ?? false);
            $restockedUnits = 0;
            if ($restockRequested && $sale->stock_restocked_at === null) {
                $restockedUnits = $this->restockSaleInventory($sale);
                $sale->stock_restocked_at = now();
                $sale->save();
            }

            $this->syncSalePaymentSnapshot($sale);
            if ((float) ($sale->amount_paid ?? 0) <= 0.00001 && $sale->refunded_at === null) {
                $sale->refunded_at = now();
                $sale->save();
            }

            AuditLogger::record($request, 'refund_recorded', 'sale', (int) $sale->id, (string) $sale->invoice_no, 'Recorded refund.', [
                'refund_amount' => $refundAmount,
                'before_amount_paid' => $beforeAmountPaid,
                'after_amount_paid' => (float) $sale->amount_paid,
                'restock_requested' => $restockRequested,
                'restocked_units' => $restockedUnits,
            ]);

            return back()->with('status', 'Refund recorded.');
        });
    }

    public function cancel(Request $request, Sale $sale): RedirectResponse
    {
        $validated = $request->validate([
            'restock_items' => ['nullable', 'boolean'],
        ]);

        return DB::transaction(function () use ($request, $sale, $validated) {
            $sale = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();
            if ($sale->cancelled_at !== null) {
                return back()->with('status', 'Sale is already cancelled.');
            }

            $restockRequested = (bool) ($validated['restock_items'] ?? true);
            $restockedUnits = 0;
            if ($restockRequested && $sale->stock_restocked_at === null) {
                $restockedUnits = $this->restockSaleInventory($sale);
                $sale->stock_restocked_at = now();
            }

            $sale->cancelled_at = now();
            $sale->save();

            AuditLogger::record($request, 'cancelled', 'sale', (int) $sale->id, (string) $sale->invoice_no, 'Cancelled sale.', [
                'restock_requested' => $restockRequested,
                'restocked_units' => $restockedUnits,
            ]);

            return back()->with('status', 'Sale cancelled.');
        });
    }

    private function paymentSnapshot(Sale $sale): array
    {
        $summary = SalePayment::query()
            ->where('sale_id', $sale->id)
            ->selectRaw('coalesce(sum(case when is_refund = 0 then amount else 0 end), 0) as paid_total')
            ->selectRaw('coalesce(sum(case when is_refund = 1 then amount else 0 end), 0) as refund_total')
            ->first();

        $paidTotal = (float) ($summary?->paid_total ?? 0);
        $refundTotal = (float) ($summary?->refund_total ?? 0);
        $netPaid = max(0, round($paidTotal - $refundTotal, 2));
        $grandTotal = max(0, (float) ($sale->grand_total ?? 0));
        $netPaidClamped = min($grandTotal, $netPaid);
        $status = match (true) {
            $netPaidClamped <= 0.00001 => 'unpaid',
            $netPaidClamped + 0.00001 >= $grandTotal => 'paid',
            default => 'partial',
        };

        return [
            'paid_total' => $paidTotal,
            'refund_total' => $refundTotal,
            'net_paid_clamped' => $netPaidClamped,
            'payment_status' => $status,
        ];
    }

    private function syncSalePaymentSnapshot(Sale $sale): void
    {
        $snapshot = $this->paymentSnapshot($sale);

        $sale->forceFill([
            'amount_paid' => round((float) $snapshot['net_paid_clamped'], 2),
            'payment_status' => (string) $snapshot['payment_status'],
        ])->save();

        if ((float) $snapshot['net_paid_clamped'] > 0.00001 && $sale->refunded_at !== null) {
            $sale->forceFill(['refunded_at' => null])->save();
        }

        if ((float) $snapshot['refund_total'] > 0 && (float) $snapshot['net_paid_clamped'] <= 0.00001 && $sale->refunded_at === null) {
            $sale->forceFill(['refunded_at' => now()])->save();
        }

        $sale->refresh();
    }

    private function ensurePaymentHistorySeeded(Sale $sale, ?int $userId = null): void
    {
        if ($sale->payments()->exists()) {
            return;
        }

        $legacyPaid = min(max(0, (float) ($sale->amount_paid ?? 0)), max(0, (float) ($sale->grand_total ?? 0)));
        if ($legacyPaid <= 0) {
            return;
        }

        $this->recordPayment($sale, [
            'recorded_by' => $userId,
            'amount' => $legacyPaid,
            'is_refund' => false,
            'method' => 'legacy',
            'notes' => 'Seeded from existing payment snapshot.',
            'paid_at' => $sale->sold_at ?? $sale->created_at ?? now(),
        ]);
    }

    private function recordPayment(Sale $sale, array $attributes): SalePayment
    {
        return $sale->payments()->create([
            'recorded_by' => $attributes['recorded_by'] ?? null,
            'amount' => round(max(0, (float) ($attributes['amount'] ?? 0)), 2),
            'is_refund' => (bool) ($attributes['is_refund'] ?? false),
            'method' => isset($attributes['method']) ? trim((string) $attributes['method']) : null,
            'reference' => isset($attributes['reference']) ? trim((string) $attributes['reference']) : null,
            'notes' => isset($attributes['notes']) ? trim((string) $attributes['notes']) : null,
            'paid_at' => $attributes['paid_at'] ?? now(),
        ]);
    }

    private function restockSaleInventory(Sale $sale): int
    {
        $sale->loadMissing('items');

        $qtyByProductId = $sale->items
            ->filter(fn (SaleItem $item) => $item->product_id !== null)
            ->groupBy('product_id')
            ->map(fn ($items) => (int) $items->sum(fn (SaleItem $item) => (int) $item->qty));

        if ($qtyByProductId->isEmpty()) {
            return 0;
        }

        $productIds = $qtyByProductId->keys()->map(fn ($id) => (int) $id)->values()->all();
        $products = Product::query()->whereIn('id', $productIds)->lockForUpdate()->get(['id'])->keyBy('id');

        $restockedUnits = 0;
        foreach ($qtyByProductId as $productId => $qty) {
            if (! $products->has((int) $productId)) {
                continue;
            }
            Product::query()->whereKey((int) $productId)->increment('stock', (int) $qty);
            $restockedUnits += (int) $qty;
        }

        return $restockedUnits;
    }

    private function createSaleWithSafeInvoice(array $attributes, Carbon $soldAt): Sale
    {
        for ($attempt = 0; $attempt < 25; $attempt++) {
            $payload = $attributes;
            $payload['invoice_no'] = $this->generateInvoiceNo($soldAt);

            try {
                return Sale::query()->create($payload);
            } catch (QueryException $exception) {
                if ($this->isDuplicateInvoiceException($exception)) {
                    continue;
                }
                throw $exception;
            }
        }

        throw ValidationException::withMessages([
            'invoice_no' => 'Could not generate a unique invoice number. Please try again.',
        ]);
    }

    private function isDuplicateInvoiceException(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = (string) ($exception->errorInfo[1] ?? '');
        $message = strtolower($exception->getMessage());

        if ($sqlState !== '23000' && $sqlState !== '23505' && $driverCode !== '1062') {
            return false;
        }

        return str_contains($message, 'invoice_no')
            || str_contains($message, 'sales_invoice_no_unique')
            || str_contains($message, 'sales.invoice_no');
    }

    private function generateInvoiceNo(Carbon $soldAt): string
    {
        $prefix = 'INV-'.$soldAt->format('Ymd').'-';

        for ($i = 0; $i < 10; $i++) {
            $candidate = $prefix.str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
            if (! Sale::query()->where('invoice_no', $candidate)->exists()) {
                return $candidate;
            }
        }

        return $prefix.str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT).'-'.str_pad((string) random_int(0, 99), 2, '0', STR_PAD_LEFT);
    }

    private function monthExpression(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    private function yearExpression(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => "strftime('%Y', {$column})",
            'pgsql' => "to_char({$column}, 'YYYY')",
            default => "DATE_FORMAT({$column}, '%Y')",
        };
    }
}
