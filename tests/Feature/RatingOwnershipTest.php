<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression guard for CODE_REVIEW_FINDINGS.md B2 — a client must not be able to rate an order
 * they are not part of (which also credited the artist's wallet). Ownership is enforced before
 * any wallet credit.
 */
class RatingOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_client_cannot_rate_an_order_they_are_not_part_of(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->client()->create(), 'api')
            ->postJson('/api/rating/store', [
                'order_id' => $order->id,
                'stars' => 5,
                'notes' => 'nice',
            ])
            ->assertStatus(403);

        // And no income transaction was created for the artist as a side effect.
        $this->assertDatabaseMissing('transactions', ['user_id' => $order->artist_id, 'type' => 'income']);
    }
}
