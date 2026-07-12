<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\UserTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression guard for the EasyKash settlement path.
 *
 * A valid HMAC-signed PAID webhook must not only flip `is_paid` — it must also advance the order
 * status to `accepted`, mirroring the HyperPay path (PaymentService::webhook → updateStatus).
 * Previously the order stayed at `in_payment` after a successful EasyKash payment, which hid it
 * from the artist's order list (artistOrders filters status IN pending/artist_pending/accepted)
 * and showed it as unpaid to the client, even though is_paid=1.
 */
class EasyKashSettlementStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a callback payload signed exactly the way EasyKashService::verifyCallbackSignature
     * expects (HMAC-SHA512 over the fields joined with no separator).
     */
    private function signedPayload(array $overrides = []): array
    {
        $payload = array_merge([
            'ProductCode'       => 'TESTCODE',
            'Amount'            => 100,
            'ProductType'       => 'Direct Pay',
            'PaymentMethod'     => 'Credit & Debit Card',
            'status'            => 'PAID',
            'easykashRef'       => 'EK-REF-123',
            'customerReference' => '999888777666',
        ], $overrides);

        $dataStr = implode('', [
            $payload['ProductCode'],
            $payload['Amount'],
            $payload['ProductType'],
            $payload['PaymentMethod'],
            $payload['status'],
            $payload['easykashRef'],
            $payload['customerReference'],
        ]);

        $payload['signatureHash'] = hash_hmac('sha512', $dataStr, config('services.easykash.secret_key'));

        return $payload;
    }

    private function orderInPaymentWithTx(string $customerReference, float $txAmount = 100): Order
    {
        $order = Order::factory()->create(['is_paid' => false]);
        $order->setStatus(OrderStatus::IN_PAYMENT->value);

        UserTransaction::forceCreate([
            'order_id'           => $order->id,
            'amount'             => $txAmount,
            'name'               => 'Buyer',
            'email'              => 'buyer@example.com',
            'mobile'             => '966500000000',
            'customer_reference' => $customerReference,
            'status'             => 'pending',
            'is_paid'            => false,
        ]);

        return $order;
    }

    private function latestStatusName(Order $order): ?string
    {
        return $order->statuses()->orderByDesc('id')->first()?->name;
    }

    public function test_valid_paid_callback_advances_order_status_to_accepted(): void
    {
        $order = $this->orderInPaymentWithTx('999888777666', 100);

        $this->postJson('/api/easykash/callback', $this->signedPayload([
            'customerReference' => '999888777666',
            'Amount'            => 100,
        ]))
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $order = $order->fresh();
        $this->assertTrue((bool) $order->is_paid, 'order should be marked paid');
        $this->assertSame(
            OrderStatus::ACCEPTED->value,
            $this->latestStatusName($order),
            'order status should advance to accepted after settlement'
        );
    }

    public function test_forged_signature_neither_settles_nor_advances_status(): void
    {
        $order = $this->orderInPaymentWithTx('111222333444', 100);

        $payload = $this->signedPayload([
            'customerReference' => '111222333444',
            'Amount'            => 100,
        ]);
        $payload['signatureHash'] = 'deadbeef'; // tampered — must be rejected

        $this->postJson('/api/easykash/callback', $payload)
            ->assertStatus(401);

        $order = $order->fresh();
        $this->assertFalse((bool) $order->is_paid, 'forged callback must not settle the order');
        $this->assertSame(
            OrderStatus::IN_PAYMENT->value,
            $this->latestStatusName($order),
            'forged callback must not advance the status'
        );
    }
}
