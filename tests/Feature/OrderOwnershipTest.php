<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression guards for SECURITY_ISSUES.md H2/H3/H4 — order actions must be limited to the
 * order's participants; a stranger with a valid token must not be able to touch other orders.
 */
class OrderOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_stranger_cannot_reject_someone_elses_order(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->client()->create(), 'api')
            ->postJson('/api/order/reject', ['order_id' => $order->id])
            ->assertStatus(403);
    }

    public function test_a_stranger_cannot_cancel_someone_elses_order(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->client()->create(), 'api')
            ->postJson('/api/order/cancel', ['order_id' => $order->id])
            ->assertStatus(403);
    }

    public function test_a_different_artist_cannot_accept_your_order(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->artist()->create(), 'api')
            ->postJson('/api/order/accept', ['order_id' => $order->id, 'cost' => 50])
            ->assertStatus(403);
    }

    public function test_the_order_client_can_reject_their_own_order(): void
    {
        $order = Order::factory()->create();

        $this->actingAs($order->client, 'api')
            ->postJson('/api/order/reject', ['order_id' => $order->id])
            ->assertStatus(200);
    }
}
