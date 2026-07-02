<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EasyKashService
{
    protected string $apiKey;
    protected string $redirectUrl;
    protected string $secretKey;

    public function __construct()
    {
        $this->apiKey = config('services.easykash.api_key');
        $this->redirectUrl = config('services.easykash.redirect_url');
        $this->secretKey = config('services.easykash.secret_key');
    }

    public function createDirectPayLink(array $data)
    {
        try {
            $payload = [
                "amount" => $data['amount'],
                "currency" => "EGP",   // required, EasyKash converts if needed
                "paymentOptions" => [2, 4, 5], // cards, wallets, fawry — your choice
                "cashExpiry" => 12,
                "name" => $data['name'],
                "email" => $data['email'],
                "mobile" => $data['mobile'],
                "redirectUrl" => $this->redirectUrl,
                "customerReference" => $data['customerReference'],
            ];

            $response = Http::withHeaders([
                "authorization" => $this->apiKey,
                "accept" => "application/json",
            ])->post("https://back.easykash.net/api/directpayv1/pay", $payload);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("EasyKash direct pay error: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Error communicating with EasyKash."
            ];
        }
    }

    public function verifyCallbackSignature(object $payload): bool
    {
        try {
            // Extract fields from callback payload (same order as EasyKash signs them)
            $productCode       = $payload->ProductCode ?? null;
            $amount            = $payload->Amount ?? null;
            $productType       = $payload->ProductType ?? null;
            $paymentMethod     = $payload->PaymentMethod ?? null;
            $status            = $payload->status ?? null;
            $easykashRef       = $payload->easykashRef ?? null;
            $customerReference = $payload->customerReference ?? null;
            $signatureHash     = isset($payload->signatureHash) ? (string) $payload->signatureHash : '';

            // Prepare data in exact sequence
            $dataToSecure = [
                $productCode,
                $amount,
                $productType,
                $paymentMethod,
                $status,
                $easykashRef,
                $customerReference,
            ];

            // Convert array to string (no separators)
            $dataStr = implode('', $dataToSecure);

            // Generate encrypted signature HMAC SHA-512
            $calculatedSignature = hash_hmac('sha512', $dataStr, $this->secretKey);

            // Compare received vs calculated
            return hash_equals($calculatedSignature, $signatureHash);
        } catch (\Exception $e) {
            Log::error("EasyKash callback signature validation failed: " . $e->getMessage());
            return false;
        }
    }
}
