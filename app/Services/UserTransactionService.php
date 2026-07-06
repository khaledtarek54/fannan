<?php

namespace App\Services;

use App\Models\UserTransaction;
use Illuminate\Support\Facades\Log;

class UserTransactionService
{
    /**
     * Create a new user transaction record
     */
    public function create(array $validated): UserTransaction
    {
        $customerReference = rand(100000, 999999);

        return UserTransaction::create([
            'order_id'           => $validated['order_id'] ?? null,
            'amount'             => $validated['amount'],
            'name'               => $validated['name'],
            'email'              => $validated['email'],
            'mobile'             => $validated['mobile'],
            'customer_reference' => $customerReference,
            'status'             => 'pending',
        ]);
    }

    /**
     * Update DB on EasyKash callback
     */
    public function updateFromCallback(object $data, array $rawPayload): ?UserTransaction
    {
        $payment = UserTransaction::where('customer_reference', $data->customerReference)->first();

        if (! $payment) {
            return null;
        }

        // [SECURITY] Idempotency — ignore replays of an already-paid transaction so a captured,
        // valid callback can't be replayed to re-trigger downstream effects (L2).
        if ($payment->is_paid) {
            return $payment;
        }

        $payment->status           = $data->status;
        $payment->easykash_ref     = $data->easykashRef;
        $payment->payment_method   = $data->PaymentMethod;
        $payment->product_type     = $data->ProductType;
        $payment->amount_paid      = $data->Amount;
        $payment->callback_payload = json_encode($rawPayload);

        if ($data->status === "PAID") {
            // [SECURITY][R2-C2/R2-H6] Only settle if the amount EasyKash actually processed covers
            // the charge we bound to this order server-side (EasyKashController sets `amount` from
            // the order total). A tampered / pay-less pay-link therefore cannot settle the order,
            // even though its PAID callback is legitimately HMAC-signed for the tiny amount.
            $paid     = (float) ($data->Amount ?? 0);
            $expected = (float) $payment->amount;

            if ($expected > 0 && $paid + 0.01 >= $expected) {
                $payment->is_paid = true;
                if ($payment->order) {
                    // Direct assignment — is_paid is no longer mass-assignable (R2-C3).
                    $order = $payment->order;
                    $order->is_paid = true;
                    $order->save();
                }
            } else {
                Log::warning('EasyKash underpayment ignored — order NOT settled', [
                    'customer_reference' => $payment->customer_reference,
                    'expected'           => $expected,
                    'paid'               => $paid,
                ]);
            }
        }

        $payment->save();

        return $payment;
    }

    // [SECURITY] updateFromRedirect() was REMOVED: it mutated payment state (is_paid) from the
    // unauthenticated, unsigned EasyKash GET redirect using the untrusted query `status`
    // (see docs/SECURITY_ISSUES.md C2). Payment state is now changed only by updateFromCallback()
    // after HMAC-SHA512 signature verification.
      /**
     * Get transaction status by customer reference
     */
    public function getTransactionStatus(string $customerReference): ?UserTransaction
    {
        return UserTransaction::where('order_id', $customerReference)->latest()->first();
    }
}
