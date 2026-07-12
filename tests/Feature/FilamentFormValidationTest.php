<?php

namespace Tests\Feature;

use App\Filament\Resources\AddressResource\Pages\CreateAddress;
use App\Filament\Resources\ArtistResource\Pages\CreateArtist;
use App\Filament\Resources\ArtistResource\Pages\EditArtist;
use App\Filament\Resources\ClientResource\Pages\CreateClient;
use App\Filament\Resources\ClientResource\Pages\EditClient;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Field-name & validation sweep across the Filament resources. Each test asserts a specific
 * field's rule; other required fields may still error (we only check the field under test, the
 * same approach AdFormValidationTest uses). Admin-panel only — no mobile API impact.
 */
class FilamentFormValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create());
    }

    // --- AddressResource: latitude/longitude were only ->required() (a double column) ---

    public function test_a_non_numeric_latitude_is_rejected(): void
    {
        Livewire::test(CreateAddress::class)
            ->fillForm(['latitude' => 'abc'])
            ->call('create')
            ->assertHasFormErrors(['latitude']);
    }

    public function test_an_out_of_range_latitude_is_rejected(): void
    {
        // Valid latitudes are -90..90; 200 is off the globe.
        Livewire::test(CreateAddress::class)
            ->fillForm(['latitude' => 200])
            ->call('create')
            ->assertHasFormErrors(['latitude']);
    }

    public function test_an_out_of_range_longitude_is_rejected(): void
    {
        // Valid longitudes are -180..180.
        Livewire::test(CreateAddress::class)
            ->fillForm(['longitude' => 500])
            ->call('create')
            ->assertHasFormErrors(['longitude']);
    }

    public function test_valid_coordinates_pass_their_own_validation(): void
    {
        Livewire::test(CreateAddress::class)
            ->fillForm(['latitude' => 24.7136, 'longitude' => 46.6753])
            ->call('create')
            ->assertHasNoFormErrors(['latitude', 'longitude']);
    }

    // --- Artist/Client password: were plaintext with no minimum length ---

    public function test_a_short_artist_password_is_rejected(): void
    {
        Livewire::test(CreateArtist::class)
            ->fillForm(['password' => 'abc'])
            ->call('create')
            ->assertHasFormErrors(['password']);
    }

    public function test_a_six_char_artist_password_passes_length_validation(): void
    {
        Livewire::test(CreateArtist::class)
            ->fillForm(['password' => '123456'])
            ->call('create')
            ->assertHasNoFormErrors(['password']);
    }

    public function test_a_short_client_password_is_rejected(): void
    {
        Livewire::test(CreateClient::class)
            ->fillForm(['password' => 'abc'])
            ->call('create')
            ->assertHasFormErrors(['password']);
    }

    // --- Password reset on edit must be SAFE: the field must not pre-load the bcrypt hash ---
    // Artist/Client accounts authenticate through the mobile API. If the edit form hydrated the
    // stored hash into the password field, a blank save would re-hash it (double-hash) and lock
    // the user out of the app. The field must start empty on edit (password is in User::$hidden).

    public function test_the_artist_edit_form_does_not_preload_the_password_hash(): void
    {
        $artist = User::factory()->artist()->create();

        Livewire::test(EditArtist::class, ['record' => $artist->getRouteKey()])
            ->assertFormSet(['password' => null]);
    }

    public function test_the_client_edit_form_does_not_preload_the_password_hash(): void
    {
        $client = User::factory()->client()->create();

        Livewire::test(EditClient::class, ['record' => $client->getRouteKey()])
            ->assertFormSet(['password' => null]);
    }
}
