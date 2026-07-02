<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->number ?? $order->id }}</title>
    <style>
        body { font-family: Arial, "Segoe UI", sans-serif; color: #222; margin: 0; padding: 24px; }
        .wrap { max-width: 720px; margin: 0 auto; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #6b21a8; padding-bottom: 12px; }
        .brand { color: #6b21a8; font-size: 26px; font-weight: bold; letter-spacing: 1px; }
        .muted { color: #666; font-size: 13px; }
        .meta { margin: 18px 0; display: flex; justify-content: space-between; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 14px; }
        th, td { text-align: left; padding: 8px 6px; border-bottom: 1px solid #eee; }
        .totals { margin-top: 16px; width: 100%; font-size: 14px; }
        .totals td { border: none; padding: 4px 6px; }
        .totals .grand { font-weight: bold; font-size: 16px; border-top: 2px solid #6b21a8; }
        .right { text-align: right; }
        .status { display: inline-block; padding: 2px 10px; border-radius: 12px; background: #f3e8ff; color: #6b21a8; font-size: 12px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="head">
        <div>
            <div class="brand">Fannan</div>
            <div class="muted">Invoice</div>
        </div>
        <div class="right">
            <div><strong>#{{ $order->number ?? $order->id }}</strong></div>
            <div class="muted">{{ optional($order->created_at)->format('Y-m-d') }}</div>
            <div class="status">{{ $order->is_paid ? 'Paid' : 'Unpaid' }}</div>
        </div>
    </div>

    <div class="meta">
        <div>
            <div class="muted">Client</div>
            <div>{{ $order->client?->name }}</div>
        </div>
        <div>
            <div class="muted">Artist</div>
            <div>{{ $order->artist?->name }}</div>
        </div>
    </div>

    <table>
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
            <tr><td class="muted">Date</td><td class="right muted">{{ $d->start_date }} → {{ $d->end_date }}</td></tr>
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

    <p class="muted" style="margin-top:24px">Thank you for using Fannan.</p>
</div>
</body>
</html>
