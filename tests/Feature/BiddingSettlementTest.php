<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\SettingKey;
use App\Models\BiddingOrderArtist;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderDate;
use App\Models\Setting;
use App\Models\SubCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guard for BUSINESS_LOGIC_ISSUES.md BL6 — a completed, paid BIDDING order pays EACH accepted
 * artist their own bid amount (net of platform fee). Bidding orders used to be excluded from
 * completion entirely and never paid out.
 */
class BiddingSettlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_completed_bidding_order_pays_each_accepted_artist_their_bid_net_of_fee(): void
    {
        Setting::create(['type' => SettingKey::PLATFORM_FEES->value, 'value' => 20]);
        $category = Category::create(['name' => 'Art']);
        $sub1 = SubCategory::create(['category_id' => $category->id, 'name' => 'Logo']);
        $sub2 = SubCategory::create(['category_id' => $category->id, 'name' => 'Poster']);

        $order = Order::factory()->bidding()->create(['is_paid' => true]);
        OrderDate::forceCreate([
            'order_id' => $order->id,
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'start_time' => '00:00:00',
            'end_time' => '00:00:00',
        ]);

        $artist1 = User::factory()->artist()->create();
        $artist2 = User::factory()->artist()->create();

        foreach ([[$artist1, $sub1, 100], [$artist2, $sub2, 50]] as [$artist, $sub, $cost]) {
            $bid = BiddingOrderArtist::create([
                'order_id' => $order->id,
                'artist_id' => $artist->id,
                'subcategory_id' => $sub->id,
                'cost' => $cost,
                'is_accepted' => 1,
            ]);
            $bid->setStatus(OrderStatus::ACCEPTED->value);
        }

        app(OrderService::class)->notifyCompletedOrders();

        // Each artist is paid their own bid minus the 20% fee.
        $this->assertEqualsWithDelta(80.0, (float) Transaction::where('user_id', $artist1->id)->where('type', 'income')->sum('amount'), 0.001);
        $this->assertEqualsWithDelta(40.0, (float) Transaction::where('user_id', $artist2->id)->where('type', 'income')->sum('amount'), 0.001);
    }
}
