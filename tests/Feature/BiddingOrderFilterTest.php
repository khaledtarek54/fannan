<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Resources\BiddingOrderResource\Pages\ListBiddingOrders;
use App\Models\Order;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin filtering of bidding orders. Admin-panel only; no API impact.
 */
class BiddingOrderFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_bidding_orders_can_be_filtered_by_current_status(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $accepted = Order::factory()->bidding()->create();
        $accepted->setStatus(OrderStatus::ACCEPTED->value);

        $pending = Order::factory()->bidding()->create();
        $pending->setStatus(OrderStatus::NEW->value);

        Livewire::test(ListBiddingOrders::class)
            ->filterTable('status', OrderStatus::ACCEPTED->value)
            ->assertCanSeeTableRecords([$accepted])
            ->assertCanNotSeeTableRecords([$pending]);
    }
}
