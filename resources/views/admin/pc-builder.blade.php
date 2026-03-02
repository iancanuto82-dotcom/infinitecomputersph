<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">PC Builder</h2>
                <p class="mt-1 text-sm text-gray-600">Build a quick quotation from your active products.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 no-print">
                <a href="{{ route(\App\Support\AdminAccess::preferredAdminRouteName(auth()->user()) ?? 'home') }}"
                    class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                    Back to dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <style>
        [x-cloak] {
            display: none !important;
        }

        .print-only {
            display: none;
        }

        .print-only-inline {
            display: none;
        }

        .pc-builder-scroll {
            max-height: 520px;
            overflow-y: auto;
            overflow-x: auto;
            overscroll-behavior: contain;
            scrollbar-gutter: stable;
        }

        @media (max-width: 640px) {
            .pc-builder-scroll {
                max-height: 420px;
            }
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

            .pc-builder-scroll {
                max-height: none !important;
                overflow: visible !important;
            }

            .print-hide-empty {
                display: none !important;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            .print-only {
                display: block !important;
            }

            .print-only-inline {
                display: inline !important;
            }

            body {
                background: #fff !important;
            }

            .print-area {
                padding: 0 !important;
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

    <div class="py-8" x-data="pcBuilder(@js($products), @js($categories), @js($copiedQuotation))">
        <div class="max-w-[92rem] mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 gap-4">
                <div class="rounded-xl bg-white shadow-sm ring-1 ring-black/10 print-area">
                    <div class="px-6 py-5 border-b border-black/10">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">Quotation</h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    Add items, set fees/discount, then print.
                                </p>
                            </div>
                            <div class="flex flex-col items-end gap-2 no-print">
                                <div class="text-right text-xs text-gray-600">
                                    <div class="font-medium text-gray-900">Date</div>
                                    <div>{{ now()->format('M d, Y') }}</div>
                                </div>
                                <a href="{{ route('admin.pc-builder.history') }}" target="_blank" rel="noopener"
                                    class="inline-flex items-center rounded-md bg-white px-3 py-2 text-xs font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                                    Quotation History
                                </a>
                            </div>
                            <div class="hidden print:block text-right text-xs text-gray-600">
                                <div class="font-medium text-gray-900">Date</div>
                                <div>{{ now()->format('M d, Y') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-5">
                        @if ($copiedFromQuotationId)
                            <div class="rounded-lg bg-blue-50 px-4 py-3 text-sm font-medium text-blue-800 ring-1 ring-inset ring-blue-200 no-print">
                                Loaded from quotation #{{ $copiedFromQuotationId }}. Update customer/components, then save as a new quotation.
                            </div>
                        @endif

                        @if ($errors->has('payload'))
                            <div class="rounded-lg bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 ring-1 ring-inset ring-rose-200 no-print">
                                {{ $errors->first('payload') }}
                            </div>
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 no-print">
                            <div>
                                <label class="block text-sm font-medium text-gray-900" for="quotation_name">Quotation name</label>
                                <input id="quotation_name" type="text" x-model.trim="quotationName"
                                    placeholder="Optional title"
                                    class="mt-1 block w-full rounded-md border-black/20 bg-white text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-900" for="customer_name">Customer name <span class="text-rose-700">*</span></label>
                                <input id="customer_name" type="text" x-model.trim="customerName"
                                    placeholder="Required"
                                    class="mt-1 block w-full rounded-md border-black/20 bg-white text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-900" for="customer_contact">Contact</label>
                                <input id="customer_contact" type="text" x-model.trim="customerContact"
                                    placeholder="Phone / Email (optional)"
                                    class="mt-1 block w-full rounded-md border-black/20 bg-white text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            </div>
                        </div>

                        <div class="rounded-xl bg-white shadow-sm ring-1 ring-black/10 no-print">
                            <div class="pc-builder-scroll relative">
                                <table class="min-w-full text-sm">
                                    <thead class="sticky top-0 z-10 text-left text-xs font-semibold text-gray-600 bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 w-[52%]">Item</th>
                                            <th class="px-6 py-3 text-right border-l border-black/10">Unit</th>
                                            <th class="px-6 py-3 text-right border-l border-black/10">Qty</th>
                                            <th class="px-6 py-3 text-right border-l border-black/10">Total</th>
                                            <th class="px-6 py-3 text-right no-print border-l border-black/10">Action</th>
                                        </tr>
                                    </thead>
                                    <template x-for="section in componentSections" :key="section.key">
                                        <tbody class="border-b border-black/10">
                                            <tr class="bg-gray-50" :class="{'print-hide-empty': !sectionHasSelection(section)}">
                                                <td class="px-6 py-4" colspan="5">
                                                    <div class="flex items-center justify-between gap-4">
                                                        <div class="flex items-center gap-2 min-w-0">
                                                            <div class="h-5 w-5 rounded-full bg-white ring-1 ring-inset ring-black/10"></div>
                                                            <div class="truncate text-sm font-semibold text-gray-900 uppercase tracking-wide" x-text="section.label"></div>
                                                        </div>
                                                        <div class="text-xs text-gray-900/50" x-show="section.hint" x-text="section.hint"></div>
                                                    </div>
                                                </td>
                                            </tr>

                                            <template x-if="!section.multi">
                                                <tr class="hover:bg-gray-50/90" :class="{'print-hide-empty': !single[section.key].product_id}">
                                                    <td class="px-6 py-4">
                                                        <div class="no-print">
                                                            <label class="sr-only" :for="`single_${section.key}`" x-text="`Select ${section.label}`"></label>
                                                            <select class="block w-full rounded-md border-black/20 bg-white text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                                                :id="`single_${section.key}`"
                                                                x-model="single[section.key].product_id"
                                                                @change="onSingleChange(section.key)">
                                                                <option value="" x-text="`Select ${section.label.toUpperCase()}...`"></option>
                                                                <template x-if="sectionUsesSubcategories(section.key)">
                                                                    <template x-for="group in groupedOptionsForWithSelected(section.key, single[section.key].product_id)" :key="`${section.key}_${group.label}`">
                                                                        <optgroup :label="group.label">
                                                                            <template x-for="product in group.products" :key="product.id">
                                                                                <option :value="String(product.id)"
                                                                                    :selected="String(product.id) === String(single[section.key].product_id)"
                                                                                    x-text="optionLabel(product)"></option>
                                                                            </template>
                                                                        </optgroup>
                                                                    </template>
                                                                </template>
                                                                <template x-if="!sectionUsesSubcategories(section.key)">
                                                                    <template x-for="product in optionsForWithSelected(section.key, single[section.key].product_id)" :key="`${section.key}_${product.id}`">
                                                                        <option :value="String(product.id)"
                                                                            :selected="String(product.id) === String(single[section.key].product_id)"
                                                                            x-text="optionLabel(product)"></option>
                                                                    </template>
                                                                </template>
                                                            </select>
                                                            <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-600"
                                                                x-show="single[section.key].product_id">
                                                                    <span x-text="productCategoryName(single[section.key].product_id)"></span>
                                                                    <span>&bull;</span>
                                                                    <span class="tabular-nums">
                                                                    Stock: <span class="font-medium" :class="stockToneClass(productStock(single[section.key].product_id))" x-text="productStock(single[section.key].product_id) ?? '-'"></span>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        <div class="print-only text-sm font-medium text-gray-900" x-text="productName(single[section.key].product_id)"></div>
                                                    </td>
                                                    <td class="px-6 py-4 text-right border-l border-black/10">
                                                        <span class="font-semibold tabular-nums text-emerald-700">
                                                            &#8369;<span x-text="formatMoney(unitPrice(single[section.key].product_id))"></span>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 text-right border-l border-black/10">
                                                        <input type="number" min="0" step="1" inputmode="numeric"
                                                            class="w-20 rounded-md border-black/20 bg-white text-right text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 no-print"
                                                            x-model.number="single[section.key].qty"
                                                            @input="normalizeSingleQty(section.key)">
                                                        <span class="tabular-nums print-only-inline" x-text="single[section.key].product_id ? single[section.key].qty : 0"></span>
                                                    </td>
                                                    <td class="px-6 py-4 text-right font-semibold tabular-nums text-gray-900 border-l border-black/10">
                                                        &#8369;<span x-text="formatMoney(singleSubtotal(section.key))"></span>
                                                    </td>
                                                    <td class="px-6 py-4 text-right whitespace-nowrap no-print border-l border-black/10">
                                                        <button type="button"
                                                            class="text-sm font-medium text-rose-700 hover:text-rose-600 disabled:text-gray-900/30"
                                                            @click="clearSingle(section.key)"
                                                            :disabled="!single[section.key].product_id">
                                                            Clear
                                                        </button>
                                                    </td>
                                                </tr>
                                            </template>

                                                <template x-if="section.multi">
                                                    <template x-for="row in rows[section.key]" :key="row.uid">
                                                    <tr class="hover:bg-gray-50/90" :class="{'print-hide-empty': !row.product_id}">
                                                            <td class="px-6 py-4">
                                                                <div class="no-print">
                                                                    <label class="sr-only" :for="`multi_${section.key}_${row.uid}`" x-text="`Select ${section.label}`"></label>
                                                                    <select class="block w-full rounded-md border-black/20 bg-white text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                                                        :id="`multi_${section.key}_${row.uid}`"
                                                                        x-model="row.product_id"
                                                                        @change="onRowChange(section.key, row.uid)">
                                                                        <option value="" x-text="`Select ${section.label.toUpperCase()}...`"></option>
                                                                        <template x-if="sectionUsesSubcategories(section.key)">
                                                                            <template x-for="group in groupedOptionsForWithSelected(section.key, row.product_id)" :key="`${section.key}_${row.uid}_${group.label}`">
                                                                                <optgroup :label="group.label">
                                                                                    <template x-for="product in group.products" :key="product.id">
                                                                                        <option :value="String(product.id)"
                                                                                            :selected="String(product.id) === String(row.product_id)"
                                                                                            x-text="optionLabel(product)"></option>
                                                                                    </template>
                                                                                </optgroup>
                                                                            </template>
                                                                        </template>
                                                                        <template x-if="!sectionUsesSubcategories(section.key)">
                                                                            <template x-for="product in optionsForWithSelected(section.key, row.product_id)" :key="`${section.key}_${row.uid}_${product.id}`">
                                                                                <option :value="String(product.id)"
                                                                                    :selected="String(product.id) === String(row.product_id)"
                                                                                    x-text="optionLabel(product)"></option>
                                                                            </template>
                                                                        </template>
                                                                </select>
                                                                <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-600"
                                                                    x-show="row.product_id">
                                                                    <span x-text="productCategoryName(row.product_id)"></span>
                                                                    <span>&bull;</span>
                                                                    <span class="tabular-nums">
                                                                        Stock: <span class="font-medium" :class="stockToneClass(productStock(row.product_id))" x-text="productStock(row.product_id) ?? '-'"></span>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <div class="print-only text-sm font-medium text-gray-900" x-text="productName(row.product_id)"></div>
                                                        </td>
                                                        <td class="px-6 py-4 text-right border-l border-black/10">
                                                            <span class="font-semibold tabular-nums text-emerald-700">
                                                                &#8369;<span x-text="formatMoney(unitPrice(row.product_id))"></span>
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4 text-right border-l border-black/10">
                                                            <input type="number" min="0" step="1" inputmode="numeric"
                                                                class="w-20 rounded-md border-black/20 bg-white text-right text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 no-print"
                                                                x-model.number="row.qty"
                                                                @input="normalizeRowQty(section.key, row.uid)">
                                                            <span class="tabular-nums print-only-inline" x-text="row.product_id ? row.qty : 0"></span>
                                                        </td>
                                                        <td class="px-6 py-4 text-right font-semibold tabular-nums text-gray-900 border-l border-black/10">
                                                            &#8369;<span x-text="formatMoney(rowSubtotal(row))"></span>
                                                        </td>
                                                        <td class="px-6 py-4 text-right whitespace-nowrap no-print border-l border-black/10">
                                                            <button type="button"
                                                                class="text-sm font-medium text-rose-700 hover:text-rose-600 disabled:text-gray-900/30"
                                                                @click="removeRow(section.key, row.uid)"
                                                                :disabled="rows[section.key].length <= 1">
                                                                Remove
                                                            </button>
                                                            <button type="button"
                                                                class="ms-3 text-sm font-medium text-gray-700 hover:text-gray-900 disabled:text-gray-900/30"
                                                                @click="clearRow(section.key, row.uid)"
                                                                :disabled="!row.product_id">
                                                                Clear
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </template>

                                            <template x-if="section.multi">
                                                <tr class="no-print">
                                                    <td class="px-6 py-4" colspan="5">
                                                        <div class="flex justify-end">
                                                            <button type="button"
                                                                class="inline-flex items-center rounded-md bg-white px-3 py-2 text-xs font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
                                                                @click="addRow(section.key)">
                                                                Add another
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </template>
                                </table>
                            </div>
                        </div>

                        <div class="print-only print-doc">
                            <div class="mb-3">
                                <div class="text-[30px] font-bold leading-none text-gray-900" x-text="quotationName || 'Quotation'"></div>
                                <div class="mt-1 text-[16px] text-gray-900/80">Date: {{ now()->format('M d, Y h:i A') }}</div>
                            </div>

                            <div class="mb-2 flex items-start justify-between gap-2 text-[18px]">
                                <div><span class="font-semibold">Customer:</span> <span x-text="customerName || 'Walk-in Customer'"></span></div>
                                <div class="text-right"><span class="font-semibold">Contact:</span> <span x-text="customerContact || '-'"></span></div>
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
                                    <template x-if="printItems.length === 0">
                                        <tr>
                                            <td colspan="5" class="text-gray-700">No items selected.</td>
                                        </tr>
                                    </template>
                                    <template x-for="item in printItems" :key="item.key">
                                        <tr>
                                            <td x-text="item.name"></td>
                                            <td x-text="item.section"></td>
                                            <td class="text-right tabular-nums" x-text="item.qty"></td>
                                            <td class="text-right tabular-nums">&#8369;<span x-text="formatMoney(item.unit)"></span></td>
                                            <td class="text-right tabular-nums">&#8369;<span x-text="formatMoney(item.total)"></span></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>

                            <div class="mt-3 print-doc-note">
                                <div class="font-semibold">Notes:</div>
                                <div class="whitespace-pre-line" x-text="notes || '-'"></div>
                            </div>

                            <div class="mt-3 ms-auto w-[320px]">
                                <div class="flex items-center justify-between text-[16px]">
                                    <div>Subtotal</div>
                                    <div class="tabular-nums">&#8369;<span x-text="formatMoney(subtotal)"></span></div>
                                </div>
                                <div class="mt-1 flex items-center justify-between text-[16px]">
                                    <div>Labor / Service fee</div>
                                    <div class="tabular-nums">&#8369;<span x-text="formatMoney(laborFeeSafe)"></span></div>
                                </div>
                                <div class="mt-1 flex items-center justify-between text-[16px]">
                                    <div>Discount</div>
                                    <div class="tabular-nums">-&#8369;<span x-text="formatMoney(discountAmount)"></span></div>
                                </div>
                                <div class="mt-2 pt-2 print-doc-grand flex items-center justify-between text-[20px]">
                                    <div>Grand total</div>
                                    <div class="tabular-nums">&#8369;<span x-text="formatMoney(grandTotal)"></span></div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 no-print">
                            <div class="rounded-xl bg-gray-50 px-4 py-4 ring-1 ring-inset ring-black/10">
                                <div class="text-sm font-medium text-gray-900">Notes</div>
                                <textarea rows="4" x-model.trim="notes"
                                    class="mt-2 block w-full rounded-md border-black/20 bg-white text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                    placeholder="Optional terms, warranty, inclusions..."></textarea>
                            </div>

                            <div class="rounded-xl bg-white px-4 py-4 ring-1 ring-inset ring-black/10 space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-900" for="labor_fee">Labor / Service fee</label>
                                    <div class="mt-1 flex items-center gap-2">
                                        <span class="text-sm text-gray-600">&#8369;</span>
                                        <input id="labor_fee" type="number" step="0.01" min="0" x-model.number="laborFee"
                                            class="block w-full rounded-md border-black/20 bg-white text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-900" for="discount">Discount</label>
                                    <div class="mt-1 grid grid-cols-3 gap-2">
                                        <select x-model="discountType"
                                            class="col-span-1 rounded-md border-black/20 bg-white text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                            <option value="amount">&#8369;</option>
                                            <option value="percent">%</option>
                                        </select>
                                        <input id="discount" type="number" step="0.01" min="0" x-model.number="discount"
                                            class="col-span-2 block w-full rounded-md border-black/20 bg-white text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl bg-gray-50 px-5 py-4 ring-1 ring-inset ring-black/10 quote-totals no-print">
                            <div class="flex items-center justify-between text-sm text-gray-700">
                                <div>Subtotal</div>
                                <div class="tabular-nums">&#8369;<span x-text="formatMoney(subtotal)"></span></div>
                            </div>
                            <div class="mt-1 flex items-center justify-between text-sm text-gray-700">
                                <div>Labor / Service fee</div>
                                <div class="tabular-nums">&#8369;<span x-text="formatMoney(laborFeeSafe)"></span></div>
                            </div>
                            <div class="mt-1 flex items-center justify-between text-sm text-gray-700">
                                <div>Discount</div>
                                <div class="tabular-nums">-&#8369;<span x-text="formatMoney(discountAmount)"></span></div>
                            </div>
                            <div class="mt-3 pt-3 border-t border-black/10 flex items-center justify-between">
                                <div class="text-sm font-semibold text-gray-900">Grand total</div>
                                <div class="text-lg font-semibold text-gray-900 tabular-nums">
                                    &#8369;<span x-text="formatMoney(grandTotal)"></span>
                                </div>
                            </div>
                        </div>

                        <div class="no-print flex justify-end">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                <button type="button"
                                    class="inline-flex items-center rounded-md bg-gray-900 px-3 py-2 text-xs font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
                                    @click="clearAll()">
                                    Clear
                                </button>
                                <form method="POST" action="{{ route('admin.pc-builder.quotations.store') }}">
                                    @csrf
                                    <input type="hidden" name="payload" :value="savePayload">
                                    <button type="submit"
                                        class="inline-flex items-center rounded-md bg-white px-3 py-2 text-xs font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:text-gray-400"
                                        :disabled="printItems.length === 0 || !String(customerName || '').trim()">
                                        Save Quotation
                                    </button>
                                </form>
                                <button type="button"
                                    class="inline-flex items-center rounded-md bg-white px-3 py-2 text-xs font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
                                    @click="openPrintPreview()">
                                    Preview
                                </button>
                            </div>
                        </div>

                        <div x-show="showPrintPreview" x-cloak
                            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-700/40 p-4 no-print">
                            <div class="w-full max-w-5xl rounded-xl bg-white p-5 shadow-xl ring-1 ring-black/10 max-h-[90vh] overflow-y-auto">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h4 class="text-base font-semibold text-gray-900">Quotation Preview</h4>
                                        <p class="mt-1 text-sm text-gray-600">This is how your quotation will look in print/PDF.</p>
                                    </div>
                                    <button type="button" class="text-sm text-gray-600 hover:text-gray-900" @click="closePrintPreview()">Close</button>
                                </div>

                                <div class="mt-4 rounded-xl bg-white ring-1 ring-black/10 overflow-hidden">
                                    <div class="px-5 py-3 border-b border-black/10">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900">Quotation</div>
                                                <div class="mt-1 text-xs text-gray-600">Name: <span x-text="quotationName || '-'"></span></div>
                                                <div class="mt-1 text-xs text-gray-600">Customer: <span x-text="customerName || '-'"></span></div>
                                                <div class="text-xs text-gray-600">Contact: <span x-text="customerContact || '-'"></span></div>
                                            </div>
                                            <div class="text-right text-xs text-gray-600">
                                                <div>Date</div>
                                                <div>{{ now()->format('M d, Y') }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <table class="min-w-full text-sm">
                                        <thead class="text-left text-xs font-semibold text-gray-600 bg-gray-50">
                                            <tr>
                                                <th class="px-5 py-2">Item</th>
                                                <th class="px-5 py-2 text-right">Qty</th>
                                                <th class="px-5 py-2 text-right">Unit</th>
                                                <th class="px-5 py-2 text-right">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/10">
                                            <template x-if="printItems.length === 0">
                                                <tr>
                                                    <td class="px-5 py-3 text-gray-600" colspan="4">No items selected.</td>
                                                </tr>
                                            </template>
                                            <template x-for="item in printItems" :key="`preview_${item.key}`">
                                                <tr>
                                                    <td class="px-5 py-2">
                                                        <div class="font-medium text-gray-900" x-text="item.name"></div>
                                                        <div class="text-xs text-gray-600" x-text="item.section"></div>
                                                    </td>
                                                    <td class="px-5 py-2 text-right tabular-nums" x-text="item.qty"></td>
                                                    <td class="px-5 py-2 text-right tabular-nums">&#8369;<span x-text="formatMoney(item.unit)"></span></td>
                                                    <td class="px-5 py-2 text-right tabular-nums">&#8369;<span x-text="formatMoney(item.total)"></span></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>

                                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-black/10">
                                        <div class="rounded-lg bg-gray-50 px-4 py-3 ring-1 ring-inset ring-black/10">
                                            <div class="text-xs font-medium text-gray-900">Notes</div>
                                            <div class="mt-1 text-xs text-gray-700 whitespace-pre-line" x-text="notes || '-'"></div>
                                        </div>
                                        <div class="rounded-lg bg-white px-4 py-3 ring-1 ring-inset ring-black/10">
                                            <div class="flex items-center justify-between text-sm text-gray-700">
                                                <span>Subtotal</span>
                                                <span class="tabular-nums">&#8369;<span x-text="formatMoney(subtotal)"></span></span>
                                            </div>
                                            <div class="mt-1 flex items-center justify-between text-sm text-gray-700">
                                                <span>Labor / Service fee</span>
                                                <span class="tabular-nums">&#8369;<span x-text="formatMoney(laborFeeSafe)"></span></span>
                                            </div>
                                            <div class="mt-1 flex items-center justify-between text-sm text-gray-700">
                                                <span>Discount</span>
                                                <span class="tabular-nums">-&#8369;<span x-text="formatMoney(discountAmount)"></span></span>
                                            </div>
                                            <div class="mt-3 pt-3 border-t border-black/10 flex items-center justify-between">
                                                <span class="text-sm font-semibold text-gray-900">Grand total</span>
                                                <span class="text-base font-semibold text-gray-900 tabular-nums">&#8369;<span x-text="formatMoney(grandTotal)"></span></span>
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
                                        @click="closePrintPreview()">
                                        Back
                                    </button>
                                    <button type="button"
                                        class="inline-flex items-center rounded-md bg-white px-3 py-2 text-xs font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
                                        @click="printQuote()">
                                        Print
                                    </button>
                                    <form method="POST" action="{{ route('admin.pc-builder.preview-pdf') }}">
                                        @csrf
                                        <input type="hidden" name="payload" :value="savePayload">
                                        <button type="submit"
                                            class="inline-flex items-center rounded-md bg-gray-900 px-3 py-2 text-xs font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-gray-700/40"
                                            :disabled="printItems.length === 0 || !String(customerName || '').trim()">
                                            Download PDF
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <script>
            (() => {
                const registerPcBuilder = () => {
                    if (!window.Alpine) return;
                    if (window.__pcBuilderRegistered) return;
                    window.__pcBuilderRegistered = true;

                    Alpine.data('pcBuilder', (products, categories, copiedQuotation = null) => ({
                    products: Array.isArray(products) ? products : [],
                    categories: Array.isArray(categories) ? categories : [],
                    copiedQuotation: copiedQuotation && typeof copiedQuotation === 'object' ? copiedQuotation : null,
                    nextUid: 1,

                    componentSections: [
                        { key: 'processor', label: 'Processor', hint: 'Choose 1 CPU.', multi: false },
                        { key: 'motherboard', label: 'Motherboard', hint: 'Choose 1 motherboard.', multi: false },
                        { key: 'cpu_cooler', label: 'CPU Cooler', hint: 'Choose 1 cooler (optional).', multi: false },
                        { key: 'ram', label: 'RAM', hint: 'Choose 1 kit (adjust quantity if needed).', multi: false },
                        { key: 'graphics', label: 'Graphics card', hint: 'Choose 1 GPU.', multi: false },
                        { key: 'storage', label: 'Storage', hint: 'You can add multiple drives.', multi: true },
                        { key: 'fans', label: 'Fans', hint: 'You can add multiple fans.', multi: true },
                        { key: 'case', label: 'Case', hint: 'Choose 1 case.', multi: false },
                        { key: 'powersupply', label: 'Power Supply', hint: 'Choose 1 PSU.', multi: false },
                        { key: 'monitor', label: 'Monitor', hint: 'You can add multiple monitors.', multi: true },
                        { key: 'keyboard', label: 'Keyboard', hint: 'Choose 1 keyboard.', multi: false },
                        { key: 'mouse', label: 'Mouse', hint: 'Choose 1 mouse.', multi: false },
                        { key: 'other', label: 'Other components', hint: 'GPU, WiFi, peripherals, etc.', multi: true },
                    ],

                    single: {
                        processor: { product_id: '', qty: 0 },
                        cpu_cooler: { product_id: '', qty: 0 },
                        motherboard: { product_id: '', qty: 0 },
                        ram: { product_id: '', qty: 0 },
                        graphics: { product_id: '', qty: 0 },
                        case: { product_id: '', qty: 0 },
                        powersupply: { product_id: '', qty: 0 },
                        keyboard: { product_id: '', qty: 0 },
                        mouse: { product_id: '', qty: 0 },
                    },

                    rows: {
                        storage: [],
                        fans: [],
                        monitor: [],
                        other: [],
                    },

                    customerName: '',
                    customerContact: '',
                    quotationName: '',
                    notes: '',

                    laborFee: 0,
                    discountType: 'amount',
                    discount: 0,
                    showPrintPreview: false,

                    categoryName(categoryId) {
                        if (!categoryId) return 'Uncategorized';
                        const category = this.categories.find(c => String(c.id) === String(categoryId));
                        return category ? category.name : 'Uncategorized';
                    },

                    init() {
                        this.applyCopiedQuotation();

                        this.componentSections.filter(s => s.multi).forEach(s => {
                            if (!Array.isArray(this.rows[s.key])) this.rows[s.key] = [];
                            if (this.rows[s.key].length === 0) this.addRow(s.key);
                        });

                        this.enforceProcessorCompatibilitySelections();
                    },

                    applyCopiedQuotation() {
                        const source = this.copiedQuotation;
                        if (!source) return;

                        this.quotationName = String(source.quotation_name || '').trim();
                        this.customerName = String(source.customer_name || '').trim();
                        this.customerContact = String(source.customer_contact || '').trim();
                        this.notes = String(source.notes || '').trim();
                        this.laborFee = Math.max(0, Number(source.labor_fee || 0));
                        this.discountType = source.discount_type === 'percent' ? 'percent' : 'amount';
                        this.discount = Math.max(0, Number(source.discount || 0));

                        Object.keys(this.single).forEach(key => {
                            this.single[key].product_id = '';
                            this.single[key].qty = 0;
                        });

                        Object.keys(this.rows).forEach(key => {
                            this.rows[key] = [];
                        });

                        const items = Array.isArray(source.items) ? source.items : [];

                        items.forEach(item => {
                            const sectionKey = String(item?.section_key || '').trim().toLowerCase();
                            const productId = String(item?.product_id || '').trim();
                            const qty = Math.max(1, parseInt(item?.qty, 10) || 1);

                            if (!productId || !this.productById(productId)) {
                                return;
                            }

                            if (this.single[sectionKey]) {
                                this.single[sectionKey].product_id = productId;
                                this.single[sectionKey].qty = qty;
                                return;
                            }

                            if (Array.isArray(this.rows[sectionKey])) {
                                this.rows[sectionKey].push({
                                    uid: this.nextUid++,
                                    product_id: productId,
                                    qty,
                                });
                                return;
                            }

                            if (Array.isArray(this.rows.other)) {
                                this.rows.other.push({
                                    uid: this.nextUid++,
                                    product_id: productId,
                                    qty,
                                });
                            }
                        });
                    },

                    productById(productId) {
                        if (!productId) return null;
                        return this.products.find(p => String(p.id) === String(productId)) || null;
                    },

                    productName(productId) {
                        const product = this.productById(productId);
                        return product ? product.name : '-';
                    },

                    productStock(productId) {
                        const product = this.productById(productId);
                        if (!product) return null;
                        return Number.isFinite(product.stock) ? product.stock : null;
                    },
                    stockToneClass(stock) {
                        const qty = Number(stock ?? 0);
                        if (!Number.isFinite(qty) || qty <= 0) return 'text-rose-600';
                        if (qty <= 3) return 'text-amber-600';
                        return 'text-emerald-600';
                    },

                    productCategoryName(productId) {
                        const product = this.productById(productId);
                        if (!product) return '-';
                        return this.categoryName(product.category_id);
                    },

                    unitPrice(productId) {
                        const product = this.productById(productId);
                        return product ? Number(product.price || 0) : 0;
                    },

                    optionLabel(product) {
                        if (!product) return '';
                        return product.name;
                    },

                    inferDdrProfile(value) {
                        const normalized = String(value || '').toLowerCase().replace(/\s+/g, '');
                        if (normalized.includes('ddr5')) return 'ddr5';
                        if (normalized.includes('ddr4')) return 'ddr4';
                        if (normalized.includes('ddr3')) return 'ddr3';
                        return '';
                    },

                    inferCpuBrandProfile(value) {
                        const normalized = String(value || '').toLowerCase().replace(/\s+/g, '');
                        if (!normalized) return '';

                        const amdNeedles = ['amd', 'ryzen', 'athlon', 'threadripper', 'am4', 'am5', 'a320', 'a520', 'b350', 'b450', 'b550', 'b650', 'b650e', 'x370', 'x470', 'x570', 'x670', 'x670e'];
                        const intelNeedles = ['intel', 'corei', 'core2', 'pentium', 'celeron', 'xeon', 'lga', 'h310', 'h410', 'h510', 'h610', 'h670', 'b360', 'b365', 'b460', 'b560', 'b660', 'b760', 'z370', 'z390', 'z490', 'z590', 'z690', 'z790'];

                        if (amdNeedles.some((needle) => normalized.includes(needle))) return 'amd';
                        if (intelNeedles.some((needle) => normalized.includes(needle))) return 'intel';
                        return '';
                    },

                    productDdrProfile(product) {
                        if (!product) return '';
                        return this.inferDdrProfile(this.categoryName(product.category_id))
                            || this.inferDdrProfile(product.name || '');
                    },

                    productCpuBrandProfile(product) {
                        if (!product) return '';
                        return this.inferCpuBrandProfile(this.categoryName(product.category_id))
                            || this.inferCpuBrandProfile(product.name || '');
                    },

                    selectedProcessorProduct() {
                        const processorId = this.single?.processor?.product_id || '';
                        return this.productById(processorId);
                    },

                    activeProcessorDdrProfile() {
                        return this.productDdrProfile(this.selectedProcessorProduct());
                    },

                    activeProcessorBrandProfile() {
                        return this.productCpuBrandProfile(this.selectedProcessorProduct());
                    },

                    usesDdrCompatibility(sectionKey) {
                        const key = String(sectionKey || '').toLowerCase();
                        return key === 'motherboard' || key === 'ram';
                    },

                    usesBrandCompatibility(sectionKey) {
                        const key = String(sectionKey || '').toLowerCase();
                        return key === 'motherboard';
                    },

                    isCompatibleProduct(sectionKey, product) {
                        let ddrCompatible = true;
                        if (this.usesDdrCompatibility(sectionKey)) {
                            const processorDdrProfile = this.activeProcessorDdrProfile();
                            if (processorDdrProfile) {
                                ddrCompatible = this.productDdrProfile(product) === processorDdrProfile;
                            }
                        }

                        let brandCompatible = true;
                        if (this.usesBrandCompatibility(sectionKey)) {
                            const processorBrandProfile = this.activeProcessorBrandProfile();
                            if (processorBrandProfile) {
                                brandCompatible = this.productCpuBrandProfile(product) === processorBrandProfile;
                            }
                        }

                        return ddrCompatible && brandCompatible;
                    },

                    hasProcessorCompatibilityProfile() {
                        return Boolean(this.activeProcessorDdrProfile() || this.activeProcessorBrandProfile());
                    },

                    enforceProcessorCompatibilitySelections() {
                        ['motherboard', 'ram'].forEach((sectionKey) => {
                            const entry = this.single?.[sectionKey];
                            if (!entry?.product_id) return;

                            const selectedProduct = this.productById(entry.product_id);
                            if (!selectedProduct) {
                                entry.product_id = '';
                                entry.qty = 0;
                                return;
                            }

                            if (!this.isCompatibleProduct(sectionKey, selectedProduct)) {
                                entry.product_id = '';
                                entry.qty = 0;
                            }
                        });
                    },

                    rowSubtotal(row) {
                        const qty = parseInt(row?.qty, 10) || 0;
                        return Math.max(0, qty) * Math.max(0, this.unitPrice(row?.product_id));
                    },

                    singleSubtotal(key) {
                        const entry = this.single[key];
                        if (!entry?.product_id) return 0;
                        const qty = parseInt(entry.qty, 10) || 0;
                        return Math.max(0, qty) * Math.max(0, this.unitPrice(entry.product_id));
                    },

                    addRow(key) {
                        if (!Array.isArray(this.rows[key])) this.rows[key] = [];
                        this.rows[key].push({ uid: this.nextUid++, product_id: '', qty: 0 });
                    },

                    removeRow(key, uid) {
                        if (!Array.isArray(this.rows[key])) return;
                        if (this.rows[key].length <= 1) return;
                        this.rows[key] = this.rows[key].filter(r => r.uid !== uid);
                    },

                    clearRow(key, uid) {
                        const row = (this.rows[key] || []).find(r => r.uid === uid);
                        if (!row) return;
                        row.product_id = '';
                        row.qty = 0;
                    },

                    onRowChange(key, uid) {
                        const row = (this.rows[key] || []).find(r => r.uid === uid);
                        if (!row) return;
                        if (row.product_id && (!(parseInt(row.qty, 10) > 0))) row.qty = 1;
                    },

                    normalizeRowQty(key, uid) {
                        const row = (this.rows[key] || []).find(r => r.uid === uid);
                        if (!row) return;
                        const nextQty = parseInt(row.qty, 10);
                        if (!Number.isFinite(nextQty)) {
                            row.qty = row.product_id ? 1 : 0;
                            return;
                        }
                        row.qty = row.product_id ? Math.max(1, nextQty) : Math.max(0, nextQty);
                    },

                    onSingleChange(key) {
                        const entry = this.single[key];
                        if (!entry) return;
                        if (entry.product_id && (!(parseInt(entry.qty, 10) > 0))) entry.qty = 1;

                        if (String(key) === 'processor') {
                            this.enforceProcessorCompatibilitySelections();
                        }
                    },

                    normalizeSingleQty(key) {
                        const entry = this.single[key];
                        if (!entry) return;
                        const nextQty = parseInt(entry.qty, 10);
                        if (!Number.isFinite(nextQty)) {
                            entry.qty = entry.product_id ? 1 : 0;
                            return;
                        }
                        entry.qty = entry.product_id ? Math.max(1, nextQty) : Math.max(0, nextQty);
                    },

                    clearSingle(key) {
                        const entry = this.single[key];
                        if (!entry) return;
                        entry.product_id = '';
                        entry.qty = 0;
                    },

                    sectionHasSelection(section) {
                        if (!section) return false;
                        if (!section.multi) {
                            return Boolean(this.single?.[section.key]?.product_id);
                        }

                        const list = this.rows?.[section.key] || [];
                        return list.some(row => Boolean(row.product_id));
                    },

                    sectionLabel(sectionKey) {
                        const section = (this.componentSections || []).find(s => s.key === sectionKey);
                        return section ? section.label : sectionKey;
                    },

                    makePrintItem(sectionKey, productId, qty, rowKey) {
                        const safeQty = parseInt(qty, 10) || 0;
                        if (!productId || safeQty <= 0) return null;

                        const product = this.productById(productId);
                        if (!product) return null;

                        const unit = this.unitPrice(productId);
                        return {
                            key: `${sectionKey}_${rowKey}_${productId}`,
                            section_key: sectionKey,
                            section: this.sectionLabel(sectionKey),
                            product_id: Number(productId),
                            name: product.name,
                            qty: safeQty,
                            unit,
                            total: safeQty * unit,
                        };
                    },

                    get printItems() {
                        const items = [];

                        (this.componentSections || []).forEach(section => {
                            if (section.multi) {
                                const rows = this.rows?.[section.key] || [];
                                rows.forEach((row, index) => {
                                    const item = this.makePrintItem(section.key, row?.product_id, row?.qty, row?.uid ?? index);
                                    if (item) items.push(item);
                                });
                                return;
                            }

                            const entry = this.single?.[section.key];
                            const item = this.makePrintItem(section.key, entry?.product_id, entry?.qty, 'single');
                            if (item) items.push(item);
                        });

                        return items;
                    },

                    get savePayload() {
                        return JSON.stringify({
                            quotation_name: this.quotationName || null,
                            customer_name: this.customerName || null,
                            customer_contact: this.customerContact || null,
                            notes: this.notes || null,
                            labor_fee: this.laborFeeSafe,
                            discount_type: this.discountType === 'percent' ? 'percent' : 'amount',
                            discount: Number(this.discount || 0),
                            items: this.printItems.map(item => ({
                                section_key: item.section_key,
                                section: item.section,
                                product_id: item.product_id,
                                qty: item.qty,
                            })),
                        });
                    },

                    categoryMatchesAny(categoryName, keywords) {
                        const name = String(categoryName || '').toLowerCase();
                        return keywords.some(k => name.includes(k));
                    },

                    nameMatchesAny(product, keywords) {
                        const name = String(product?.name || '').toLowerCase();
                        return keywords.some(k => name.includes(k));
                    },

                    categoryIs(categoryName, keywords) {
                        const name = String(categoryName || '')
                            .trim()
                            .toLowerCase()
                            .replace(/\s+/g, ' ');
                        return keywords.some(k => name === k);
                    },

                    matchesAny(product, keywords) {
                        if (!keywords || keywords.length === 0) return true;
                        const categoryName = this.categoryName(product?.category_id);
                        return this.categoryMatchesAny(categoryName, keywords) || this.nameMatchesAny(product, keywords);
                    },

                    matchesNone(product, keywords) {
                        if (!keywords || keywords.length === 0) return true;
                        const categoryName = this.categoryName(product?.category_id);
                        const name = String(product?.name || '').toLowerCase();
                        return !keywords.some(k => categoryName.toLowerCase().includes(k) || name.includes(k));
                    },

                    sortedProducts(list) {
                        const copy = Array.isArray(list) ? list.slice() : [];
                        copy.sort((a, b) => String(a?.name || '').localeCompare(String(b?.name || '')));
                        return copy;
                    },

                    sectionUsesSubcategories(key) {
                        const normalized = String(key || '').toLowerCase();
                        return normalized === 'processor' || normalized === 'motherboard' || normalized === 'ram';
                    },

                    categoryIdsForSection(key) {
                        const normalized = String(key || '').toLowerCase();
                        const needlesBySection = {
                            processor: ['processor', 'cpu'],
                            motherboard: ['motherboard', 'mobo', 'mainboard'],
                            ram: ['ram', 'memory', 'desktop ram'],
                        };

                        const needles = needlesBySection[normalized] || [];
                        if (!needles.length) return [];

                        const categories = Array.isArray(this.categories) ? this.categories : [];
                        const mainCategoryIds = categories
                            .filter((category) => {
                                const parentId = category?.parent_id ?? null;
                                if (parentId !== null && String(parentId) !== '') return false;
                                const name = String(category?.name || '').toLowerCase();
                                return needles.some((needle) => name.includes(String(needle).toLowerCase()));
                            })
                            .map((category) => Number(category?.id || 0))
                            .filter((id) => Number.isFinite(id) && id > 0);

                        if (!mainCategoryIds.length) {
                            return categories
                                .filter((category) => {
                                    const name = String(category?.name || '').toLowerCase();
                                    return needles.some((needle) => name.includes(String(needle).toLowerCase()));
                                })
                                .map((category) => Number(category?.id || 0))
                                .filter((id) => Number.isFinite(id) && id > 0);
                        }

                        const allIds = new Set(mainCategoryIds);
                        let added = true;
                        while (added) {
                            added = false;
                            categories.forEach((category) => {
                                const id = Number(category?.id || 0);
                                const parentId = Number(category?.parent_id || 0);
                                if (id > 0 && parentId > 0 && allIds.has(parentId) && !allIds.has(id)) {
                                    allIds.add(id);
                                    added = true;
                                }
                            });
                        }

                        return Array.from(allIds.values());
                    },

                    optionsFor(key) {
                        const includeByKey = {
                            processor: ['processor', 'cpu', 'ryzen', 'intel', 'athlon', 'pentium', 'celeron', 'core i', 'xeon'],
                            cpu_cooler: ['cooler', 'aio', 'heatsink', 'liquid', 'radiator'],
                            motherboard: ['motherboard', 'mobo'],
                            ram: ['ram', 'memory', 'ddr'],
                            graphics: ['graphics', 'gpu', 'video card', 'vga'],
                            storage: ['storage', 'ssd', 'hdd', 'nvme', 'm.2'],
                            fans: ['fan'],
                            case: ['case', 'chassis'],
                            powersupply: ['power supply', 'psu', 'powersupply'],
                            monitor: ['monitor'],
                            keyboard: ['keyboard', 'keyb'],
                            mouse: ['mouse'],
                        };
                        const excludeByKey = {
                            processor: ['cooler', 'aio', 'heatsink', 'liquid', 'fan', 'radiator', 'motherboard', 'mobo', 'chipset', 'socket', 'lga', 'am4', 'am5', 'h610', 'h510', 'h410', 'h310', 'b450', 'b550', 'a320', 'a520', 'x570', 'z690', 'z790'],
                            cpu_cooler: ['memory', 'ram', 'ddr', 'cable', 'extension', 'adapter', 'converter', 'laptop', 'notebook'],
                            ram: ['case', 'chassis', 'motherboard', 'mobo', 'psu', 'power supply', 'gpu', 'graphics', 'ssd', 'hdd', 'nvme'],
                            storage: ['caddy', 'enclosure', 'adapter', 'converter', 'cable', 'dock', 'case', 'tray'],
                            fans: ['case w/', 'case with', 'chassis', 'keyboard', 'mouse', 'splitter', 'hub', 'controller', 'psu', 'power supply', 'kit'],
                            case: ['fan', 'fans', 'psu', 'power supply', 'keyboard', 'mouse'],
                            powersupply: ['cable', 'extension', 'connector', 'socket', 'surge', 'switch', 'poe', 'printer', 'case', 'chassis', 'case w/', 'case with', 'w/ psu', 'w/psu', 'with psu'],
                            monitor: ['adapter', 'converter', 'cable', 'hdmi', 'displayport', 'dp ', 'dvi', 'vga', 'extender', 'splitter'],
                            graphics: ['adapter', 'converter', 'cable', 'hdmi', 'displayport', 'dp ', 'dvi', 'vga', 'extender', 'splitter'],
                        };

                        if (key === 'other') {
                            return this.sortedProducts(this.products);
                        }

                        const include = includeByKey[key] || [];
                        const exclude = excludeByKey[key] || [];
                        const sectionCategoryIds = this.categoryIdsForSection(key);
                        const categoryExact = {
                            cpu_cooler: ['cpu cooler', 'cpu coolers', 'cooler'],
                            graphics: ['graphics card', 'graphics cards', 'gpu', 'vga'],
                            processor: ['processor', 'processors', 'cpu'],
                            ram: ['ram', 'memory'],
                            storage: ['storage', 'storage device', 'storage devices', 'ssd', 'hdd', 'nvme'],
                            fans: ['fans', 'fan'],
                            case: ['case', 'chassis'],
                            powersupply: ['power supply', 'power supplies', 'psu'],
                            monitor: ['monitor', 'monitors', 'display monitor'],
                        };

                        const filtered = this.products.filter(p => {
                            if (this.sectionUsesSubcategories(key) && sectionCategoryIds.length > 0) {
                                const productCategoryId = Number(p?.category_id || 0);
                                if (!sectionCategoryIds.includes(productCategoryId)) {
                                    return false;
                                }
                            }

                            const categoryName = this.categoryName(p?.category_id);
                            const exacts = categoryExact[key] || [];
                            const exactMatch = exacts.length ? this.categoryIs(categoryName, exacts) : false;
                            const scopedBySubcategory = this.sectionUsesSubcategories(key) && sectionCategoryIds.length > 0;

                            // Processor matching must not rely on broad category markers like "intel"
                            // because motherboard categories can include those tokens.
                            if (key === 'processor') {
                                const processorCategoryInclude = ['processor', 'cpu'];
                                const processorNameInclude = ['processor', 'cpu', 'ryzen', 'intel', 'athlon', 'pentium', 'celeron', 'core i', 'xeon'];
                                const includeMatch = this.categoryMatchesAny(categoryName, processorCategoryInclude)
                                    || this.nameMatchesAny(p, processorNameInclude)
                                    || exactMatch;

                                return includeMatch && this.matchesNone(p, exclude) && this.isCompatibleProduct(key, p);
                            }

                            return (scopedBySubcategory || this.matchesAny(p, include) || exactMatch)
                                && this.matchesNone(p, exclude)
                                && this.isCompatibleProduct(key, p);
                        });

                        if (filtered.length) {
                            return this.sortedProducts(filtered);
                        }

                        if (key === 'cpu_cooler') {
                            return [];
                        }

                        if ((key === 'motherboard' || key === 'ram') && this.hasProcessorCompatibilityProfile()) {
                            return [];
                        }

                        return this.sortedProducts(this.products);
                    },

                    groupedOptionsForWithSelected(key, selectedProductId) {
                        const options = this.optionsForWithSelected(key, selectedProductId);
                        if (!this.sectionUsesSubcategories(key)) return [];

                        const categoryNameById = new Map(
                            (Array.isArray(this.categories) ? this.categories : []).map((category) => [
                                String(category?.id ?? ''),
                                String(category?.name || 'General'),
                            ])
                        );

                        const grouped = new Map();
                        options.forEach((product) => {
                            const label = categoryNameById.get(String(product?.category_id ?? '')) || 'General';

                            if (!grouped.has(label)) {
                                grouped.set(label, []);
                            }
                            grouped.get(label).push(product);
                        });

                        return Array.from(grouped.entries())
                            .map(([label, products]) => ({
                                label,
                                products: this.sortedProducts(products),
                            }))
                            .sort((a, b) => String(a.label).localeCompare(String(b.label)));
                    },

                    optionsForWithSelected(key, selectedProductId) {
                        const options = this.optionsFor(key);
                        if (!selectedProductId) return options;

                        const selectedId = String(selectedProductId);
                        if (options.some(product => String(product?.id) === selectedId)) {
                            return options;
                        }

                        const selectedProduct = this.productById(selectedProductId);
                        if (!selectedProduct) {
                            return options;
                        }

                        return this.sortedProducts([...options, selectedProduct]);
                    },

                    get subtotal() {
                        const singles = Object.keys(this.single).reduce((sum, key) => sum + this.singleSubtotal(key), 0);
                        const multi = Object.keys(this.rows).reduce((sum, key) => {
                            const rows = Array.isArray(this.rows[key]) ? this.rows[key] : [];
                            return sum + rows.reduce((s, row) => s + this.rowSubtotal(row), 0);
                        }, 0);
                        return singles + multi;
                    },

                    get laborFeeSafe() {
                        const v = Number(this.laborFee || 0);
                        return v > 0 ? v : 0;
                    },

                    get discountAmount() {
                        const discount = Number(this.discount || 0);
                        if (!(discount > 0)) return 0;

                        if (this.discountType === 'percent') {
                            const percent = Math.min(100, Math.max(0, discount));
                            return (this.subtotal + this.laborFeeSafe) * (percent / 100);
                        }

                        return Math.min(this.subtotal + this.laborFeeSafe, discount);
                    },

                    get grandTotal() {
                        return Math.max(0, (this.subtotal + this.laborFeeSafe) - this.discountAmount);
                    },

                    clearAll() {
                        Object.keys(this.single).forEach(key => {
                            this.single[key].product_id = '';
                            this.single[key].qty = 0;
                        });
                        Object.keys(this.rows).forEach(key => {
                            this.rows[key] = [{ uid: this.nextUid++, product_id: '', qty: 0 }];
                        });
                        this.customerName = '';
                        this.customerContact = '';
                        this.quotationName = '';
                        this.notes = '';
                        this.laborFee = 0;
                        this.discountType = 'amount';
                        this.discount = 0;
                    },

                    printQuote() {
                        this.closePrintPreview();
                        window.print();
                    },

                    openPrintPreview() {
                        this.showPrintPreview = true;
                    },

                    closePrintPreview() {
                        this.showPrintPreview = false;
                    },

                    formatMoney(amount) {
                        const value = Number(amount || 0);
                        return new Intl.NumberFormat('en-PH', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                        }).format(value);
                    },
                }));
                };

                document.addEventListener('alpine:init', registerPcBuilder);
                registerPcBuilder();
            })();
        </script>
    </div>
</x-app-layout>
