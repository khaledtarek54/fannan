<?php

namespace Tests\Feature;

use App\Filament\Resources\ArtistResource;
use App\Filament\Resources\ArtistResource\Pages\ListArtists;
use App\Models\Order;
use App\Models\Rating;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The artists list: review/order-count columns and filters. Admin-panel only; no API impact.
 */
class ArtistListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_each_artist_row_carries_its_review_and_order_counts(): void
    {
        $artist = User::factory()->artist()->create();
        $client = User::factory()->client()->create();

        Rating::create(['client_id' => $client->id, 'artist_id' => $artist->id, 'stars' => 5]);
        Rating::create(['client_id' => $client->id, 'artist_id' => $artist->id, 'stars' => 4]);
        Order::factory()->count(3)->create(['artist_id' => $artist->id]);

        $row = ArtistResource::getEloquentQuery()->findOrFail($artist->id);

        $this->assertSame(2, (int) $row->ratings_count);
        $this->assertSame(3, (int) $row->artist_orders_count);
    }

    public function test_artists_can_be_filtered_by_gender(): void
    {
        $male = User::factory()->artist()->create(['gender' => 'male', 'completed_profile' => 1]);
        $female = User::factory()->artist()->create(['gender' => 'female', 'completed_profile' => 1]);

        Livewire::test(ListArtists::class)
            ->filterTable('gender', 'male')
            ->assertCanSeeTableRecords([$male])
            ->assertCanNotSeeTableRecords([$female]);
    }
}
