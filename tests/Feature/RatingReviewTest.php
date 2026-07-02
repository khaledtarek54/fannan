<?php

namespace Tests\Feature;

use App\Enums\SettingKey;
use App\Models\Order;
use App\Models\OrderDate;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guard for BUSINESS_LOGIC_ISSUES.md BL1 — payout moved to order completion, so rating is now a
 * REVIEW ONLY: it records the rating but must NOT credit the artist's wallet. Dedup still applies.
 */
class RatingReviewTest extends TestCase
{
    use RefreshDatabase;

    private function completedOrder(): Order
    {
        $order = Order::factory()->create(['cost' => 100]);
        OrderDate::forceCreate([
            'order_id' => $order->id,
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'start_time' => '00:00:00',
            'end_time' => '00:00:00',
        ]);

        return $order;
    }

    public function test_rating_records_a_review_but_does_not_credit_the_artist(): void
    {
        Setting::create(['type' => SettingKey::PLATFORM_FEES->value, 'value' => 20]);
        $order = $this->completedOrder();

        $this->actingAs($order->client, 'api')
            ->postJson('/api/rating/store', ['order_id' => $order->id, 'stars' => 5, 'notes' => 'great'])
            ->assertStatus(200);

        $this->assertDatabaseHas('ratings', ['client_id' => $order->client_id, 'model_id' => $order->id]);
        $this->assertEquals(0, Transaction::where('user_id', $order->artist_id)->where('type', 'income')->count());
    }

    public function test_a_second_rating_for_the_same_order_is_refused(): void
    {
        $order = $this->completedOrder();

        $this->actingAs($order->client, 'api')
            ->postJson('/api/rating/store', ['order_id' => $order->id, 'stars' => 5])
            ->assertStatus(200);

        $this->actingAs($order->client, 'api')
            ->postJson('/api/rating/store', ['order_id' => $order->id, 'stars' => 4])
            ->assertStatus(400);
    }
}
