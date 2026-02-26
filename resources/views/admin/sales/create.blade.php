<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">Add sale</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Record items customers bought in store.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-black/10 p-6 sm:p-8"
                x-data="saleForm({{ \Illuminate\Support\Js::from($products) }})">
                <form method="POST" action="{{ route('admin.sales.store') }}" class="space-y-6"
                    @submit="if (soldAtAuto) soldAt = formatLocalDateTime(new Date())">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <label for="sold_at" class="block text-sm font-medium text-gray-900">Date & time</label>
                                <button type="button"
                                    class="inline-flex items-center rounded-md bg-white px-2.5 py-1 text-xs font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50"
                                    @click="soldAtAuto = true; soldAt = formatLocalDateTime(new Date())">
                                    Now
                                </button>
                            </div>
                            <input id="sold_at" name="sold_at" type="datetime-local"
                                x-model="soldAt"
                                @input="soldAtAuto = false"
                                value="{{ old('sold_at', now()->format('Y-m-d\\TH:i')) }}"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            @error('sold_at')<div class="mt-2 text-sm text-rose-700">{{ $message }}</div>@enderror
                        </div>

                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-900/80">
                                <input type="hidden" name="deduct_stock" value="0">
                                <input type="checkbox" name="deduct_stock" value="1" {{ old('deduct_stock', 1) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-gray-900 shadow-sm focus:ring-orange-500">
                                Deduct stock automatically
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="customer_name" class="block text-sm font-medium text-gray-900">Customer name <span class="text-rose-700">*</span></label>
                            <input id="customer_name" name="customer_name" type="text" value="{{ old('customer_name') }}" required
                                class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            @error('customer_name')<div class="mt-2 text-sm text-rose-700">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label for="customer_contact" class="block text-sm font-medium text-gray-900">Contact (optional)</label>
                            <input id="customer_contact" name="customer_contact" type="text" value="{{ old('customer_contact') }}"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            @error('customer_contact')<div class="mt-2 text-sm text-rose-700">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-900">Items</label>

                        @error('items')<div class="mt-2 text-sm text-rose-700">{{ $message }}</div>@enderror

                        <div class="mt-3 space-y-3">
                            <template x-for="(row, idx) in rows" :key="row.key">
                                <div class="rounded-lg bg-gray-50/80 p-4 ring-1 ring-inset ring-black/10">
                                    <div class="sale-item-row">
                                        <div class="sale-item-field min-w-0 relative">
                                            <label class="sale-item-field-label"
                                                :for="`sale_item_product_search_${idx}_${row.key}`">
                                                Product
                                            </label>
                                            <input type="hidden"
                                                :name="`items[${idx}][product_id]`"
                                                :value="row.product_id || ''">

                                            <input type="text"
                                                :id="`sale_item_product_search_${idx}_${row.key}`"
                                                class="block h-[42px] w-full rounded-md border-black/20 bg-white px-3 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                                x-model="row.search"
                                                @focus="openProductSearch(row)"
                                                @input="onProductSearchInput(row)"
                                                @blur="closeProductSearch(row)"
                                                @keydown.escape.prevent="row.searchOpen = false"
                                                @keydown.arrow-down.prevent="moveProductCursor(row, 1)"
                                                @keydown.arrow-up.prevent="moveProductCursor(row, -1)"
                                                @keydown.enter.prevent="selectHighlightedProduct(row)"
                                                placeholder="Search product by name"
                                                aria-label="Product search">

                                            <div x-show="row.searchOpen" x-cloak
                                                class="absolute z-30 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-black/10 bg-white shadow-lg">
                                                <template x-for="(p, pIdx) in filteredProducts(row)" :key="p.id">
                                                    <button type="button"
                                                        class="block w-full px-3 py-2 text-left text-sm hover:bg-orange-50"
                                                        :class="pIdx === row.searchCursor ? 'bg-orange-50' : ''"
                                                        @mousedown.prevent="selectProduct(row, p)">
                                                        <div class="font-medium text-gray-900" x-text="p.name"></div>
                                                        <div class="mt-0.5 text-xs text-gray-600 tabular-nums">
                                                            &#8369;<span x-text="formatMoney(p.price)"></span> Â· Stock: <span x-text="p.stock"></span>
                                                        </div>
                                                    </button>
                                                </template>

                                                <div x-show="filteredProducts(row).length === 0"
                                                    class="px-3 py-2 text-sm text-gray-600">
                                                    No products matched your search.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="sale-item-field">
                                            <label class="sale-item-field-label"
                                                :for="`sale_item_qty_${idx}_${row.key}`">
                                                Quantity
                                            </label>
                                            <input type="number" min="1"
                                                :id="`sale_item_qty_${idx}_${row.key}`"
                                                class="block h-[42px] w-full rounded-md border-black/20 bg-white px-3 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                                :name="`items[${idx}][qty]`"
                                                x-model.number="row.qty"
                                                aria-label="Quantity"
                                                placeholder="Qty">
                                        </div>

                                        <div class="sale-item-field">
                                            <label class="sale-item-field-label"
                                                :for="`sale_item_unit_price_${idx}_${row.key}`">
                                                Unit Price
                                            </label>
                                            <input type="number" min="0" step="0.01"
                                                :id="`sale_item_unit_price_${idx}_${row.key}`"
                                                class="block h-[42px] w-full rounded-md border-black/20 bg-white px-3 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                                :name="`items[${idx}][unit_price]`"
                                                x-model.number="row.unit_price"
                                                aria-label="Unit price"
                                                placeholder="â‚±">
                                        </div>

                                        <div class="sale-item-actions">
                                            <div class="sale-item-meta tabular-nums">
                                                <span x-show="row.product_id">
                                                    Stock: <span class="font-medium text-gray-900" x-text="productStock(row.product_id)"></span>
                                                </span>
                                            </div>
                                            <button type="button"
                                                class="inline-flex h-[42px] items-center justify-center rounded-md bg-white px-3 text-sm font-medium text-rose-700 shadow-sm ring-1 ring-inset ring-black/10 hover:bg-orange-50"
                                                @click="removeRow(idx)"
                                                :disabled="rows.length === 1">
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="mt-3">
                            <button type="button"
                                class="inline-flex items-center justify-center rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
                                @click="addRow()">
                                Add item
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="discount" class="block text-sm font-medium text-gray-900">Discount (optional)</label>
                            <input id="discount" name="discount" type="number" min="0" step="0.01" x-model.number="discount"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            @error('discount')<div class="mt-2 text-sm text-rose-700">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label for="payment_mode" class="block text-sm font-medium text-gray-900">Mode of payment</label>
                            <select id="payment_mode" name="payment_mode" required x-model="payment_mode"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                @foreach ($paymentModeGroups as $groupLabel => $modeOptions)
                                    <optgroup label="{{ $groupLabel }}">
                                        @foreach ($modeOptions as $modeValue => $modeLabel)
                                            <option value="{{ $modeValue }}"
                                                {{ old('payment_mode', $defaultPaymentMode) === $modeValue ? 'selected' : '' }}>
                                                {{ $modeLabel }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('payment_mode')<div class="mt-2 text-sm text-rose-700">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label for="payment_status" class="block text-sm font-medium text-gray-900">Payment status</label>
                            <select id="payment_status" name="payment_status"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                x-model="payment_status">
                                <option value="paid" {{ old('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="partial" {{ old('payment_status') === 'partial' ? 'selected' : '' }}>Partial</option>
                                <option value="unpaid" {{ old('payment_status', 'unpaid') === 'unpaid' ? 'selected' : '' }}>Not yet paid</option>
                            </select>
                            @error('payment_status')<div class="mt-2 text-sm text-rose-700">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div x-show="payment_status === 'partial'" x-cloak>
                            <label for="amount_paid" class="block text-sm font-medium text-gray-900">Amount paid</label>
                            <input id="amount_paid" name="amount_paid" type="number" min="0" step="0.01" value="{{ old('amount_paid', 0) }}"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            @error('amount_paid')<div class="mt-2 text-sm text-rose-700">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label for="sale_status" class="block text-sm font-medium text-gray-900">Sale status</label>
                            <select id="sale_status" name="sale_status"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                <option value="completed" {{ old('sale_status') === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="processing" {{ old('sale_status', 'processing') === 'processing' ? 'selected' : '' }}>Processing</option>
                            </select>
                            @error('sale_status')<div class="mt-2 text-sm text-rose-700">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-900">Notes (optional)</label>
                            <input id="notes" name="notes" type="text" value="{{ old('notes') }}"
                                class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            @error('notes')<div class="mt-2 text-sm text-rose-700">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="rounded-lg bg-gray-50/80 p-4 ring-1 ring-inset ring-black/10">
                        <div class="flex items-center justify-between text-sm">
                            <div class="text-gray-700">Subtotal</div>
                            <div class="font-semibold text-gray-900 tabular-nums">&#8369;<span x-text="formatMoney(subtotal())"></span></div>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-sm" x-show="discountAmount() > 0" x-cloak>
                            <div class="text-gray-700">Discount</div>
                            <div class="font-semibold tabular-nums text-rose-700">-&#8369;<span x-text="formatMoney(discountAmount())"></span></div>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-sm" x-show="hasPosSurcharge()" x-cloak>
                            <div class="text-gray-700">POS surcharge ({{ (int) round($posSurchargeRate * 100) }}%)</div>
                            <div class="font-semibold text-gray-900 tabular-nums">&#8369;<span x-text="formatMoney(posSurchargeAmount())"></span></div>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-sm">
                            <div class="text-gray-700">Grand total</div>
                            <div class="text-lg font-semibold text-gray-900 tabular-nums">&#8369;<span x-text="formatMoney(grandTotal())"></span></div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <a href="{{ route('admin.sales') }}"
                            class="inline-flex items-center justify-center rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                            Back
                        </a>

                        <button type="submit"
                            class="inline-flex items-center justify-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                            Create sale
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .sale-item-row {
            display: grid;
            gap: 0.75rem;
            grid-template-columns: 1fr;
        }

        .sale-item-field-label {
            display: block;
            margin-bottom: 0.35rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: rgb(55 65 81);
        }

        .sale-item-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .sale-item-meta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            white-space: nowrap;
            font-size: 0.75rem;
            color: rgb(0 0 0 / 0.6);
        }

        @media (min-width: 640px) {
            .sale-item-row {
                align-items: end;
                grid-template-columns: minmax(0, 1.8fr) 7rem 10rem auto;
            }

            .sale-item-actions {
                flex-wrap: nowrap;
            }
        }
    </style>

    <script>
        function saleForm(products) {
            return {
                products,
                soldAt: @json(old('sold_at', now()->format('Y-m-d\\TH:i'))),
                soldAtAuto: @json(old('sold_at') ? false : true),
                payment_status: @json(old('payment_status', 'unpaid')),
                payment_mode: @json(old('payment_mode', $defaultPaymentMode)),
                discount: Number(@json(old('discount', 0))),
                posSurchargeRate: Number(@json($posSurchargeRate)),
                posSurchargeModes: @json($posSurchargeModes),
                rows: [],
                init() {
                    this.rows = [this.createRow()]

                    if (!this.soldAtAuto) return
                    const tick = () => {
                        if (!this.soldAtAuto) return
                        this.soldAt = this.formatLocalDateTime(new Date())
                    }

                    tick()
                    setInterval(tick, 1000)
                },
                createRow() {
                    return {
                        key: crypto?.randomUUID?.() ?? String(Date.now() + Math.random()),
                        product_id: '',
                        qty: 1,
                        unit_price: 0,
                        search: '',
                        searchOpen: false,
                        searchCursor: -1,
                    }
                },
                formatLocalDateTime(date) {
                    const pad = (n) => String(n).padStart(2, '0')
                    const y = date.getFullYear()
                    const m = pad(date.getMonth() + 1)
                    const d = pad(date.getDate())
                    const hh = pad(date.getHours())
                    const mm = pad(date.getMinutes())
                    return `${y}-${m}-${d}T${hh}:${mm}`
                },
                addRow() {
                    this.rows.push(this.createRow())
                },
                removeRow(idx) {
                    if (this.rows.length <= 1) return
                    this.rows.splice(idx, 1)
                },
                productById(productId) {
                    const id = Number(productId)
                    return this.products.find(p => Number(p.id) === id) ?? null
                },
                productStock(productId) {
                    return Number(this.productById(productId)?.stock ?? 0)
                },
                openProductSearch(row) {
                    row.searchOpen = true
                    row.searchCursor = 0
                },
                closeProductSearch(row) {
                    window.setTimeout(() => {
                        row.searchOpen = false
                        row.searchCursor = -1

                        if (!row.product_id) return
                        const selected = this.productById(row.product_id)
                        if (selected) row.search = String(selected.name || '')
                    }, 120)
                },
                onProductSearchInput(row) {
                    row.searchOpen = true
                    row.searchCursor = 0

                    const selected = this.productById(row.product_id)
                    if (!selected) {
                        row.product_id = ''
                        row.unit_price = 0
                        return
                    }

                    if (String(row.search || '').trim() !== String(selected.name || '')) {
                        row.product_id = ''
                        row.unit_price = 0
                    }
                },
                filteredProducts(row) {
                    const query = String(row.search || '').trim().toLowerCase()

                    if (query === '') {
                        return this.products.slice(0, 30)
                    }

                    return this.products
                        .filter((p) => {
                            const name = String(p.name || '').toLowerCase()
                            const idText = String(p.id || '')
                            return name.includes(query) || idText.includes(query)
                        })
                        .slice(0, 30)
                },
                moveProductCursor(row, step) {
                    const list = this.filteredProducts(row)
                    if (list.length === 0) {
                        row.searchCursor = -1
                        return
                    }

                    if (!row.searchOpen) row.searchOpen = true
                    if (!Number.isInteger(row.searchCursor) || row.searchCursor < 0) {
                        row.searchCursor = 0
                        return
                    }

                    row.searchCursor = (row.searchCursor + step + list.length) % list.length
                },
                selectHighlightedProduct(row) {
                    const list = this.filteredProducts(row)
                    if (list.length === 0) return

                    const idx = Number.isInteger(row.searchCursor) && row.searchCursor >= 0
                        ? row.searchCursor
                        : 0

                    this.selectProduct(row, list[idx])
                },
                selectProduct(row, product) {
                    if (!product) return
                    row.product_id = String(product.id)
                    row.search = String(product.name || '')
                    row.unit_price = Number(product.price ?? 0)
                    row.searchOpen = false
                    row.searchCursor = -1
                },
                onProductChange(row) {
                    const p = this.productById(row.product_id)
                    if (!p) return
                    row.unit_price = Number(p.price ?? 0)
                },
                lineTotal(row) {
                    const qty = Math.max(1, Number(row.qty ?? 1))
                    const price = Math.max(0, Number(row.unit_price ?? 0))
                    return qty * price
                },
                subtotal() {
                    return this.rows.reduce((sum, row) => sum + this.lineTotal(row), 0)
                },
                discountAmount() {
                    const discount = Number(this.discount ?? 0)
                    return Number.isFinite(discount) ? Math.max(0, discount) : 0
                },
                hasPosSurcharge() {
                    const selectedMode = String(this.payment_mode ?? '').toLowerCase()
                    return this.posSurchargeModes.includes(selectedMode)
                },
                baseGrandTotal() {
                    return Math.max(0, this.subtotal() - this.discountAmount())
                },
                posSurchargeAmount() {
                    if (!this.hasPosSurcharge()) return 0
                    return this.baseGrandTotal() * this.posSurchargeRate
                },
                grandTotal() {
                    return this.baseGrandTotal() + this.posSurchargeAmount()
                },
                formatMoney(value) {
                    const n = Number(value ?? 0)
                    return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                },
            }
        }
    </script>
</x-app-layout>


