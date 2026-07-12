<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

/**
 * Orders created per month over the last 6 months — a quick volume trend on the dashboard.
 */
class OrdersChartWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('app.orders');
    }

    protected function getData(): array
    {
        // [DASH-P3] one grouped, range-filtered query (index-friendly on created_at) instead of six
        // per-render count() queries with whereYear/whereMonth (which couldn't use an index).
        // Anchor on startOfMonth() BEFORE subMonths: subtracting months from day-29..31 overflows
        // (e.g. Jul-31 minus one month = Jul-01, not Jun), which duplicates/drops buckets. Day 1
        // exists in every month, so this is safe.
        $since = now()->startOfMonth()->subMonths(5);
        $perMonth = Order::query()
            ->where('created_at', '>=', $since)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $labels = [];
        $counts = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i);
            $labels[] = $month->format('M Y');
            $counts[] = (int) ($perMonth[$month->format('Y-m')] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => __('app.orders'),
                    'data' => $counts,
                    'backgroundColor' => 'rgba(168, 85, 247, 0.5)', // brand purple
                    'borderColor' => 'rgb(168, 85, 247)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
