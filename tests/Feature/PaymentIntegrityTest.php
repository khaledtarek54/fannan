<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderPaymentTransaction;
use App\Models\User;
use App\Models\UserTransaction;
use App\Services\HyperPayService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Payment-integrity guards for docs/SECURITY_ISSUES_ROUND2.md:
 *  R2-C3 — is_paid / pricing columns are not mass-assignable on an order.
 *  R2-H7 — /easykash/pay requires ownership of the order.
 *  R2-C2 — the EasyKash charge is bound to the server-computed order total (client amount ignored).
 *  R2-H6 — payment confirmation only settles when the paid amount / order actually match.
 */
class PaymentIntegrityTest extends TestCase
{
    use RefreshDatabase;

    // ───────────────────────── R2-C3 ─────────────────────────

    public function test_is_paid_and_pricing_are_not_mass_assignable_on_an_order(): void
    {
        $order = Order::factory()->create(); // is_paid = false

        // A $request->all() sink would forward these — they must be dropped.
        $order->update(['is_paid' => true, 'coupon_amount' => 999, 'updated_budget' => 5]);

        $fresh = $order->fresh();
        $this->assertFalse((bool) $fresh->is_paid);
        $this->assertNotEquals(999, $fresh->coupon_amount);
    }

    // ───────────────────────── R2-H7 ─────────────────────────

    public function test_easykash_pay_rejects_a_non_owner_of_the_order(): void
    {
        Http::fake(); // never reach the gateway
        $order = Order::factory()->create();
        $attacker = User::factory()->client()->create();

        $this->actingAs($attacker, 'api')
            ->postJson('/api/easykash/pay', [
                'order_id' => $order->id,
                'amount'   => 100,
                'name'     => 'A',
                'email'    => 'a@example.com',
                'mobile'   => '966500000000',
            ])->assertStatus(403);

        $this->assertDatabaseCount('user_transactions', 0);
    }

    // ───────────────────────── R2-C2 ─────────────────────────

    public function test_easykash_pay_ignores_client_amount_and_charges_the_order_total(): void
    {
        Http::fake(['back.easykash.net/*' => Http::response(['redirectUrl' => 'https://pay.test/x'], 200)]);
        $order = Order::factory()->create(['cost' => 250]); // direct order, no offers → total_cost 250
        $client = $order->client;

        $this->actingAs($client, 'api')
            ->postJson('/api/easykash/pay', [
                'order_id' => $order->id,
                'amount'   => 1, // attacker attempts to pay 1
                'name'     => 'A',
                'email'    => 'a@example.com',
                'mobile'   => '966500000000',
            ])->assertStatus(200);

        $tx = UserTransaction::where('order_id', $order->id)->first();
        $this->assertNotNull($tx);
        $this->assertEqualsWithDelta(250.0, (float) $tx->amount, 0.001); // server total, not the sent 1
    }

    // ─────────────────────── R2-H6 (EasyKash) ───────────────────────

    public function test_easykash_underpaid_callback_does_not_settle_the_order(): void
    {
        [$order, $tx] = $this->orderWithEasykashTx('654321', 250);

        $this->postJson('/api/easykash/callback', $this->signedEasykashPayload([
            'status' => 'PAID', 'Amount' => 1, 'customerReference' => '654321',
        ]))->assertStatus(200);

        $this->assertFalse((bool) $order->fresh()->is_paid);
        $this->assertFalse((bool) $tx->fresh()->is_paid);
    }

    public function test_easykash_full_payment_callback_settles_the_order(): void
    {
        [$order, $tx] = $this->orderWithEasykashTx('777777', 250);

        $this->postJson('/api/easykash/callback', $this->signedEasykashPayload([
            'status' => 'PAID', 'Amount' => 250, 'customerReference' => '777777',
        ]))->assertStatus(200);

        $this->assertTrue((bool) $order->fresh()->is_paid);
        $this->assertTrue((bool) $tx->fresh()->is_paid);
    }

    // ─────────────────────── R2-H6 (HyperPay) ───────────────────────

    public function test_hyperpay_status_does_not_settle_a_mismatched_order(): void
    {
        $order = Order::factory()->create(['is_paid' => false]);
        $opt = OrderPaymentTransaction::forceCreate([
            'order_id' => $order->id, 'amount' => 250, 'checkout_id' => 'CHK1', 'is_complete' => false,
        ]);

        // Gateway reports success, but for a DIFFERENT checkout (another order's merchantTransactionId).
        $this->mock(HyperPayService::class, fn ($m) => $m->shouldReceive('getPaymentStatus')->andReturn([
            'status' => true, 'checkoutId' => 'CHK1',
            'amount' => 250, 'merchantTransactionId' => 'Transaction999999',
        ]));

        app(PaymentService::class)->getPaymentStatus(request());

        $this->assertFalse((bool) $order->fresh()->is_paid);
        $this->assertFalse((bool) $opt->fresh()->is_complete);
    }

    public function test_hyperpay_status_settles_a_matching_order(): void
    {
        $order = Order::factory()->create(['is_paid' => false]);
        $opt = OrderPaymentTransaction::forceCreate([
            'order_id' => $order->id, 'amount' => 250, 'checkout_id' => 'CHK2', 'is_complete' => false,
        ]);

        $this->mock(HyperPayService::class, fn ($m) => $m->shouldReceive('getPaymentStatus')->andReturn([
            'status' => true, 'checkoutId' => 'CHK2',
            'amount' => 250, 'merchantTransactionId' => 'Transaction' . $order->id,
        ]));

        app(PaymentService::class)->getPaymentStatus(request());

        $this->assertTrue((bool) $order->fresh()->is_paid);
        $this->assertTrue((bool) $opt->fresh()->is_complete);
    }

    // ───────────────────────── helpers ─────────────────────────

    /** @return array{0: Order, 1: UserTransaction} */
    private function orderWithEasykashTx(string $ref, float $amount): array
    {
        $order = Order::factory()->create(['is_paid' => false]);
        $tx = UserTransaction::forceCreate([
            'order_id' => $order->id,
            'amount' => $amount,
            'name' => 'A', 'email' => 'a@example.com', 'mobile' => '966500000000',
            'customer_reference' => $ref,
            'status' => 'pending',
            'is_paid' => false,
        ]);

        return [$order, $tx];
    }

    /** HMAC-sign a callback exactly as EasyKashService::verifyCallbackSignature expects. */
    private function signedEasykashPayload(array $overrides): array
    {
        $p = array_merge([
            'ProductCode' => 'PC1',
            'Amount' => 250,
            'ProductType' => 'order',
            'PaymentMethod' => 'card',
            'status' => 'PAID',
            'easykashRef' => 'EKREF1',
            'customerReference' => '000000',
        ], $overrides);

        $dataStr = $p['ProductCode'] . $p['Amount'] . $p['ProductType'] . $p['PaymentMethod']
            . $p['status'] . $p['easykashRef'] . $p['customerReference'];
        $p['signatureHash'] = hash_hmac('sha512', $dataStr, config('services.easykash.secret_key'));

        return $p;
    }
}
