<?php

namespace App\Services;

use App\Models\UserTransaction;

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

        $payment->status           = $data->status;
        $payment->easykash_ref     = $data->easykashRef;
        $payment->payment_method   = $data->PaymentMethod;
        $payment->product_type     = $data->ProductType;
        $payment->amount_paid      = $data->Amount;
        $payment->callback_payload = json_encode($rawPayload);

        if ($data->status === "PAID") {
            $payment->is_paid = true;
            if ($payment->order) {
                $payment->order->update(['is_paid' => true]);
            }
        }

        $payment->save();

        return $payment;
    }

    /**
     * Update DB from EasyKash GET redirect (simpler update with query params)
     */
    public function updateFromRedirect(string $customerReference, string $status, ?string $easykashRef = null): ?UserTransaction
    {
        $payment = UserTransaction::where('customer_reference', $customerReference)->first();

        if (! $payment) {
            return null;
        }

        $payment->status = $status;

        if ($easykashRef) {
            $payment->easykash_ref = $easykashRef;
        }

        if ($status === "PAID") {
            $payment->is_paid = true;
            if ($payment->order) {
                $payment->order->update(['is_paid' => true]);
            }
        }

        $payment->save();

        return $payment;
    }
}
