<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $order->number ?? $order->id }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #222; font-size: 13px; }
        .brand { color: #6b21a8; font-size: 26px; font-weight: bold; letter-spacing: 1px; }
        .muted { color: #666; font-size: 12px; }
        .right { text-align: right; }
        .head { width: 100%; border-bottom: 2px solid #6b21a8; padding-bottom: 8px; }
        .head td { vertical-align: top; padding: 0; }
        .meta { width: 100%; margin: 18px 0; }
        .meta td { width: 50%; vertical-align: top; padding: 0; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.lines th, table.lines td { text-align: left; padding: 7px 6px; border-bottom: 1px solid #eee; }
        table.totals { width: 55%; margin-top: 16px; margin-left: 45%; border-collapse: collapse; }
        table.totals td { padding: 4px 6px; }
        table.totals .grand td { font-weight: bold; font-size: 15px; border-top: 2px solid #6b21a8; }
        .status { display: inline-block; padding: 2px 10px; border-radius: 10px; background: #f3e8ff; color: #6b21a8; font-size: 11px; }
    </style>
</head>
<body>
    <table class="head">
        <tr>
            <td>
                <div class="brand">Fannan</div>
                <div class="muted">Invoice</div>
            </td>
            <td class="right">
                <div><strong>#{{ $order->number ?? $order->id }}</strong></div>
                <div class="muted">{{ optional($order->created_at)->format('Y-m-d') }}</div>
                <div class="status">{{ $order->is_paid ? 'Paid' : 'Unpaid' }}</div>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td>
                <div class="muted">Client</div>
                <div>{{ $order->client?->name }}</div>
            </td>
            <td>
                <div class="muted">Artist</div>
                <div>{{ $order->artist?->name }}</div>
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
        <tr><th>Service</th><th class="right">Details</th></tr>
        </thead>
        <tbody>
        @forelse($order->categories as $line)
            <tr>
                <td>{{ $line->subcategory?->name ?? '—' }}</td>
                <td class="right">{{ $line->budget ?? '' }}</td>
            </tr>
        @empty
            <tr><td>{{ $order->name ?? 'Service' }}</td><td class="right"></td></tr>
        @endforelse
        @foreach($order->dates as $d)
            <tr><td class="muted">Date</td><td class="right muted">{{ $d->start_date }} &rarr; {{ $d->end_date }}</td></tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Cost</td><td class="right">{{ number_format($breakdown['cost'], 2) }}</td></tr>
        <tr><td>Tax</td><td class="right">{{ number_format($breakdown['tax'], 2) }}</td></tr>
        <tr><td>VAT</td><td class="right">{{ number_format($breakdown['vat'], 2) }}</td></tr>
        <tr><td>Discount</td><td class="right">- {{ number_format($breakdown['discount'], 2) }}</td></tr>
        <tr class="grand"><td>Total</td><td class="right">{{ number_format($breakdown['total_cost'], 2) }}</td></tr>
    </table>

    <p class="muted" style="margin-top:28px">Thank you for using Fannan.</p>
</body>
</html>
