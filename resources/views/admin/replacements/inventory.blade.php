<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-gray-900 text-white flex items-center justify-center">
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16M9 4v16m6-16v16" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-xl text-gray-900 leading-tight">RMA Inventory</h2>
                    <p class="mt-1 text-sm text-gray-600">Accumulated inventory view of replaced and warranty items.</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.replacements.index') }}"
                    class="inline-flex h-10 items-center justify-center rounded-md bg-white px-4 text-sm font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                    Back To RMA Logs
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

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-black/10">
                    <div class="text-sm font-medium text-gray-600">RMA Products</div>
                    <div class="mt-2 text-2xl sm:text-3xl font-semibold text-gray-900 tabular-nums">{{ number_format((int) $rmaInventoryProductCount) }}</div>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-black/10">
                    <div class="text-sm font-medium text-gray-600">RMA Units</div>
                    <div class="mt-2 text-2xl sm:text-3xl font-semibold text-gray-900 tabular-nums">{{ number_format((int) $rmaInventoryUnits) }}</div>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-black/10">
                    <div class="text-sm font-medium text-gray-600">RMA Value</div>
                    <div class="mt-2 text-2xl sm:text-3xl font-semibold text-rose-700 tabular-nums">&#8369;{{ number_format((float) $rmaInventoryValue, 2) }}</div>
                </div>
            </div>

            <div class="rounded-xl bg-white shadow-sm ring-1 ring-black/10 p-6">
                <form method="GET" class="inventory-filter-form">
                    <div>
                        <label for="rma_inventory_type" class="block text-sm font-medium text-gray-900">Type</label>
                        <select id="rma_inventory_type" name="type"
                            class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <option value="" {{ $selectedType === '' ? 'selected' : '' }}>All</option>
                            <option value="replacement" {{ $selectedType === 'replacement' ? 'selected' : '' }}>Replacement</option>
                            <option value="warranty" {{ $selectedType === 'warranty' ? 'selected' : '' }}>Warranty</option>
                        </select>
                    </div>
                    <div>
                        <label for="rma_inventory_q" class="block text-sm font-medium text-gray-900">Search</label>
                        <input id="rma_inventory_q" name="q" type="text" value="{{ $search }}" placeholder="Product name"
                            class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                    </div>
                    <div>
                        <button type="submit"
                            class="inline-flex h-10 w-full items-center justify-center rounded-md bg-blue-600 px-4 text-sm font-medium text-white shadow-sm hover:bg-blue-600/90 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">
                            Apply
                        </button>
                    </div>
                    <div>
                        <a href="{{ route('admin.replacements.inventory') }}"
                            class="inline-flex h-10 w-full items-center justify-center rounded-md bg-white px-4 text-sm font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                            Clear
                        </a>
                    </div>
                </form>
            </div>

            <div class="rounded-xl bg-white shadow-sm ring-1 ring-black/10 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-5 border-b border-black/10">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">RMA Inventory Records</h3>
                        <p class="mt-1 text-sm text-gray-600">Grouped by product and type.</p>
                    </div>
                </div>

                @if ($rmaInventory->isEmpty())
                    <div class="px-6 py-8 text-sm text-gray-700">
                        No RMA inventory records found.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs font-semibold text-gray-600 bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3">Product</th>
                                    <th class="px-6 py-3">Type</th>
                                    <th class="px-6 py-3 text-right">RMA Qty</th>
                                    <th class="px-6 py-3 text-right">RMA Value</th>
                                    <th class="px-6 py-3 text-right">Current Store Stock</th>
                                    <th class="px-6 py-3">Last RMA Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-black/10">
                                @foreach ($rmaInventory as $row)
                                    @php($typeLabel = $row->type === 'warranty' ? 'Warranty' : 'Replacement')
                                    <tr class="even:bg-zinc-50 hover:bg-gray-50/90">
                                        <td class="px-6 py-3 font-medium text-gray-900">{{ $row->product_name }}</td>
                                        <td class="px-6 py-3">
                                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $row->type === 'warranty' ? 'bg-cyan-50 text-cyan-700 ring-1 ring-cyan-200' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' }}">
                                                {{ $typeLabel }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-right tabular-nums font-semibold text-gray-900">{{ number_format((int) ($row->total_quantity ?? 0)) }}</td>
                                        <td class="px-6 py-3 text-right tabular-nums font-semibold text-rose-700">&#8369;{{ number_format((float) ($row->total_cost ?? 0), 2) }}</td>
                                        <td class="px-6 py-3 text-right tabular-nums text-gray-700">
                                            {{ $row->current_stock !== null ? number_format((int) $row->current_stock) : '-' }}
                                        </td>
                                        <td class="px-6 py-3 text-gray-700">
                                            {{ !empty($row->last_processed_at) ? \Illuminate\Support\Carbon::parse((string) $row->last_processed_at)->format('M j, Y h:i A') : '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        .inventory-filter-form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
            align-items: end;
        }

        @media (min-width: 1024px) {
            .inventory-filter-form {
                grid-template-columns: minmax(0, 1fr) minmax(0, 1.5fr) 8rem 8rem;
            }
        }
    </style>
</x-app-layout>

