<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Models\UserTransaction;
use App\Services\OrderPricingService;
use App\Services\UserTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Group 5 hardening guards for docs/SECURITY_ISSUES_ROUND2.md:
 *  R2-H5 — profile photo must be a real image (stored-XSS / abuse).
 *  R2-M3 — a coupon larger than the order cannot produce a negative total.
 *  R2-M4 — getTransactionStatus() resolves by customer_reference, not order_id.
 *  R2-L2 — the dead POST /support/delete route is gone.
 *  R2-L5 — UserTransaction never serializes the raw payload / payer PII.
 */
class HardeningTest extends TestCase
{
    use RefreshDatabase;

    // ───────── R2-H5 ─────────

    public function test_profile_photo_rejects_a_non_image_upload(): void
    {
        $client = User::factory()->client()->create(['phone' => '966500000077', 'email' => 'me@example.com']);

        $this->actingAs($client, 'api')->post('/api/client/complete/profile', array_merge($this->profilePayload(), [
            'profile_photo' => UploadedFile::fake()->create('evil.php', 100, 'application/x-php'),
        ]), ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('profile_photo');
    }

    public function test_profile_photo_accepts_a_real_image(): void
    {
        Storage::fake('public');
        $client = User::factory()->client()->create(['phone' => '966500000078', 'email' => 'me2@example.com']);

        $this->actingAs($client, 'api')->post('/api/client/complete/profile', array_merge($this->profilePayload([
            'phone' => '966500000078', 'email' => 'me2@example.com',
        ]), [
            'profile_photo' => UploadedFile::fake()->image('avatar.jpg'),
        ]), ['Accept' => 'application/json'])
            ->assertJsonMissingValidationErrors('profile_photo');
    }

    // ───────── R2-M3 ─────────

    public function test_a_coupon_larger_than_the_order_cannot_go_negative(): void
    {
        $breakdown = app(OrderPricingService::class)->breakdown(100.0, 500.0); // discount ≫ cost

        $this->assertSame(0.0, (float) $breakdown['total_cost']);
        $this->assertLessThanOrEqual(100.0, (float) $breakdown['discount']); // discount clamped to cost
    }

    // ───────── R2-M4 ─────────

    public function test_get_transaction_status_resolves_by_customer_reference(): void
    {
        $order = Order::factory()->create();
        $this->makeTx($order->id, 'REF-A', 100);
        $this->makeTx($order->id, 'REF-B', 200);

        $found = app(UserTransactionService::class)->getTransactionStatus('REF-A');

        $this->assertNotNull($found);
        $this->assertSame('REF-A', $found->customer_reference);
    }

    // ───────── R2-L2 ─────────

    public function test_dead_support_delete_route_is_gone(): void
    {
        $this->actingAs(User::factory()->create(), 'api')
            ->postJson('/api/support/delete', [])
            ->assertStatus(404);
    }

    // ───────── R2-L5 ─────────

    public function test_user_transaction_hides_payload_and_payer_pii(): void
    {
        $order = Order::factory()->create();
        $tx = $this->makeTx($order->id, 'REF-C', 100, [
            'email' => 'payer@example.com',
            'mobile' => '966500000000',
            'callback_payload' => '{"secret":true}',
            'easykash_ref' => 'EK-123',
        ]);

        $arr = $tx->fresh()->toArray();

        foreach (['callback_payload', 'easykash_ref', 'email', 'mobile'] as $hidden) {
            $this->assertArrayNotHasKey($hidden, $arr);
        }
    }

    // ───────── helpers ─────────

    private function makeTx(int $orderId, string $ref, float $amount, array $extra = []): UserTransaction
    {
        return UserTransaction::forceCreate(array_merge([
            'order_id' => $orderId,
            'customer_reference' => $ref,
            'amount' => $amount,
            'name' => 'Payer',
            'email' => 'p@example.com',
            'mobile' => '966500000000',
            'status' => 'pending',
            'is_paid' => false,
        ], $extra));
    }

    private function profilePayload(array $overrides = []): array
    {
        return array_merge([
            'phone' => '966500000077',
            'name' => 'Me',
            'email' => 'me@example.com',
            'dob' => '1990-01-01',
            'gender' => 'male',
            'city_id' => 1,
            'city' => 'Riyadh',
            'latitude' => '24.7136',
            'longitude' => '46.6753',
        ], $overrides);
    }
}
