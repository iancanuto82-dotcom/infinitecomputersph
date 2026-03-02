<x-app-layout>
    @php($canEditSales = \App\Support\AdminAccess::hasPermission(auth()->user(), 'sales.edit'))

    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-gray-900 text-white flex items-center justify-center">
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 6h14a2 2 0 012 2v11a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2zm3 7l2 2 4-4" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-xl text-gray-900 leading-tight">Replacement & Warranty</h2>
                    <p class="mt-1 text-sm text-gray-600">Track replaced items and auto write-off at cost price.</p>
                </div>
            </div>
            <div>
                <a href="{{ route('admin.replacements.inventory') }}"
                    class="inline-flex h-10 items-center justify-center rounded-md bg-white px-4 text-sm font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                    View RMA Inventory
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-[92rem] mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-inset ring-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 ring-1 ring-inset ring-rose-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-black/10">
                    <div class="text-sm font-medium text-gray-600">Total Write-off</div>
                    <div class="mt-2 text-2xl sm:text-3xl font-semibold text-rose-700 tabular-nums">&#8369;{{ number_format((float) $totalWriteOff, 2) }}</div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-black/10">
                    <div class="text-sm font-medium text-gray-600">Current Month</div>
                    <div class="mt-2 text-2xl sm:text-3xl font-semibold text-gray-900 tabular-nums">&#8369;{{ number_format((float) $currentMonthWriteOff, 2) }}</div>
                    <div class="mt-1 text-xs text-gray-600">{{ now()->format('F Y') }}</div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-black/10">
                    <div class="text-sm font-medium text-gray-600">Total Units Replaced</div>
                    <div class="mt-2 text-2xl sm:text-3xl font-semibold text-gray-900 tabular-nums">{{ number_format((int) $totalUnits) }}</div>
                </div>
            </div>

            @if ($canEditSales)
                <div class="rounded-xl bg-white shadow-sm ring-1 ring-black/10 p-6">
                    <h3 class="text-base font-semibold text-gray-900">Add Replacement/Warranty Item</h3>
                    <p class="mt-1 text-sm text-gray-600">Stock will be deducted and an expense will be created using product cost price.</p>

                    <form method="POST" action="{{ route('admin.replacements.store') }}" class="mt-4 grid grid-cols-1 lg:grid-cols-6 gap-3">
                        @csrf
                        <div class="lg:col-span-2">
                            <label for="replacement_product_id" class="block text-sm font-medium text-gray-900">Product</label>
                            <select id="replacement_product_id" name="product_id" required
                                class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                <option value="">Select product</option>
                                @foreach ($products as $product)
                                    @php($unitCost = $product->cost_price !== null ? number_format((float) $product->cost_price, 2, '.', '') : '')
                                    @php($optionDisabled = ((int) $product->stock) <= 0 || $product->cost_price === null)
                                    <option value="{{ (int) $product->id }}"
                                        data-stock="{{ (int) $product->stock }}"
                                        data-unit-cost="{{ $unitCost }}"
                                        {{ old('product_id') == $product->id ? 'selected' : '' }}
                                        {{ $optionDisabled ? 'disabled' : '' }}>
                                        {{ $product->name }} | Stock: {{ (int) $product->stock }} | Cost:
                                        {{ $product->cost_price !== null ? 'PHP '.number_format((float) $product->cost_price, 2) : 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="replacement_type" class="block text-sm font-medium text-gray-900">Type</label>
                            <select id="replacement_type" name="type"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                <option value="replacement" {{ old('type', 'replacement') === 'replacement' ? 'selected' : '' }}>Replacement</option>
                                <option value="warranty" {{ old('type') === 'warranty' ? 'selected' : '' }}>Warranty</option>
                            </select>
                        </div>

                        <div>
                            <label for="replacement_quantity" class="block text-sm font-medium text-gray-900">Quantity</label>
                            <input id="replacement_quantity" name="quantity" type="number" min="1" required value="{{ old('quantity', 1) }}"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                        </div>

                        <div>
                            <label for="replacement_processed_at" class="block text-sm font-medium text-gray-900">Date & time</label>
                            <input id="replacement_processed_at" name="processed_at" type="datetime-local"
                                value="{{ old('processed_at', now()->format('Y-m-d\\TH:i')) }}"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                        </div>

                        <div class="lg:col-span-6 grid grid-cols-1 lg:grid-cols-3 gap-3">
                            <div>
                                <div class="text-xs text-gray-600">Unit cost</div>
                                <div id="replacement_unit_cost_preview" class="mt-1 rounded-md border border-black/10 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900 tabular-nums">PHP 0.00</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-600">Total write-off</div>
                                <div id="replacement_total_cost_preview" class="mt-1 rounded-md border border-black/10 bg-gray-50 px-3 py-2 text-sm font-semibold text-rose-700 tabular-nums">PHP 0.00</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-600">Available stock</div>
                                <div id="replacement_stock_preview" class="mt-1 rounded-md border border-black/10 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900 tabular-nums">0</div>
                            </div>
                        </div>

                        <div class="lg:col-span-6">
                            <label for="replacement_notes" class="block text-sm font-medium text-gray-900">Notes (optional)</label>
                            <input id="replacement_notes" name="notes" type="text" value="{{ old('notes') }}"
                                placeholder="Customer/job reference or replacement reason"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                        </div>

                        <div class="lg:col-span-6">
                            <button type="submit"
                                class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                                Save Replacement/Warranty
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="rounded-xl bg-white shadow-sm ring-1 ring-black/10 p-6">
                <form method="GET" class="replacement-filter-form">
                    <div>
                        <label for="replacement_type_filter" class="block text-sm font-medium text-gray-900">Type</label>
                        <select id="replacement_type_filter" name="type"
                            class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <option value="" {{ $selectedType === '' ? 'selected' : '' }}>All</option>
                            <option value="replacement" {{ $selectedType === 'replacement' ? 'selected' : '' }}>Replacement</option>
                            <option value="warranty" {{ $selectedType === 'warranty' ? 'selected' : '' }}>Warranty</option>
                        </select>
                    </div>

                    <div>
                        <label for="replacement_q" class="block text-sm font-medium text-gray-900">Search</label>
                        <input id="replacement_q" name="q" type="text" value="{{ $search }}" placeholder="Product name or notes"
                            class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                    </div>

                    <div>
                        <button type="submit"
                            class="inline-flex h-10 w-full items-center justify-center rounded-md bg-blue-600 px-4 text-sm font-medium text-white shadow-sm hover:bg-blue-600/90 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">
                            Apply
                        </button>
                    </div>
                    <div>
                        <a href="{{ route('admin.replacements.index') }}"
                            class="inline-flex h-10 w-full items-center justify-center rounded-md bg-white px-4 text-sm font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                            Clear
                        </a>
                    </div>
                </form>
            </div>

            <div class="rounded-xl bg-white shadow-sm ring-1 ring-black/10 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-5 border-b border-black/10">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Replacement/Warranty Records</h3>
                        <p class="mt-1 text-sm text-gray-600">Logged items with automatic expense write-off.</p>
                    </div>
                </div>

                @if ($items->isEmpty())
                    <div class="px-6 py-10 text-center text-sm text-gray-700">
                        No replacement or warranty records yet.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs font-semibold text-gray-600 bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3">Type</th>
                                    <th class="px-6 py-3">Product</th>
                                    <th class="px-6 py-3 text-right">Qty</th>
                                    <th class="px-6 py-3 text-right">Unit Cost</th>
                                    <th class="px-6 py-3 text-right">Total</th>
                                    <th class="px-6 py-3">Notes</th>
                                    <th class="px-6 py-3">Recorded By</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-black/10">
                                @foreach ($items as $item)
                                    @php($typeLabel = $item->type === 'warranty' ? 'Warranty' : 'Replacement')
                                    <tr class="replacement-record-row even:bg-zinc-50 hover:bg-gray-50/90">
                                        <td class="px-6 py-3 whitespace-nowrap text-gray-700">
                                            {{ optional($item->processed_at)->format('M j, Y h:i A') ?? '-' }}
                                        </td>
                                        <td class="px-6 py-3">
                                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $item->type === 'warranty' ? 'bg-cyan-50 text-cyan-700 ring-1 ring-cyan-200' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' }}">
                                                {{ $typeLabel }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 font-medium text-gray-900">{{ $item->product_name }}</td>
                                        <td class="px-6 py-3 text-right tabular-nums text-gray-900">{{ number_format((int) $item->quantity) }}</td>
                                        <td class="px-6 py-3 text-right tabular-nums text-gray-700">&#8369;{{ number_format((float) $item->unit_cost, 2) }}</td>
                                        <td class="px-6 py-3 text-right tabular-nums font-semibold text-rose-700">&#8369;{{ number_format((float) $item->total_cost, 2) }}</td>
                                        <td class="px-6 py-3 text-gray-700">{{ $item->notes ?: '-' }}</td>
                                        <td class="px-6 py-3 text-gray-700">{{ $item->processor?->name ?: 'System' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-black/10">
                        {{ $items->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        .replacement-filter-form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
            align-items: end;
        }

        @media (min-width: 1024px) {
            .replacement-filter-form {
                grid-template-columns: minmax(0, 1fr) minmax(0, 1.5fr) 8rem 8rem;
            }
        }
    </style>

    <script>
        (() => {
            const productInput = document.getElementById('replacement_product_id');
            const qtyInput = document.getElementById('replacement_quantity');
            const unitCostPreview = document.getElementById('replacement_unit_cost_preview');
            const totalCostPreview = document.getElementById('replacement_total_cost_preview');
            const stockPreview = document.getElementById('replacement_stock_preview');

            if (!productInput || !qtyInput || !unitCostPreview || !totalCostPreview || !stockPreview) return;

            const formatMoney = (value) => {
                const amount = Number.isFinite(value) ? value : 0;
                return 'PHP ' + amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            };

            const syncPreview = () => {
                const selected = productInput.options[productInput.selectedIndex];
                const unitCost = Number(selected?.dataset?.unitCost || 0);
                const stock = Number(selected?.dataset?.stock || 0);
                const quantity = Math.max(1, Number(qtyInput.value || 1));
                const total = unitCost * quantity;

                unitCostPreview.textContent = formatMoney(unitCost);
                totalCostPreview.textContent = formatMoney(total);
                stockPreview.textContent = Number.isFinite(stock) ? String(stock) : '0';
            };

            productInput.addEventListener('change', syncPreview);
            qtyInput.addEventListener('input', syncPreview);
            syncPreview();
        })();
    </script>
</x-app-layout>
