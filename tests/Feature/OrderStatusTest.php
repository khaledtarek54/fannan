<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guard for SECURITY_ISSUES.md M2 — the order-status lookup did not exist and was built here with an
 * ownership check: a caller can only read the status/price of orders they are a participant in.
 */
class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_participant_can_read_their_order_status(): void
    {
        $order = Order::factory()->create();
        $order->setStatus(OrderStatus::ACCEPTED->value);

        $this->actingAs($order->client, 'api')
            ->postJson('/api/order/status', ['order_id' => $order->id])
            ->assertStatus(200)
            ->assertJsonPath('data.status', OrderStatus::ACCEPTED->value);
    }

    public function test_a_non_participant_cannot_read_the_order_status(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->client()->create(), 'api')
            ->postJson('/api/order/status', ['order_id' => $order->id])
            ->assertStatus(403);
    }
}
