<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ ($quotation['quotation_name'] ?? null) ?: 'Quotation '.($quoteId ? '#'.$quoteId : '') }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
            margin: 24px;
        }

        .header {
            margin-bottom: 14px;
        }

        .title {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }

        .meta {
            margin-top: 4px;
            color: #4b5563;
            font-size: 11px;
        }

        .info-table,
        .items-table,
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        .items-table {
            margin-top: 12px;
        }

        .items-table th {
            text-align: left;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 7px 8px;
            font-size: 11px;
        }

        .items-table td {
            border: 1px solid #e5e7eb;
            padding: 7px 8px;
            font-size: 11px;
        }

        .text-right {
            text-align: right;
        }

        .section {
            color: #6b7280;
            font-size: 10px;
            margin-top: 2px;
        }

        .notes {
            margin-top: 12px;
            border: 1px solid #d1d5db;
            padding: 8px;
            min-height: 38px;
        }

        .totals-wrap {
            margin-top: 12px;
            width: 300px;
            margin-left: auto;
        }

        .totals-table td {
            padding: 4px 0;
            font-size: 11px;
        }

        .grand {
            border-top: 1px solid #111827;
            font-weight: 700;
            font-size: 13px;
            padding-top: 6px !important;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">{{ ($quotation['quotation_name'] ?? null) ?: 'Quotation'.($quoteId ? ' #'.$quoteId : '') }}</h1>
        <div class="meta">
            Ref: {{ $quoteId ? '#'.$quoteId : 'Draft' }} | Date: {{ optional($quoteDate)->format('M d, Y h:i A') ?? now()->format('M d, Y h:i A') }}
        </div>
    </div>

    <table class="info-table">
        <tr>
            <td>
                <strong>Customer:</strong> {{ $quotation['customer_name'] ?: 'Walk-in Customer' }}
            </td>
            <td class="text-right">
                <strong>Contact:</strong> {{ $quotation['customer_contact'] ?: '-' }}
            </td>
        </tr>
    </table>

    <table class="items-table">
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
            @forelse (($quotation['items'] ?? []) as $item)
                <tr>
                    <td>{{ $item['product_name'] ?? '-' }}</td>
                    <td>{{ $item['section'] ?? '-' }}</td>
                    <td class="text-right">{{ $item['qty'] ?? 0 }}</td>
                    <td class="text-right">&#8369;{{ number_format((float) ($item['unit_price'] ?? 0), 2) }}</td>
                    <td class="text-right">&#8369;{{ number_format((float) ($item['line_total'] ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No items selected.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="notes">
        <strong>Notes:</strong><br>
        {{ $quotation['notes'] ?: '-' }}
    </div>

    <div class="totals-wrap">
        <table class="totals-table">
            <tr>
                <td>Subtotal</td>
                <td class="text-right">&#8369;{{ number_format((float) ($quotation['subtotal'] ?? 0), 2) }}</td>
            </tr>
            <tr>
                <td>Labor / Service fee</td>
                <td class="text-right">&#8369;{{ number_format((float) ($quotation['labor_fee'] ?? 0), 2) }}</td>
            </tr>
            <tr>
                <td>Discount</td>
                <td class="text-right">-&#8369;{{ number_format((float) ($quotation['discount_amount'] ?? 0), 2) }}</td>
            </tr>
            <tr>
                <td class="grand">Grand total</td>
                <td class="text-right grand">&#8369;{{ number_format((float) ($quotation['grand_total'] ?? 0), 2) }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
