<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PcBuilderController extends Controller
{
    public function __invoke(Request $request): View
    {
        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'category_id', 'stock'])
            ->map(fn (Product $product) => [
                'id' => (int) $product->id,
                'name' => $product->name,
                'price' => (float) $product->price,
                'category_id' => $product->category_id ? (int) $product->category_id : null,
                'stock' => (int) $product->stock,
            ])
            ->values();

        $copiedFromQuotationId = null;
        $copiedQuotation = null;
        $copyId = (int) $request->integer('copy');

        if ($copyId > 0) {
            $source = Quotation::query()->find($copyId);

            if ($source) {
                $copiedFromQuotationId = (int) $source->id;
                $copiedQuotation = [
                    'quotation_name' => $source->quotation_name,
                    'customer_name' => $source->customer_name,
                    'customer_contact' => $source->customer_contact,
                    'notes' => $source->notes,
                    'labor_fee' => (float) ($source->labor_fee ?? 0),
                    'discount_type' => $source->discount_type === 'percent' ? 'percent' : 'amount',
                    'discount' => (float) ($source->discount ?? 0),
                    'items' => collect(is_array($source->items) ? $source->items : [])
                        ->map(fn ($item) => [
                            'section_key' => (string) ($item['section_key'] ?? ''),
                            'product_id' => (int) ($item['product_id'] ?? 0),
                            'qty' => max(1, (int) ($item['qty'] ?? 1)),
                        ])
                        ->filter(fn ($item) => $item['product_id'] > 0)
                        ->values()
                        ->all(),
                ];
            }
        }

        return view('admin.pc-builder', [
            'categories' => $categories,
            'products' => $products,
            'copiedQuotation' => $copiedQuotation,
            'copiedFromQuotationId' => $copiedFromQuotationId,
        ]);
    }

    public function history(): View
    {
        $quotationHistory = Quotation::query()
            ->latest()
            ->paginate(20);

        return view('admin.pc-builder-history', [
            'quotationHistory' => $quotationHistory,
        ]);
    }

    public function preview(Quotation $quotation): View
    {
        return view('admin.pc-builder-quotation-preview', [
            'quotation' => $quotation,
        ]);
    }

    public function downloadPdf(Quotation $quotation): Response
    {
        $quoteData = [
            'quotation_name' => $quotation->quotation_name,
            'customer_name' => $quotation->customer_name,
            'customer_contact' => $quotation->customer_contact,
            'notes' => $quotation->notes,
            'subtotal' => (float) $quotation->subtotal,
            'labor_fee' => (float) $quotation->labor_fee,
            'discount_type' => $quotation->discount_type,
            'discount' => (float) $quotation->discount,
            'discount_amount' => (float) $quotation->discount_amount,
            'grand_total' => (float) $quotation->grand_total,
            'items' => is_array($quotation->items) ? $quotation->items : [],
        ];

        return Pdf::loadView('admin.pc-builder-quotation-pdf', [
            'quotation' => $quoteData,
            'quoteId' => $quotation->id,
            'quoteDate' => $quotation->created_at,
        ])->setPaper('a4')->download("quotation-{$quotation->id}.pdf");
    }

    public function downloadPreviewPdf(Request $request): Response|RedirectResponse
    {
        $payload = $this->decodePayloadFromRequest($request);

        if (!is_array($payload)) {
            return back()
                ->withErrors(['payload' => 'Invalid quotation payload. Please try again.']);
        }

        try {
            $quoteData = $this->buildQuotationData($payload);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return Pdf::loadView('admin.pc-builder-quotation-pdf', [
            'quotation' => $quoteData,
            'quoteId' => null,
            'quoteDate' => now(),
        ])->setPaper('a4')->download('quotation-preview-'.now()->format('Ymd-His').'.pdf');
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->decodePayloadFromRequest($request);

        if (!is_array($payload)) {
            return back()
                ->withErrors(['payload' => 'Invalid quotation payload. Please try again.']);
        }

        try {
            $quoteData = $this->buildQuotationData($payload);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        Quotation::query()->create([
            'created_by' => $request->user()?->id,
            ...$quoteData,
        ]);

        return redirect()
            ->route('admin.pc-builder')
            ->with('status', 'Quotation saved successfully.');
    }

    private function decodePayloadFromRequest(Request $request): ?array
    {
        $payloadRaw = (string) $request->input('payload', '');
        $payload = json_decode($payloadRaw, true);

        return is_array($payload) ? $payload : null;
    }

    private function buildQuotationData(array $payload): array
    {
        $validated = validator($payload, [
            'quotation_name' => ['nullable', 'string', 'max:255'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_contact' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'labor_fee' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'in:amount,percent'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.section' => ['nullable', 'string', 'max:100'],
            'items.*.section_key' => ['nullable', 'string', 'max:100'],
        ], [
            'items.min' => 'Select at least one item first.',
            'customer_name.required' => 'Customer name is required.',
        ])->validate();

        $productIds = collect($validated['items'])
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->get(['id', 'name', 'price'])
            ->keyBy('id');

        $items = [];
        $subtotal = 0.0;

        foreach ($validated['items'] as $item) {
            $productId = (int) $item['product_id'];
            $qty = max(1, (int) $item['qty']);
            $product = $products->get($productId);

            if (!$product) {
                continue;
            }

            $unitPrice = (float) $product->price;
            $lineTotal = $unitPrice * $qty;
            $subtotal += $lineTotal;

            $items[] = [
                'section_key' => (string) ($item['section_key'] ?? ''),
                'section' => (string) ($item['section'] ?? ''),
                'product_id' => $productId,
                'product_name' => $product->name,
                'qty' => $qty,
                'unit_price' => round($unitPrice, 2),
                'line_total' => round($lineTotal, 2),
            ];
        }

        if (count($items) === 0) {
            throw ValidationException::withMessages([
                'payload' => 'Select at least one valid item first.',
            ]);
        }

        $laborFee = max(0, (float) ($validated['labor_fee'] ?? 0));
        $discountType = ($validated['discount_type'] ?? 'amount') === 'percent' ? 'percent' : 'amount';
        $discount = max(0, (float) ($validated['discount'] ?? 0));
        $baseAmount = $subtotal + $laborFee;

        $discountAmount = $discountType === 'percent'
            ? $baseAmount * (min(100, max(0, $discount)) / 100)
            : min($baseAmount, $discount);

        $grandTotal = max(0, $baseAmount - $discountAmount);

        return [
            'quotation_name' => $validated['quotation_name'] ?? null,
            'customer_name' => $validated['customer_name'] ?? null,
            'customer_contact' => $validated['customer_contact'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'subtotal' => round($subtotal, 2),
            'labor_fee' => round($laborFee, 2),
            'discount_type' => $discountType,
            'discount' => round($discount, 2),
            'discount_amount' => round($discountAmount, 2),
            'grand_total' => round($grandTotal, 2),
            'items' => $items,
        ];
    }
}
