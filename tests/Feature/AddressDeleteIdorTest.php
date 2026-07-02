<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression guard for SECURITY_ISSUES.md M4 — a client must only be able to delete their own
 * saved addresses, not any address by id.
 */
class AddressDeleteIdorTest extends TestCase
{
    use RefreshDatabase;

    private function addressFor(User $owner): Address
    {
        $city = City::create(['name' => 'Riyadh']);

        return Address::forceCreate([
            'user_id' => $owner->id,
            'city_id' => $city->id,
            'name' => 'Home',
            'latitude' => '24.7',
            'longitude' => '46.6',
        ]);
    }

    public function test_a_client_cannot_delete_another_clients_address(): void
    {
        $address = $this->addressFor(User::factory()->client()->create());

        $this->actingAs(User::factory()->client()->create(), 'api')
            ->postJson('/api/address/delete', ['address_id' => $address->id])
            ->assertStatus(403);

        $this->assertDatabaseHas('addresses', ['id' => $address->id]);
    }

    public function test_a_client_can_delete_their_own_address(): void
    {
        $owner = User::factory()->client()->create();
        $address = $this->addressFor($owner);

        $this->actingAs($owner, 'api')
            ->postJson('/api/address/delete', ['address_id' => $address->id])
            ->assertStatus(200);
    }
}
