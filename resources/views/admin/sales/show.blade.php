<x-app-layout>
    @php($canEditSales = \App\Support\AdminAccess::hasPermission(auth()->user(), 'sales.edit'))
    @php($effectiveSaleStatus = $sale->effectiveStatus())
    @php($statusClass = match ($effectiveSaleStatus) {
        'refunded' => 'bg-violet-50 text-violet-700 ring-violet-200',
        'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'completed' => 'bg-blue-50 text-blue-700 ring-blue-200',
        default => 'bg-gray-50 text-orange-700 ring-orange-200',
    })

    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">Sale {{ $sale->invoice_no ?: ('#'.$sale->id) }}</h2>
                <p class="mt-1 text-sm text-gray-600">
                    {{ optional($sale->sold_at)->format('M d, Y h:i A') ?? '-' }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.sales.receipt', $sale) }}"
                    class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-600/90 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">
                    Preview Receipt
                </a>
                <a href="{{ route('admin.sales') }}"
                    class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-black/15 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                    Back to Sales
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if ($errors->any())
                <div class="rounded-lg bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 ring-1 ring-inset ring-rose-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-black/10 space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-5 gap-4 text-sm">
                    <div>
                        <div class="text-gray-600">Customer</div>
                        <div class="font-medium text-gray-900">{{ $sale->customer_name }}</div>
                        @if ($sale->customer_contact)
                            <div class="mt-1 text-xs text-gray-600">{{ $sale->customer_contact }}</div>
                        @endif
                    </div>
                    <div>
                        <div class="text-gray-600">Payment</div>
                        <div class="font-medium text-gray-900">{{ $sale->payment_status === 'unpaid' ? 'Not yet paid' : ucfirst((string) $sale->payment_status) }}</div>
                        <div class="mt-1 text-xs text-gray-600 tabular-nums">
                            Paid: &#8369;{{ number_format((float) ($sale->amount_paid ?? 0), 2) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-gray-600">Payment mode</div>
                        <div class="font-medium text-gray-900">{{ \App\Support\SalePaymentMode::label($sale->payment_mode) }}</div>
                    </div>
                    <div>
                        <div class="text-gray-600">Sale status</div>
                        <span class="mt-1 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $statusClass }}">
                            {{ ucfirst($effectiveSaleStatus) }}
                        </span>
                    </div>
                    <div>
                        <div class="text-gray-600">Balance</div>
                        <div class="font-semibold text-gray-900 tabular-nums">&#8369;{{ number_format((float) $paymentSummary['remaining'], 2) }}</div>
                    </div>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs font-semibold text-gray-600 bg-gray-50">
                            <tr>
                                <th class="px-4 py-2">Item</th>
                                <th class="px-4 py-2 text-right">Qty</th>
                                <th class="px-4 py-2 text-right">Unit</th>
                                <th class="px-4 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/10">
                            @foreach ($sale->items as $item)
                                <tr>
                                    <td class="px-4 py-2 font-medium text-gray-900">{{ $item->product_name }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums">{{ (int) $item->qty }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums">&#8369;{{ number_format((float) $item->unit_price, 2) }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums">&#8369;{{ number_format((float) $item->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-lg bg-gray-50 px-4 py-3 ring-1 ring-inset ring-black/10">
                        <div class="text-xs font-medium text-gray-900">Notes</div>
                        <div class="mt-1 whitespace-pre-line text-xs text-gray-700">{{ $sale->notes ?: '-' }}</div>
                    </div>
                    <div class="rounded-lg bg-white px-4 py-3 ring-1 ring-inset ring-black/10">
                        <div class="flex items-center justify-between text-sm text-gray-700">
                            <span>Subtotal</span>
                            <span class="tabular-nums">&#8369;{{ number_format((float) $sale->subtotal, 2) }}</span>
                        </div>
                        <div class="mt-1 flex items-center justify-between text-sm text-gray-700">
                            <span>Discount</span>
                            <span class="tabular-nums">-&#8369;{{ number_format((float) $sale->discount, 2) }}</span>
                        </div>
                        <div class="mt-1 flex items-center justify-between text-sm text-gray-700">
                            <span>Net paid</span>
                            <span class="tabular-nums">&#8369;{{ number_format((float) $paymentSummary['net_paid'], 2) }}</span>
                        </div>
                        <div class="mt-1 flex items-center justify-between text-sm text-gray-700">
                            <span>Refunded</span>
                            <span class="tabular-nums">-&#8369;{{ number_format((float) $paymentSummary['refund_total'], 2) }}</span>
                        </div>
                        <div class="mt-3 pt-3 border-t border-black/10 flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-900">Grand total</span>
                            <span class="text-base font-semibold text-gray-900 tabular-nums">&#8369;{{ number_format((float) $sale->grand_total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-black/10">
                    <h3 class="text-base font-semibold text-gray-900">Payment History</h3>
                    <p class="mt-1 text-sm text-gray-600">Each payment and refund is recorded here.</p>

                    @if ($hasLegacyPaymentSnapshot)
                        <div class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-200">
                            Legacy sale: no payment entries yet. Current paid snapshot is &#8369;{{ number_format((float) ($sale->amount_paid ?? 0), 2) }}.
                        </div>
                    @endif

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs font-semibold text-gray-600 bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2">Date</th>
                                    <th class="px-3 py-2">Type</th>
                                    <th class="px-3 py-2">Method</th>
                                    <th class="px-3 py-2 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-black/10">
                                @forelse ($sale->payments as $payment)
                                    <tr>
                                        <td class="px-3 py-2 text-gray-700">{{ optional($payment->paid_at)->format('M d, Y h:i A') ?? '-' }}</td>
                                        <td class="px-3 py-2">
                                            @if ($payment->is_refund)
                                                <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-200">Refund</span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">Payment</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-gray-700">{{ \App\Support\SalePaymentMode::label($payment->method) }}</td>
                                        <td class="px-3 py-2 text-right font-medium tabular-nums {{ $payment->is_refund ? 'text-rose-700' : 'text-gray-900' }}">
                                            {{ $payment->is_refund ? '-' : '' }}&#8369;{{ number_format((float) $payment->amount, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-6 text-center text-sm text-gray-600">No payment records yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-black/10 space-y-4">
                    <h3 class="text-base font-semibold text-gray-900">Actions</h3>
                    <p class="text-sm text-gray-600">Add payments, process refunds, or cancel this sale.</p>

                    @if ($canEditSales)
                        <form method="POST" action="{{ route('admin.sales.payments.store', $sale) }}" class="rounded-lg border border-black/10 p-4 space-y-3">
                            @csrf
                            <div class="text-sm font-semibold text-gray-900">Add Payment</div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <input name="amount" type="number" min="0.01" step="0.01" required placeholder="Amount"
                                    class="rounded-md border-black/20 bg-white px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500">
                                <select name="method" required
                                    class="rounded-md border-black/20 bg-white px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500">
                                    @foreach ($paymentModeGroups as $groupLabel => $modeOptions)
                                        <optgroup label="{{ $groupLabel }}">
                                            @foreach ($modeOptions as $modeValue => $modeLabel)
                                                <option value="{{ $modeValue }}"
                                                    {{ old('method', $sale->payment_mode ?: \App\Support\SalePaymentMode::DEFAULT) === $modeValue ? 'selected' : '' }}>
                                                    {{ $modeLabel }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <input name="reference" type="text" placeholder="Reference (optional)"
                                    class="rounded-md border-black/20 bg-white px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500">
                                <input name="paid_at" type="datetime-local" value="{{ now()->format('Y-m-d\\TH:i') }}"
                                    class="rounded-md border-black/20 bg-white px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500">
                            </div>
                            <input name="notes" type="text" placeholder="Notes (optional)"
                                class="w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500">
                            <button type="submit"
                                class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-600/90">
                                Save Payment
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.sales.refund', $sale) }}" class="rounded-lg border border-black/10 p-4 space-y-3">
                            @csrf
                            <div class="text-sm font-semibold text-gray-900">Refund</div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <input name="amount" type="number" min="0.01" step="0.01" required placeholder="Refund amount"
                                    class="rounded-md border-black/20 bg-white px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500">
                                <select name="method" required
                                    class="rounded-md border-black/20 bg-white px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500">
                                    @foreach ($paymentModeGroups as $groupLabel => $modeOptions)
                                        <optgroup label="{{ $groupLabel }}">
                                            @foreach ($modeOptions as $modeValue => $modeLabel)
                                                <option value="{{ $modeValue }}"
                                                    {{ old('method', $sale->payment_mode ?: \App\Support\SalePaymentMode::DEFAULT) === $modeValue ? 'selected' : '' }}>
                                                    {{ $modeLabel }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <input name="reference" type="text" placeholder="Reference (optional)"
                                    class="rounded-md border-black/20 bg-white px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500">
                                <input name="paid_at" type="datetime-local" value="{{ now()->format('Y-m-d\\TH:i') }}"
                                    class="rounded-md border-black/20 bg-white px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500">
                            </div>
                            <input name="notes" type="text" placeholder="Reason / notes"
                                class="w-full rounded-md border-black/20 bg-white px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="hidden" name="restock_items" value="0">
                                <input type="checkbox" name="restock_items" value="1" class="rounded border-gray-300 text-gray-900 focus:ring-orange-500">
                                Restock items (only if not already restocked)
                            </label>
                            <button type="submit"
                                class="inline-flex items-center rounded-md bg-rose-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-rose-600/90">
                                Process Refund
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.sales.cancel', $sale) }}" class="rounded-lg border border-black/10 p-4 space-y-3">
                            @csrf
                            <div class="text-sm font-semibold text-gray-900">Cancel Sale</div>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="hidden" name="restock_items" value="0">
                                <input type="checkbox" name="restock_items" value="1" checked class="rounded border-gray-300 text-gray-900 focus:ring-orange-500">
                                Restock items when cancelling
                            </label>
                            <button type="submit"
                                class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800"
                                {{ $sale->cancelled_at ? 'disabled' : '' }}>
                                {{ $sale->cancelled_at ? 'Already Cancelled' : 'Cancel Sale' }}
                            </button>
                        </form>
                    @else
                        <div class="rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-600 ring-1 ring-inset ring-black/10">
                            You can view payment history, but only users with Sales edit permission can add payments or process refunds.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
