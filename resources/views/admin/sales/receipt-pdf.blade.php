<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt {{ $sale->invoice_no ?: ('#'.$sale->id) }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
            margin: 24px;
        }

        .top {
            display: table;
            width: 100%;
            margin-bottom: 14px;
        }

        .top-left,
        .top-right {
            display: table-cell;
            vertical-align: top;
        }

        .top-right {
            text-align: right;
            font-size: 11px;
            color: #374151;
        }

        .title {
            margin: 0;
            font-size: 21px;
            font-weight: 700;
            color: #111827;
        }

        .store-name {
            margin-top: 2px;
            font-size: 12px;
            color: #4b5563;
        }

        .customer-box {
            margin-top: 8px;
            margin-bottom: 10px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            padding: 10px;
        }

        .customer-line {
            margin: 0;
            line-height: 1.45;
        }

        .items-table,
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th {
            text-align: left;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 7px 8px;
            font-size: 11px;
            color: #111827;
        }

        .items-table td {
            border: 1px solid #e5e7eb;
            padding: 7px 8px;
            font-size: 11px;
        }

        .text-right {
            text-align: right;
        }

        .totals-wrap {
            width: 300px;
            margin-left: auto;
            margin-top: 12px;
        }

        .totals-table td {
            padding: 4px 0;
            font-size: 11px;
        }

        .grand {
            border-top: 1px solid #111827;
            font-size: 13px;
            font-weight: 700;
            padding-top: 6px !important;
        }

        .notes {
            margin-top: 12px;
            border: 1px solid #e5e7eb;
            padding: 8px;
            min-height: 36px;
            white-space: pre-line;
            font-size: 11px;
        }

        .footer {
            margin-top: 18px;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    @php($invoice = $sale->invoice_no ?: ('SALE-'.$sale->id))
    @php($paymentStatus = $sale->payment_status === 'unpaid' ? 'Not yet paid' : ucfirst((string) $sale->payment_status))
    @php($paymentMode = \App\Support\SalePaymentMode::label($sale->payment_mode))
    @php($effectiveSaleStatus = $sale->effectiveStatus())
    @php($grandTotal = (float) ($sale->grand_total ?? 0))
    @php($amountPaid = (float) ($sale->amount_paid ?? 0))
    @php($refundTotal = (float) collect($sale->payments ?? [])->where('is_refund', true)->sum('amount'))
    @php($balance = max(0, $grandTotal - $amountPaid))

    <div class="top">
        <div class="top-left">
            <h1 class="title">Sales Receipt</h1>
            <div class="store-name">{{ $storeName }}</div>
        </div>
        <div class="top-right">
            <div><strong>Invoice:</strong> {{ $invoice }}</div>
            <div><strong>Sold At:</strong> {{ optional($sale->sold_at)->format('M d, Y h:i A') ?? '-' }}</div>
            <div><strong>Printed:</strong> {{ now()->format('M d, Y h:i A') }}</div>
        </div>
    </div>

    <div class="customer-box">
        <p class="customer-line"><strong>Customer:</strong> {{ $sale->customer_name ?: 'Walk-in Customer' }}</p>
        <p class="customer-line"><strong>Contact:</strong> {{ $sale->customer_contact ?: '-' }}</p>
        <p class="customer-line"><strong>Payment Mode:</strong> {{ $paymentMode }}</p>
        <p class="customer-line"><strong>Payment Status:</strong> {{ $paymentStatus }}</p>
        <p class="customer-line"><strong>Sale Status:</strong> {{ ucfirst($effectiveSaleStatus) }}</p>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sale->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td class="text-right">{{ (int) $item->qty }}</td>
                    <td class="text-right">&#8369;{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="text-right">&#8369;{{ number_format((float) $item->line_total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No items found for this sale.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals-wrap">
        <table class="totals-table">
            <tr>
                <td>Subtotal</td>
                <td class="text-right">&#8369;{{ number_format((float) ($sale->subtotal ?? 0), 2) }}</td>
            </tr>
            <tr>
                <td>Discount</td>
                <td class="text-right">-&#8369;{{ number_format((float) ($sale->discount ?? 0), 2) }}</td>
            </tr>
            <tr>
                <td>Amount Paid</td>
                <td class="text-right">&#8369;{{ number_format($amountPaid, 2) }}</td>
            </tr>
            <tr>
                <td>Refunded</td>
                <td class="text-right">-&#8369;{{ number_format($refundTotal, 2) }}</td>
            </tr>
            <tr>
                <td>Balance</td>
                <td class="text-right">&#8369;{{ number_format($balance, 2) }}</td>
            </tr>
            <tr>
                <td class="grand">Grand Total</td>
                <td class="text-right grand">&#8369;{{ number_format($grandTotal, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="notes">
        <strong>Notes:</strong><br>
        {{ $sale->notes ?: '-' }}
    </div>

    <div class="footer">
        This document serves as the customer receipt for the recorded sale.
    </div>
</body>
</html>
