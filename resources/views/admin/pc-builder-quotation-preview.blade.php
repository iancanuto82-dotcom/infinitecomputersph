<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">{{ $quotation->quotation_name ?: 'Quotation #'.$quotation->id }}</h2>
                <p class="mt-1 text-sm text-gray-600">
                    #{{ $quotation->id }} &middot; {{ $quotation->created_at?->format('M d, Y h:i A') ?? '-' }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 no-print">
                <a href="{{ route('admin.pc-builder.history') }}"
                    class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                    Back to History
                </a>

                @if ($quotation->sale_id)
                    <a href="{{ route('admin.sales') }}"
                        class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-600/90 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">
                        Added to Sales
                    </a>
                @else
                    <form method="POST" action="{{ route('admin.pc-builder.quotations.add-to-sales', $quotation) }}" class="inline">
                        @csrf
                        <input type="hidden" name="deduct_stock" value="1">
                        <button type="submit"
                            class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-600/90 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2"
                            onclick="return confirm('Add this quotation to Sales and deduct stock?')">
                            Add to Sales
                        </button>
                    </form>
                @endif

                <button type="button"
                    class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
                    onclick="openQuotationPrintPreview()">
                    Preview
                </button>
            </div>
        </div>
    </x-slot>

    <style>
        .print-only {
            display: none;
        }

        @media print {
            @page {
                margin: 24px;
            }

            nav,
            header,
            .no-print {
                display: none !important;
            }

            .screen-only {
                display: none !important;
            }

            .print-only {
                display: block !important;
            }

            body {
                background: #fff !important;
            }

            .print-doc {
                font-size: 12px;
                color: #111827;
            }

            .print-doc-table th {
                text-align: left;
                background: #f3f4f6 !important;
                border: 1px solid #d1d5db !important;
                padding: 7px 8px !important;
                font-size: 11px !important;
            }

            .print-doc-table td {
                border: 1px solid #e5e7eb !important;
                padding: 7px 8px !important;
                font-size: 11px !important;
            }

            .print-doc-note {
                border: 1px solid #d1d5db !important;
                padding: 8px !important;
                min-height: 38px !important;
            }

            .print-doc-grand {
                border-top: 1px solid #111827 !important;
                font-weight: 700 !important;
            }
        }
    </style>

    <div class="py-8 screen-only">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-black/10">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <div class="text-gray-600">Quotation name</div>
                        <div class="font-medium text-gray-900">{{ $quotation->quotation_name ?: '-' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-600">Customer</div>
                        <div class="font-medium text-gray-900">{{ $quotation->customer_name ?: 'Walk-in Customer' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-600">Contact</div>
                        <div class="font-medium text-gray-900">{{ $quotation->customer_contact ?: '-' }}</div>
                    </div>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs font-semibold text-gray-600 bg-gray-50">
                            <tr>
                                <th class="px-4 py-2">Item</th>
                                <th class="px-4 py-2">Section</th>
                                <th class="px-4 py-2 text-right">Qty</th>
                                <th class="px-4 py-2 text-right">Unit</th>
                                <th class="px-4 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/10">
                            @forelse (($quotation->items ?? []) as $item)
                                <tr>
                                    <td class="px-4 py-2 font-medium text-gray-900">{{ $item['product_name'] ?? '-' }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $item['section'] ?? '-' }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums">{{ $item['qty'] ?? 0 }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums">&#8369;{{ number_format((float) ($item['unit_price'] ?? 0), 2) }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums font-medium">&#8369;{{ number_format((float) ($item['line_total'] ?? 0), 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-3 text-gray-600" colspan="5">No items recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-lg bg-gray-50 px-4 py-3 ring-1 ring-inset ring-black/10">
                        <div class="text-sm text-gray-600">Notes</div>
                        <div class="mt-1 whitespace-pre-line text-sm text-gray-900">{{ $quotation->notes ?: '-' }}</div>
                    </div>

                    <div class="rounded-lg bg-white px-4 py-3 ring-1 ring-inset ring-black/10">
                        <div class="flex items-center justify-between text-sm text-gray-700">
                            <span>Subtotal</span>
                            <span class="tabular-nums">&#8369;{{ number_format((float) $quotation->subtotal, 2) }}</span>
                        </div>
                        <div class="mt-1 flex items-center justify-between text-sm text-gray-700">
                            <span>Labor / Service fee</span>
                            <span class="tabular-nums">&#8369;{{ number_format((float) $quotation->labor_fee, 2) }}</span>
                        </div>
                        <div class="mt-1 flex items-center justify-between text-sm text-gray-700">
                            <span>Discount</span>
                            <span class="tabular-nums">-&#8369;{{ number_format((float) $quotation->discount_amount, 2) }}</span>
                        </div>
                        <div class="mt-3 pt-3 border-t border-black/10 flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-900">Grand total</span>
                            <span class="text-lg font-semibold text-gray-900 tabular-nums">&#8369;{{ number_format((float) $quotation->grand_total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="print-only print-doc">
        <div class="mb-3">
            <div class="text-[30px] font-bold leading-none text-gray-900">{{ $quotation->quotation_name ?: 'Quotation #'.$quotation->id }}</div>
            <div class="mt-1 text-[16px] text-gray-900/80">Date: {{ $quotation->created_at?->format('M d, Y h:i A') ?? '-' }}</div>
        </div>

        <div class="mb-2 flex items-start justify-between gap-2 text-[18px]">
            <div><span class="font-semibold">Customer:</span> {{ $quotation->customer_name ?: 'Walk-in Customer' }}</div>
            <div class="text-right"><span class="font-semibold">Contact:</span> {{ $quotation->customer_contact ?: '-' }}</div>
        </div>

        <table class="w-full border-collapse print-doc-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Section</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Unit</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($quotation->items ?? []) as $item)
                    <tr>
                        <td>{{ $item['product_name'] ?? '-' }}</td>
                        <td>{{ $item['section'] ?? '-' }}</td>
                        <td class="text-right">{{ $item['qty'] ?? 0 }}</td>
                        <td class="text-right">&#8369;{{ number_format((float) ($item['unit_price'] ?? 0), 2) }}</td>
                        <td class="text-right">&#8369;{{ number_format((float) ($item['line_total'] ?? 0), 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No items recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3 print-doc-note">
            <div class="font-semibold">Notes:</div>
            <div class="whitespace-pre-line">{{ $quotation->notes ?: '-' }}</div>
        </div>

        <div class="mt-3 ms-auto w-[320px]">
            <div class="flex items-center justify-between text-[16px]">
                <div>Subtotal</div>
                <div>&#8369;{{ number_format((float) $quotation->subtotal, 2) }}</div>
            </div>
            <div class="mt-1 flex items-center justify-between text-[16px]">
                <div>Labor / Service fee</div>
                <div>&#8369;{{ number_format((float) $quotation->labor_fee, 2) }}</div>
            </div>
            <div class="mt-1 flex items-center justify-between text-[16px]">
                <div>Discount</div>
                <div>-&#8369;{{ number_format((float) $quotation->discount_amount, 2) }}</div>
            </div>
            <div class="mt-2 pt-2 print-doc-grand flex items-center justify-between text-[20px]">
                <div>Grand total</div>
                <div>&#8369;{{ number_format((float) $quotation->grand_total, 2) }}</div>
            </div>
        </div>
    </div>

    <div id="quotation-print-preview" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-700/40 p-4 no-print">
        <div class="w-full max-w-5xl rounded-xl bg-white p-5 shadow-xl ring-1 ring-black/10 max-h-[90vh] overflow-y-auto">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h4 class="text-base font-semibold text-gray-900">Quotation Preview</h4>
                    <p class="mt-1 text-sm text-gray-600">This is how your quotation will look in print/PDF.</p>
                </div>
                <button type="button" class="text-sm text-gray-600 hover:text-gray-900" onclick="closeQuotationPrintPreview()">Close</button>
            </div>

            <div class="mt-4 rounded-xl bg-white ring-1 ring-black/10 overflow-hidden">
                <div class="px-5 py-3 border-b border-black/10">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-gray-900">{{ $quotation->quotation_name ?: 'Quotation #'.$quotation->id }}</div>
                            <div class="mt-1 text-xs text-gray-600">Customer: {{ $quotation->customer_name ?: 'Walk-in Customer' }}</div>
                            <div class="text-xs text-gray-600">Contact: {{ $quotation->customer_contact ?: '-' }}</div>
                        </div>
                        <div class="text-right text-xs text-gray-600">
                            <div>Date</div>
                            <div>{{ $quotation->created_at?->format('M d, Y h:i A') ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs font-semibold text-gray-600 bg-gray-50/90">
                        <tr>
                            <th class="px-5 py-2">Item</th>
                            <th class="px-5 py-2">Section</th>
                            <th class="px-5 py-2 text-right">Qty</th>
                            <th class="px-5 py-2 text-right">Unit</th>
                            <th class="px-5 py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/10">
                        @forelse (($quotation->items ?? []) as $item)
                            <tr>
                                <td class="px-5 py-2 font-medium text-gray-900">{{ $item['product_name'] ?? '-' }}</td>
                                <td class="px-5 py-2 text-gray-700">{{ $item['section'] ?? '-' }}</td>
                                <td class="px-5 py-2 text-right tabular-nums">{{ $item['qty'] ?? 0 }}</td>
                                <td class="px-5 py-2 text-right tabular-nums">&#8369;{{ number_format((float) ($item['unit_price'] ?? 0), 2) }}</td>
                                <td class="px-5 py-2 text-right tabular-nums">&#8369;{{ number_format((float) ($item['line_total'] ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-5 py-3 text-gray-600" colspan="5">No items recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-black/10">
                    <div class="rounded-lg bg-gray-50 px-4 py-3 ring-1 ring-inset ring-black/10">
                        <div class="text-xs font-medium text-gray-900">Notes</div>
                        <div class="mt-1 whitespace-pre-line text-xs text-gray-700">{{ $quotation->notes ?: '-' }}</div>
                    </div>
                    <div class="rounded-lg bg-white px-4 py-3 ring-1 ring-inset ring-black/10">
                        <div class="flex items-center justify-between text-sm text-gray-700">
                            <span>Subtotal</span>
                            <span class="tabular-nums">&#8369;{{ number_format((float) $quotation->subtotal, 2) }}</span>
                        </div>
                        <div class="mt-1 flex items-center justify-between text-sm text-gray-700">
                            <span>Labor / Service fee</span>
                            <span class="tabular-nums">&#8369;{{ number_format((float) $quotation->labor_fee, 2) }}</span>
                        </div>
                        <div class="mt-1 flex items-center justify-between text-sm text-gray-700">
                            <span>Discount</span>
                            <span class="tabular-nums">-&#8369;{{ number_format((float) $quotation->discount_amount, 2) }}</span>
                        </div>
                        <div class="mt-3 pt-3 border-t border-black/10 flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-900">Grand total</span>
                            <span class="text-base font-semibold text-gray-900 tabular-nums">&#8369;{{ number_format((float) $quotation->grand_total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3 text-xs text-gray-600">
                Download PDF will generate and download a PDF file.
            </div>

            <div class="mt-4 flex justify-end gap-2">
                <button type="button"
                    class="inline-flex items-center rounded-md bg-white px-3 py-2 text-xs font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
                    onclick="closeQuotationPrintPreview()">
                    Back
                </button>
                <button type="button"
                    class="inline-flex items-center rounded-md bg-white px-3 py-2 text-xs font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
                    onclick="printQuotationFromPreview()">
                    Print
                </button>
                <a href="{{ route('admin.pc-builder.quotations.download-pdf', $quotation) }}"
                    class="inline-flex items-center rounded-md bg-gray-900 px-3 py-2 text-xs font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                    Download PDF
                </a>
            </div>
        </div>
    </div>

    <script>
        const quotationPrintPreview = document.getElementById('quotation-print-preview');

        function openQuotationPrintPreview() {
            if (!quotationPrintPreview) return;
            quotationPrintPreview.classList.remove('hidden');
            quotationPrintPreview.classList.add('flex');
        }

        function closeQuotationPrintPreview() {
            if (!quotationPrintPreview) return;
            quotationPrintPreview.classList.add('hidden');
            quotationPrintPreview.classList.remove('flex');
        }

        function printQuotationFromPreview() {
            closeQuotationPrintPreview();
            window.print();
        }

    </script>
</x-app-layout>
