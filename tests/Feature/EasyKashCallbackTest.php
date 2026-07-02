<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\UserTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression guard for SECURITY_ISSUES.md C2 — the unsigned GET callback must NOT mark a
 * transaction/order as paid. Previously `?status=PAID&customerReference=...` marked orders paid.
 */
class EasyKashCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_callback_does_not_mark_an_order_paid(): void
    {
        $order = Order::factory()->create(['is_paid' => false]);

        $tx = UserTransaction::forceCreate([
            'order_id' => $order->id,
            'amount' => 100,
            'name' => 'Test',
            'email' => 'test@example.com',
            'mobile' => '966500000000',
            'customer_reference' => '123456',
            'status' => 'pending',
            'is_paid' => false,
        ]);

        $this->get('/api/easykash/callback?status=PAID&customerReference=123456')
            ->assertRedirect('/payment-failed.html');

        $this->assertFalse((bool) $order->fresh()->is_paid);
        $this->assertFalse((bool) $tx->fresh()->is_paid);
    }
}
