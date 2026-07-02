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
 * Regression guard for CODE_REVIEW_FINDINGS.md B2 — rating a completed order credits the artist
 * (net of platform fees) exactly ONCE. Previously each call created a new credit, letting a client
 * inflate an artist's withdrawable balance.
 */
class RatingCreditTest extends TestCase
{
    use RefreshDatabase;

    public function test_rating_credits_the_artist_once_then_is_deduped(): void
    {
        Setting::create(['type' => SettingKey::PLATFORM_FEES->value, 'value' => 20]);

        $order = Order::factory()->create(['cost' => 100]);
        // A date in the past marks the order "complete", so it can be rated.
        OrderDate::forceCreate([
            'order_id' => $order->id,
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'start_time' => '00:00:00',
            'end_time' => '00:00:00',
        ]);

        $payload = ['order_id' => $order->id, 'stars' => 5, 'notes' => 'great'];

        // First rating succeeds and credits the artist.
        $this->actingAs($order->client, 'api')
            ->postJson('/api/rating/store', $payload)
            ->assertStatus(200);

        // Second rating for the same order is refused (no second credit).
        $this->actingAs($order->client, 'api')
            ->postJson('/api/rating/store', $payload)
            ->assertStatus(400);

        $incomes = Transaction::where('user_id', $order->artist_id)->where('type', 'income')->get();
        $this->assertCount(1, $incomes);
        $this->assertEqualsWithDelta(80.0, (float) $incomes->first()->amount, 0.001); // 100 − 20%
    }
}
