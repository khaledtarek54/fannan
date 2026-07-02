<?php

namespace Tests\Feature;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\CouponUser;
use App\Models\Order;
use App\Models\OrderDate;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guard for BUSINESS_LOGIC_ISSUES.md BL7 — a coupon is consumed only when the order actually
 * COMPLETES (a real sale), not at the quote step (where it used to be burned even without payment).
 */
class CouponConsumptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_coupon_is_consumed_only_when_the_order_completes(): void
    {
        $client = User::factory()->client()->create();
        $coupon = Coupon::create([
            'type' => CouponType::FIXED->value,
            'amount' => 10,
            'code' => 'SAVE10',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
        ]);

        $order = Order::factory()->create([
            'client_id' => $client->id,
            'is_paid' => true,
            'coupon_id' => $coupon->id,
            'coupon_amount' => 10,
        ]);
        OrderDate::forceCreate([
            'order_id' => $order->id,
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'start_time' => '00:00:00',
            'end_time' => '00:00:00',
        ]);

        // Not consumed yet — the order hasn't completed.
        $this->assertDatabaseMissing('coupon_users', ['user_id' => $client->id, 'coupon_id' => $coupon->id]);

        app(OrderService::class)->notifyCompletedOrders();

        // Consumed exactly once on completion.
        $this->assertDatabaseHas('coupon_users', ['user_id' => $client->id, 'coupon_id' => $coupon->id]);
        $this->assertEquals(1, CouponUser::where('coupon_id', $coupon->id)->count());
    }
}
