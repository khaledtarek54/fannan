<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Resources\DirectOrderResource\Pages\ListDirectOrders;
use App\Models\Order;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin filtering of direct orders. Admin-panel only; no API impact.
 */
class DirectOrderFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_direct_orders_can_be_filtered_by_current_status(): void
    {
        $admin = User::factory()->admin()->create();

        $accepted = Order::factory()->create();
        $accepted->setStatus(OrderStatus::ACCEPTED->value);

        $pending = Order::factory()->create();
        $pending->setStatus(OrderStatus::NEW->value);

        $this->actingAs($admin);

        Livewire::test(ListDirectOrders::class)
            ->filterTable('status', OrderStatus::ACCEPTED->value)
            ->assertCanSeeTableRecords([$accepted])
            ->assertCanNotSeeTableRecords([$pending]);
    }
}
