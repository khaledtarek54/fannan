<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\SettingKey;
use App\Models\Order;
use App\Models\OrderDate;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards for BUSINESS_LOGIC_ISSUES.md BL1-BL6 — artists are paid on order COMPLETION (escrow):
 * net of the platform fee, exactly once, and ONLY for paid orders. Rating is not involved.
 */
class OrderSettlementTest extends TestCase
{
    use RefreshDatabase;

    private function withPastDate(Order $order): Order
    {
        OrderDate::forceCreate([
            'order_id' => $order->id,
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'start_time' => '00:00:00',
            'end_time' => '00:00:00',
        ]);

        return $order;
    }

    public function test_a_completed_paid_order_credits_the_artist_net_of_fee_exactly_once(): void
    {
        Setting::create(['type' => SettingKey::PLATFORM_FEES->value, 'value' => 20]);
        $order = $this->withPastDate(Order::factory()->create(['cost' => 100, 'is_paid' => true]));

        app(OrderService::class)->notifyCompletedOrders();

        $incomes = Transaction::where('user_id', $order->artist_id)->where('type', 'income')->get();
        $this->assertCount(1, $incomes);
        $this->assertEqualsWithDelta(80.0, (float) $incomes->first()->amount, 0.001); // 100 − 20%

        $this->assertTrue(
            $order->fresh()->statuses()->where('name', OrderStatus::COMPLETED->value)->exists()
        );

        // Re-running the nightly completion job must not pay the artist again.
        app(OrderService::class)->notifyCompletedOrders();
        $this->assertEquals(1, Transaction::where('user_id', $order->artist_id)->where('type', 'income')->count());
    }

    public function test_a_legacy_aliased_payout_prevents_a_second_credit(): void
    {
        Setting::create(['type' => SettingKey::PLATFORM_FEES->value, 'value' => 20]);
        $order = $this->withPastDate(Order::factory()->create(['cost' => 100, 'is_paid' => true]));

        // A payout already exists for this order, but stored under the legacy morph alias 'ORDER'
        // (older code wrote this instead of the FQCN). The completion job must recognise it and NOT
        // pay the artist again. Guards the settleOrder double-pay bug found on prod (order 273).
        Transaction::forceCreate([
            'user_id' => $order->artist_id,
            'type' => 'income',
            'amount' => 80,
            'model_type' => 'ORDER',
            'model_id' => $order->id,
            'is_completed' => false,
        ]);

        app(OrderService::class)->notifyCompletedOrders();

        $this->assertEquals(
            1,
            Transaction::where('model_id', $order->id)->where('type', 'income')->count(),
            'legacy ORDER-aliased payout should block a second credit'
        );
    }

    public function test_an_unpaid_order_is_never_settled(): void
    {
        Setting::create(['type' => SettingKey::PLATFORM_FEES->value, 'value' => 20]);
        $order = $this->withPastDate(Order::factory()->create(['cost' => 100, 'is_paid' => false]));

        app(OrderService::class)->notifyCompletedOrders();

        $this->assertEquals(0, Transaction::where('user_id', $order->artist_id)->where('type', 'income')->count());
    }
}
