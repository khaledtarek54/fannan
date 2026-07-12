<?php

namespace Tests\Feature;

use App\Filament\Widgets\OrdersChartWidget;
use App\Filament\Widgets\PlatformStatsWidget;
use App\Models\Order;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Dashboard widgets. Admin-panel only; no API impact.
 */
class DashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_orders_chart_counts_orders_per_month_in_one_pass(): void
    {
        // 2 this month, 1 last month — the grouped query + zero-fill must bucket them correctly.
        Order::factory()->count(2)->create(['created_at' => now()->startOfMonth()->addDay()]);
        Order::factory()->create(['created_at' => now()->subMonth()->startOfMonth()->addDay()]);

        $getData = new ReflectionMethod(OrdersChartWidget::class, 'getData');
        $getData->setAccessible(true);
        $data = $getData->invoke(new OrdersChartWidget());

        $counts = $data['datasets'][0]['data'];

        $this->assertCount(6, $counts);
        $this->assertSame(2, $counts[5], 'current month bucket');
        $this->assertSame(1, $counts[4], 'previous month bucket');
        $this->assertSame(now()->format('M Y'), end($data['labels']));
    }

    public function test_orders_chart_has_no_duplicate_or_missing_months_on_a_month_end_date(): void
    {
        // Regression for the Carbon subMonths overflow: on the 31st, day-of-month subtraction skips
        // short months and duplicates others. Anchoring on startOfMonth() must give 6 distinct months.
        Carbon::setTestNow('2025-01-31');

        Order::factory()->create(['created_at' => '2024-11-15']);          // Nov 2024
        Order::factory()->count(2)->create(['created_at' => '2025-01-10']); // Jan 2025

        $getData = new ReflectionMethod(OrdersChartWidget::class, 'getData');
        $getData->setAccessible(true);
        $data = $getData->invoke(new OrdersChartWidget());

        $labels = $data['labels'];
        $counts = $data['datasets'][0]['data'];

        $this->assertSame(6, count(array_unique($labels)), 'six distinct months, no duplicates/gaps');
        $this->assertSame(['Aug 2024', 'Sep 2024', 'Oct 2024', 'Nov 2024', 'Dec 2024', 'Jan 2025'], $labels);
        $this->assertSame(1, $counts[3], 'Nov 2024 bucket');
        $this->assertSame(2, $counts[5], 'Jan 2025 bucket');

        Carbon::setTestNow();
    }

    public function test_platform_stats_widget_renders(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Order::factory()->count(3)->create(['is_paid' => true]);

        Livewire::test(PlatformStatsWidget::class)->assertSuccessful();
    }
}
