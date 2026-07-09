<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Support;
use App\Models\Transaction;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The local demo-data seeder must run without error and populate the panel (guards against a future
 * fillable/schema change silently breaking it). Dev tooling; no API impact.
 */
class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_explorable_demo_data(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertGreaterThan(0, Order::count());
        $this->assertGreaterThan(0, Order::where('is_paid', true)->count(), 'some paid orders for GMV');
        $this->assertGreaterThan(0, Transaction::where('type', 'withdraw')->where('is_completed', 0)->count(), 'pending payouts');
        $this->assertSame(3, Coupon::count(), 'active / scheduled / expired coupons');
        $this->assertGreaterThan(0, Support::where('is_complete', 0)->count(), 'open tickets');
    }
}
