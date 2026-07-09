<?php

namespace Tests\Feature;

use App\Filament\Resources\ClientResource;
use App\Filament\Resources\ClientResource\Pages\ListClients;
use App\Models\City;
use App\Models\Order;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The clients list: order-count column, city column (via the real relation), and filters.
 * Admin-panel only; no API impact.
 */
class ClientListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_each_client_row_carries_its_order_count(): void
    {
        $client = User::factory()->client()->create(['completed_profile' => 1]);
        Order::factory()->count(2)->create(['client_id' => $client->id]);

        $row = ClientResource::getEloquentQuery()->findOrFail($client->id);

        $this->assertSame(2, (int) $row->client_orders_count);
    }

    public function test_the_city_column_resolves_the_related_city_name(): void
    {
        // Regression: the column read `city.name` (a string attribute → blank); it must read the
        // cityRelation belongsTo instead.
        $city = City::create(['name' => 'Riyadh']);
        $client = User::factory()->client()->create(['completed_profile' => 1, 'city_id' => $city->id]);

        Livewire::test(ListClients::class)
            ->assertTableColumnFormattedStateSet('cityRelation.name', 'Riyadh', $client);
    }

    public function test_clients_can_be_filtered_by_city(): void
    {
        $riyadh = City::create(['name' => 'Riyadh']);
        $jeddah = City::create(['name' => 'Jeddah']);
        $a = User::factory()->client()->create(['completed_profile' => 1, 'city_id' => $riyadh->id]);
        $b = User::factory()->client()->create(['completed_profile' => 1, 'city_id' => $jeddah->id]);

        Livewire::test(ListClients::class)
            ->filterTable('city_id', $riyadh->id)
            ->assertCanSeeTableRecords([$a])
            ->assertCanNotSeeTableRecords([$b]);
    }
}
