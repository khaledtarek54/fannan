<?php

namespace Tests\Feature;

use App\Http\Resources\CounterpartyResource;
use App\Http\Resources\UserResource;
use App\Models\Address;
use App\Models\City;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PII / data-leak guards for docs/SECURITY_ISSUES_ROUND2.md:
 *  R2-H2 — fcm_token is never serialized (push-token hijacking).
 *  R2-H3 — counterparty contact (email/phone/whatsapp) + tax IDs are exposed only for a confirmed
 *          engagement, never in browse/list responses.
 *  R2-H1 — bidding browse hides the client's PII + exact location from non-participant artists.
 */
class PiiRedactionTest extends TestCase
{
    use RefreshDatabase;

    // ───────────────── R2-H2 ─────────────────

    public function test_fcm_token_is_not_serialized_by_user_resource_or_the_model(): void
    {
        $user = User::factory()->create(['fcm_token' => 'secret-push-token']);

        $this->assertArrayNotHasKey('fcm_token', (new UserResource($user))->toArray(request()));
        $this->assertArrayNotHasKey('fcm_token', $user->toArray());
    }

    // ───────────────── R2-H3 (counterparty shape) ─────────────────

    public function test_counterparty_hides_contact_and_tax_by_default(): void
    {
        $artist = User::factory()->artist()->create();

        $data = (new CounterpartyResource($artist))->toArray(request());

        foreach (
            ['email', 'phone', 'whatsapp', 'phone_prefix', 'vat_number', 'cr_number',
                'fcm_token', 'latitude', 'longitude', 'date_of_birth', 'gender'] as $leak
        ) {
            $this->assertArrayNotHasKey($leak, $data, "$leak must not leak in a browse/list embed");
        }
    }

    public function test_counterparty_exposes_contact_only_for_a_confirmed_engagement(): void
    {
        $artist = User::factory()->artist()->create();

        $data = (new CounterpartyResource($artist, true))->toArray(request());

        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('phone', $data);
        // Tax IDs / push token are never exposed, even for a confirmed engagement.
        $this->assertArrayNotHasKey('vat_number', $data);
        $this->assertArrayNotHasKey('fcm_token', $data);
    }

    // ───────────────── R2-H1 (bidding IDOR) ─────────────────

    private function biddingOrderWithAddress(User $owner): Order
    {
        $city = City::create(['name' => 'Riyadh']);
        $address = Address::create([
            'user_id' => $owner->id,
            'city_id' => $city->id,
            'name' => 'Home',
            'description' => '12 Secret Street',
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ]);

        return Order::factory()->bidding()->create([
            'client_id' => $owner->id,
            'address_id' => $address->id,
        ]);
    }

    public function test_non_participant_artist_cannot_see_client_pii_or_exact_location(): void
    {
        $owner = User::factory()->client()->create(['email' => 'client@example.com']);
        $order = $this->biddingOrderWithAddress($owner);
        $stranger = User::factory()->artist()->create();

        $json = $this->actingAs($stranger, 'api')
            ->postJson('/api/bidding-order/id', ['order_id' => $order->id])
            ->assertStatus(200)
            ->json();

        $this->assertNull($json['orders']['latitude']);
        $this->assertNull($json['orders']['longitude']);
        $this->assertArrayNotHasKey('email', $json['orders']['client']);
        $this->assertArrayNotHasKey('phone', $json['orders']['client']);
        // City stays visible so artists can still decide whether to bid.
        $this->assertSame('Riyadh', $json['orders']['city']);
    }

    public function test_the_owner_still_sees_their_own_location_and_contact(): void
    {
        $owner = User::factory()->client()->create(['email' => 'client@example.com']);
        $order = $this->biddingOrderWithAddress($owner);

        $json = $this->actingAs($owner, 'api')
            ->postJson('/api/bidding-order/id', ['order_id' => $order->id])
            ->assertStatus(200)
            ->json();

        $this->assertEqualsWithDelta(24.7136, (float) $json['orders']['latitude'], 0.0001);
        $this->assertSame('client@example.com', $json['orders']['client']['email']);
    }
}
