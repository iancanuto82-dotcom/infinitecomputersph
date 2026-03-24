<x-app-layout>
    @php($canEditSales = \App\Support\AdminAccess::hasPermission(auth()->user(), 'sales.edit'))

    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-gray-900 text-white flex items-center justify-center">
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-1.2 6H19m-12 0a1 1 0 001 1h10a1 1 0 001-1M9 22a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-xl text-gray-900 leading-tight">Sales</h2>
                    <p class="mt-1 text-sm text-gray-600">Track revenue, expenses, payments, and in-store purchases.</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.expenses') }}"
                    class="inline-flex items-center gap-2 rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                    Expenses
                </a>
                @if ($canEditSales)
                    <a href="{{ route('admin.sales.create') }}"
                        class="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-600/90 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">
                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/15">+</span>
                        New Sale
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-[92rem] mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="rounded-lg bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 ring-1 ring-inset ring-rose-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-black/10 flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-medium text-gray-600">Total Sales</div>
                        <div class="mt-2 text-2xl sm:text-3xl font-semibold text-gray-900 tabular-nums">&#8369;{{ number_format((float) $totalRevenue, 2) }}</div>
                        <div class="mt-1 text-xs text-gray-600">{{ number_format((int) $totalSalesCount) }} transactions</div>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center ring-1 ring-inset ring-blue-100">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M8 15l3-3 3 2 6-7" />
                        </svg>
                    </div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-black/10 flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-medium text-gray-600">Total Expenses</div>
                        <div class="mt-2 text-2xl sm:text-3xl font-semibold text-rose-700 tabular-nums">&#8369;{{ number_format((float) $totalExpenses, 2) }}</div>
                        <div class="mt-1 text-xs text-gray-600">Logged expense entries</div>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-rose-50 text-rose-700 flex items-center justify-center ring-1 ring-inset ring-rose-100">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4m16 0l-1.2 12a2 2 0 01-2 1.8H7.2a2 2 0 01-2-1.8L4 7m4-3h8a2 2 0 012 2v1H6V6a2 2 0 012-2z" />
                        </svg>
                    </div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-black/10 flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-medium text-gray-600">Current Month (Net)</div>
                        <div class="mt-2 text-2xl sm:text-3xl font-semibold tabular-nums {{ (float) $currentMonthNetSales < 0 ? 'text-rose-700' : 'text-gray-900' }}">
                            &#8369;{{ number_format((float) $currentMonthNetSales, 2) }}
                        </div>
                        <div class="mt-1 text-xs text-gray-600">{{ now()->format('F Y') }} &middot; {{ number_format((int) $currentMonthSalesCount) }} sales</div>
                        <div class="mt-0.5 text-xs text-gray-600">
                            Sales: &#8369;{{ number_format((float) $currentMonthRevenue, 2) }} &middot; Expenses: &#8369;{{ number_format((float) $currentMonthExpenses, 2) }}
                        </div>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-cyan-50 text-cyan-700 flex items-center justify-center ring-1 ring-inset ring-cyan-100">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M6 7h12a2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V9a2 2 0 012-2z" />
                        </svg>
                    </div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-black/10 flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-medium text-gray-600">Paid Sales</div>
                        <div class="mt-2 text-2xl sm:text-3xl font-semibold text-gray-900 tabular-nums">&#8369;{{ number_format((float) $paidRevenue, 2) }}</div>
                        <div class="mt-1 text-xs text-gray-600">Received payments</div>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center ring-1 ring-inset ring-emerald-100">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m7 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-black/10 flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-medium text-gray-600">Outstanding</div>
                        <div class="mt-2 text-2xl sm:text-3xl font-semibold text-rose-700 tabular-nums">&#8369;{{ number_format((float) $outstanding, 2) }}</div>
                        <div class="mt-1 text-xs text-gray-600">Unpaid amount</div>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-rose-50 text-rose-700 flex items-center justify-center ring-1 ring-inset ring-rose-100">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86l-7.3 12.64A2 2 0 004.72 19h14.56a2 2 0 001.73-2.5l-7.3-12.64a2 2 0 00-3.42 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div x-data="{ monthlyBreakdownOpen: true }" class="rounded-xl bg-white shadow-sm ring-1 ring-black/10 p-6">
                <button type="button"
                    class="flex w-full items-center justify-between gap-4 text-left"
                    :aria-expanded="monthlyBreakdownOpen ? 'true' : 'false'"
                    @click="monthlyBreakdownOpen = !monthlyBreakdownOpen">
                    <div class="flex items-center gap-2">
                        <svg viewBox="0 0 24 24" class="h-5 w-5 text-blue-700" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16M4 5h16M6 7v10M10 9h10M10 13h10" />
                        </svg>
                        <div>
                            <div class="text-base font-semibold text-gray-900">Monthly Sales Breakdown</div>
                            <div class="text-sm text-gray-600">Last 12 months</div>
                        </div>
                    </div>

                    <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-600">
                        <span x-text="monthlyBreakdownOpen ? 'Hide' : 'Show'"></span>
                        <svg viewBox="0 0 20 20" class="h-4 w-4 transition-transform duration-200" :class="monthlyBreakdownOpen ? 'rotate-180' : ''" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </button>

                <div x-cloak x-show="monthlyBreakdownOpen" x-transition class="mt-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @forelse ($monthlyBreakdown as $row)
                            @php($monthKey = (string) $row->month)
                            @php($isCurrent = $monthKey === now()->format('Y-m'))
                            <a href="{{ route('admin.sales', ['year' => substr($monthKey, 0, 4), 'month' => (int) substr($monthKey, 5, 2)]) }}"
                                class="rounded-xl border p-4 shadow-sm {{ $isCurrent ? 'border-blue-600 ring-1 ring-blue-200 bg-blue-50/30' : 'border-black/10 bg-white hover:bg-gray-50/80' }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <div class="font-semibold text-gray-900">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $monthKey)->format('F Y') }}</div>
                                            @if ($isCurrent)
                                                <span class="inline-flex items-center rounded-full bg-blue-600 px-2 py-0.5 text-xs font-semibold text-white">Current</span>
                                            @endif
                                        </div>
                                        <div class="mt-1 text-sm text-gray-600">{{ number_format((int) $row->sales_count) }} sale(s)</div>
                                    </div>
                                    <div class="text-right font-semibold text-gray-900 tabular-nums">
                                        &#8369;{{ number_format((float) $row->revenue, 2) }}
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="text-sm text-gray-600">No sales yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="rounded-xl bg-white shadow-sm ring-1 ring-black/10 p-6">
                <form method="GET" class="sales-filter-form">
                    <div class="sales-filter-field">
                        <label for="month" class="block text-sm font-medium text-gray-900">Month Filter</label>
                        <select id="month" name="month"
                            class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <option value="all" {{ $selectedMonthNumber ? '' : 'selected' }}>All Months</option>
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ (int) $selectedMonthNumber === $m ? 'selected' : '' }}>
                                    {{ \Illuminate\Support\Carbon::createFromDate(2020, $m, 1)->format('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div class="sales-filter-field">
                        <label for="year" class="block text-sm font-medium text-gray-900">Year Filter</label>
                        <select id="year" name="year"
                            class="mt-1 block w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <option value="all" {{ $selectedYear ? '' : 'selected' }}>All Years</option>
                            @foreach ($years as $y)
                                <option value="{{ $y }}" {{ (int) $selectedYear === (int) $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sales-filter-field">
                        <label for="q" class="block text-sm font-medium text-gray-900">Search</label>
                        <div class="sales-search-wrap">
                            <input id="q" name="q" type="text" value="{{ $search }}"
                                placeholder="Invoice # or customer name"
                                class="block h-10 w-full rounded-md border-black/20 bg-white px-3 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <button type="submit"
                                class="sales-search-btn inline-flex items-center justify-center rounded-md bg-zinc-900 px-4 text-sm font-medium text-white shadow-sm hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                                Search
                            </button>
                        </div>
                    </div>

                    <div>
                        <button type="submit"
                            class="sales-filter-btn inline-flex w-full items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-600/90 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">
                            Apply Filters
                        </button>
                    </div>
                    <div>
                        <a href="{{ route('admin.sales') }}"
                            class="sales-filter-btn inline-flex w-full items-center justify-center rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                            Clear
                        </a>
                    </div>
                </form>
            </div>

            <div class="rounded-xl bg-white shadow-sm ring-1 ring-black/10 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-5 border-b border-black/10">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Sales Records</h3>
                        <p class="mt-1 text-sm text-gray-600">Invoice, customer, payment, and status.</p>
                    </div>
                </div>

                @if ($sales->isEmpty())
                    <div class="px-6 py-10 text-center">
                        <div class="text-sm text-gray-700">No sales found for the selected filters.</div>
                        @if ($canEditSales)
                            <div class="mt-3">
                                <a href="{{ route('admin.sales.create') }}"
                                    class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                                    Add sale
                                </a>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs font-semibold text-gray-600 bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3">Invoice #</th>
                                    <th class="px-6 py-3">Customer</th>
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3 text-right">Total Amount</th>
                                    <th class="px-6 py-3">Payment Mode</th>
                                    <th class="px-6 py-3">Payment Status</th>
                                    <th class="px-6 py-3">Sale Status</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-black/10">
                                @foreach ($sales as $sale)
                                    @php($invoice = $sale->invoice_no ?: ('SALE-'.$sale->id))
                                    @php($payment = $sale->payment_status ?? 'paid')
                                    @php($grandTotal = (float) ($sale->grand_total ?? 0))
                                    @php($amountPaid = (float) ($sale->amount_paid ?? 0))
                                    @php($saleStatus = (string) ($sale->sale_status ?? 'completed'))
                                    @php($effectiveSaleStatus = $sale->effectiveStatus())
                                    @php($isLifecycleLocked = in_array($effectiveSaleStatus, ['cancelled', 'refunded'], true))
                                    @php($paymentLocked = $isLifecycleLocked || $payment === 'paid')
                                    @php($saleStatusLocked = $isLifecycleLocked || $saleStatus === 'completed')
                                    @php($paymentLockLabel = match (true) {
                                        $isLifecycleLocked => ucfirst($effectiveSaleStatus),
                                        $payment === 'paid' => 'Paid',
                                        default => 'Locked',
                                    })
                                    @php($paymentSelectClass = match ($payment) {
                                        'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                        'partial' => 'bg-gray-50 text-orange-700 ring-orange-200',
                                        default => 'bg-rose-50 text-rose-700 ring-rose-200',
                                    })
                                    @php($saleStatusClass = match ($effectiveSaleStatus) {
                                        'refunded' => 'bg-violet-50 text-violet-700 ring-violet-200',
                                        'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-200',
                                        'completed' => 'bg-blue-50 text-blue-700 ring-blue-200',
                                        default => 'bg-gray-50 text-orange-700 ring-orange-200',
                                    })

                                    <tr class="even:bg-zinc-50 hover:bg-gray-50/90">
                                        <td class="px-6 py-3 align-middle font-semibold text-gray-900 whitespace-nowrap">{{ $invoice }}</td>
                                        <td class="px-6 py-3 align-middle">
                                            <div class="font-medium text-gray-900">{{ $sale->customer_name }}</div>
                                            @if ($sale->customer_contact)
                                                <div class="mt-0.5 text-xs text-gray-600">{{ $sale->customer_contact }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 align-middle whitespace-nowrap text-gray-700">
                                            {{ optional($sale->sold_at)->format('M j, Y h:i A') ?? '-' }}
                                        </td>
                                        <td class="px-6 py-3 align-middle text-right whitespace-nowrap font-semibold text-gray-900 tabular-nums">
                                            &#8369;{{ number_format((float) $sale->grand_total, 2) }}
                                        </td>
                                        <td class="px-6 py-3 align-middle">
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-200">
                                                {{ \App\Support\SalePaymentMode::label($sale->payment_mode) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 align-middle">
                                            @if ($canEditSales)
                                                <div x-data="{ status: @js($payment), previousStatus: @js($payment) }" class="space-y-2">
                                                    <div class="flex items-center gap-2">
                                                        <form method="POST" action="{{ route('admin.sales.payment', $sale) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <div class="status-select-wrap">
                                                                <select name="payment_status"
                                                                    class="status-select block h-9 w-full rounded-md border border-transparent px-3 text-xs font-semibold shadow-sm ring-1 ring-inset focus:outline-none focus:ring-2 focus:ring-offset-0 disabled:bg-zinc-100 disabled:text-zinc-500 disabled:ring-zinc-200 {{ $paymentSelectClass }}"
                                                                    x-model="status"
                                                                    @change="
                                                                        if (status === previousStatus) return;
                                                                        if (status !== 'partial') {
                                                                            const statusLabel = status === 'unpaid' ? 'Not yet paid' : status.charAt(0).toUpperCase() + status.slice(1);
                                                                            window.openSalesStatusConfirmModal({
                                                                                title: 'Update payment status',
                                                                                message: 'Are you sure you want to change payment status to ' + statusLabel + '?',
                                                                                onConfirm: () => {
                                                                                    previousStatus = status;
                                                                                    $event.target.form.submit();
                                                                                },
                                                                                onCancel: () => {
                                                                                    status = previousStatus;
                                                                                },
                                                                            });
                                                                            return;
                                                                        }
                                                                        previousStatus = status;
                                                                    "
                                                                    {{ $paymentLocked ? 'disabled' : '' }}>
                                                                    <option value="paid">Paid</option>
                                                                    <option value="partial">Partial</option>
                                                                    <option value="unpaid">Not yet paid</option>
                                                                </select>
                                                                <svg class="status-select-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" aria-hidden="true">
                                                                    <path d="M6 8l4 4 4-4" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                                                                </svg>
                                                            </div>

                                                            <input type="hidden" name="amount_paid" value="{{ (float) ($sale->amount_paid ?? 0) }}">
                                                        </form>

                                                        @if ($paymentLocked)
                                                            <span class="locked-chip">{{ $paymentLockLabel }}</span>
                                                        @endif
                                                    </div>

                                                    @if (! $paymentLocked)
                                                        <div x-show="status === 'partial'" class="space-y-2">
                                                            <form method="POST" action="{{ route('admin.sales.payment', $sale) }}" class="flex items-center gap-2">
                                                                @csrf
                                                                @method('PATCH')
                                                                <input type="hidden" name="payment_status" value="partial">
                                                                <input type="number" min="0" step="0.01" name="amount_paid" value="{{ (float) ($sale->amount_paid ?? 0) }}"
                                                                    class="block h-9 w-36 rounded-md border-black/20 bg-white px-3 text-xs shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                                                    placeholder="Amount paid">
                                                                <button type="submit"
                                                                    class="inline-flex h-9 items-center rounded-md bg-white px-3 text-xs font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                                                                    Save
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 ring-1 ring-inset ring-zinc-200">
                                                    {{ $payment === 'unpaid' ? 'Not yet paid' : ucfirst((string) $payment) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 align-middle">
                                            @if ($canEditSales && ! $saleStatusLocked)
                                                <form method="POST" action="{{ route('admin.sales.status', $sale) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <div class="status-select-wrap">
                                                        <select name="sale_status"
                                                            class="status-select block h-9 w-full rounded-md border border-transparent px-3 text-xs font-semibold shadow-sm ring-1 ring-inset focus:outline-none focus:ring-2 focus:ring-offset-0 {{ $saleStatusClass }}"
                                                            data-previous-status="{{ $saleStatus }}"
                                                            onchange="const previousStatus = this.dataset.previousStatus; if (this.value === previousStatus) return; const nextStatusLabel = this.options[this.selectedIndex].text; window.openSalesStatusConfirmModal({ title: 'Update sale status', message: 'Are you sure you want to change sale status to ' + nextStatusLabel + '?', onConfirm: () => { this.dataset.previousStatus = this.value; this.form.submit(); }, onCancel: () => { this.value = previousStatus; } });">
                                                            <option value="completed" {{ $saleStatus === 'completed' ? 'selected' : '' }}>Completed</option>
                                                            <option value="processing" {{ $saleStatus === 'processing' ? 'selected' : '' }}>Processing</option>
                                                        </select>
                                                        <svg class="status-select-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" aria-hidden="true">
                                                            <path d="M6 8l4 4 4-4" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </div>
                                                </form>
                                            @else
                                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $saleStatusClass }}">
                                                    {{ ucfirst($effectiveSaleStatus) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 align-middle text-right whitespace-nowrap">
                                            <div class="inline-flex items-center justify-end gap-2">
                                                <a href="{{ route('admin.sales.receipt', $sale) }}"
                                                    class="inline-flex h-9 items-center justify-center rounded-md bg-white px-3 text-xs font-semibold text-emerald-700 shadow-sm ring-1 ring-inset ring-emerald-600/30 hover:bg-emerald-50">
                                                    Receipt
                                                </a>
                                                <a href="{{ route('admin.sales.show', $sale) }}"
                                                    class="inline-flex h-9 items-center justify-center rounded-md bg-white px-3 text-xs font-semibold text-blue-700 shadow-sm ring-1 ring-inset ring-blue-600/30 hover:bg-blue-50">
                                                    View
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-black/10">
                        {{ $sales->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div id="sales-status-confirm-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl ring-1 ring-black/10">
            <h3 id="sales-status-confirm-title" class="text-base font-semibold text-gray-900">Confirm action</h3>
            <p id="sales-status-confirm-message" class="mt-2 text-sm text-gray-600">Are you sure you want to continue?</p>

            <div class="mt-5 flex items-center justify-end gap-2">
                <button id="sales-status-confirm-cancel" type="button"
                    class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                    Cancel
                </button>
                <button id="sales-status-confirm-accept" type="button"
                    class="inline-flex items-center rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">
                    Confirm
                </button>
            </div>
        </div>
    </div>

    <style>
        .sales-filter-form {
            display: grid;
            gap: 0.75rem;
            align-items: end;
            grid-template-columns: 1fr;
        }

        .sales-filter-field {
            min-width: 0;
        }

        .sales-filter-btn {
            height: 2.5rem;
        }

        .sales-search-wrap {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .sales-search-btn {
            height: 2.5rem;
            white-space: nowrap;
        }

        @media (min-width: 1024px) {
            .sales-filter-form {
                grid-template-columns: minmax(0, 1.25fr) minmax(0, 0.9fr) minmax(0, 1.2fr) 9.5rem 8.5rem;
            }

            .sales-search-wrap .sales-search-btn {
                min-width: 5.75rem;
            }
        }

        .status-select-wrap {
            position: relative;
            width: 9rem;
        }

        .status-select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding-right: 2rem;
        }

        .status-select-icon {
            pointer-events: none;
            position: absolute;
            right: 0.55rem;
            top: 50%;
            width: 0.95rem;
            height: 0.95rem;
            transform: translateY(-50%);
            opacity: 0.75;
        }

        .locked-chip {
            display: inline-flex;
            align-items: center;
            height: 1.5rem;
            border-radius: 9999px;
            padding: 0 0.55rem;
            font-size: 0.6875rem;
            font-weight: 600;
            line-height: 1;
            color: rgb(82 82 91);
            background: rgb(244 244 245);
            border: 1px solid rgb(228 228 231);
        }
    </style>

    <script>
        (() => {
            const modal = document.getElementById('sales-status-confirm-modal');
            const titleEl = document.getElementById('sales-status-confirm-title');
            const messageEl = document.getElementById('sales-status-confirm-message');
            const cancelBtn = document.getElementById('sales-status-confirm-cancel');
            const acceptBtn = document.getElementById('sales-status-confirm-accept');

            if (!modal || !titleEl || !messageEl || !cancelBtn || !acceptBtn) return;

            let currentAction = null;
            let lastFocusedElement = null;

            const closeModal = ({ runCancel = false } = {}) => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');

                const action = currentAction;
                currentAction = null;

                if (runCancel && action && typeof action.onCancel === 'function') {
                    action.onCancel();
                }

                if (lastFocusedElement instanceof HTMLElement) {
                    lastFocusedElement.focus();
                }
                lastFocusedElement = null;
            };

            window.openSalesStatusConfirmModal = ({ title, message, onConfirm, onCancel }) => {
                currentAction = { onConfirm, onCancel };
                lastFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;

                titleEl.textContent = title || 'Confirm action';
                messageEl.textContent = message || 'Are you sure you want to continue?';

                modal.classList.remove('hidden');
                modal.classList.add('flex');
                acceptBtn.focus();
            };

            cancelBtn.addEventListener('click', () => closeModal({ runCancel: true }));

            acceptBtn.addEventListener('click', () => {
                const action = currentAction;
                closeModal();
                if (action && typeof action.onConfirm === 'function') {
                    action.onConfirm();
                }
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal({ runCancel: true });
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal({ runCancel: true });
                }
            });
        })();
    </script>
</x-app-layout>
