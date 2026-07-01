<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class HyperPayService
{

    protected string $baseUrl;
    protected string $entityId;
    protected string $accessToken;

    public function __construct()
    {
        $this->baseUrl = config('hyperpay.base_url');
        $this->accessToken = config('hyperpay.access_token');
    }

    private function getEntityId(string $paymentMethod): ?string
    {
        if ($paymentMethod == "mada")
            return config('hyperpay.mada_entity_id');
        elseif ($paymentMethod == "apple_pay")
            return config('hyperpay.apple_pay_entity_id');
        else
            return config('hyperpay.entity_id');
    }

    public function createPaymentWidget(int $orderId, int $amount, string $currency, string $paymentType, string $paymentMethod): \stdClass
    {
        $url = $this->baseUrl . '/v1/checkouts';
        $this->entityId = $this->getEntityId($paymentMethod);
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Accept' => 'application/json',
        ])->asForm()->post($url, [
            'entityId' => $this->entityId,
            'amount' => $amount,
            'currency' => $currency,
            'paymentType' => $paymentType,
            'merchantTransactionId' => "Transaction".$orderId,
            "shopperResultUrl" => config('app.url') . '/api/webhook?payment_method=' . $paymentMethod,
            'integrity' => true
        ]);

        $response = $response->json();

        $data = new \stdClass();
        if (isset($response['id'])) {
            $data->status = true;
            $data->link = $this->baseUrl . '/v1/paymentWidgets.js?checkoutId=' . $response['id'];
            $data->response = $response;
            $data->message = trans('app.success');
            return $data;
        }
        $data->status = false;
        $data->message = trans('app.generate_payment_error');
        return $data;
    }

    public function getPaymentStatus($request): array
    {
        $resourcePath = $request->resourcePath;
        $paymentMethod = $request->payment_method;
        $this->entityId = $this->getEntityId($paymentMethod);
        $url = $this->baseUrl . $resourcePath . '?entityId=' . $this->entityId;
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
            ])->get($url);

            $response = $response->json();
            if (isset($response['result']['code'])) {
                $resultCode = $response['result']['code'];

                $successPattern = '/^(000\.000\.|000\.100\.1|000\.[36]|000\.400\.[12]0)/';
                $retryPattern = '/^(000\.400\.0[^3]|000\.400\.100)/';
                $openSessionPattern = '/^(000\.200)/';
                $waitingPattern = '/^(000\.400\.[1][0-9][1-9]|000\.400\.2)/';
                $failurePattern = '/^(300\.100\.100)/';

                if (preg_match($successPattern, $resultCode)) {
                    return [
                        'status' => true,
                        'message' => trans('app.payment_success'),
                        'status_string' => "Paid",
                        'checkoutId' => $request->id,
                    ];
                } elseif (preg_match($retryPattern, $resultCode)) {
                    return [
                        'status' => false,
                        'message' => trans('app.payment_retry'),
                        'status_string' => "Not paid",
                        'checkoutId' => $request->id,
                    ];
                } elseif (preg_match($openSessionPattern, $resultCode)) {
                    return [
                        'status' => false,
                        'message' => trans('app.payment_open_session'),
                        'status_string' => "Not paid",
                        'checkoutId' => $request->id,
                    ];
                } elseif (preg_match($waitingPattern, $resultCode)) {
                    return [
                        'status' => false,
                        'message' => trans('app.payment_waiting'),
                        'status_string' => "Not paid",
                        'checkoutId' => $request->id,
                    ];
                } elseif (preg_match($failurePattern, $resultCode)) {
                    return [
                        'status' => false,
                        'message' => trans('app.payment_failure'),
                        'status_string' => "Not paid",
                        'checkoutId' => $request->id,
                    ];
                } else {
                    return [
                        'status' => false,
                        'message' => trans('app.unknown_payment_error'),
                        'status_string' => "Not paid",
                        'checkoutId' => $request->id,
                    ];
                }
            }

            return [
                'status' => false,
                'message' => trans('app.payment_error'),
                'status_string' => "Not paid",
                'checkoutId' => $request->id,
            ];
        } catch (\Exception $exception) {
            return [
                'status' => false,
                'message' => trans('app.exception_error'),
                'status_string' => "Not paid",
                'checkoutId' => $request->id,
            ];
        }
    }

    public function createPaymentServerToServer($amount, $currency, $paymentType): \stdClass
    {
        $url = $this->baseUrl . '/v1/payments';
        $data = [
            'entityId' => $this->entityId,
            'amount' => $amount,
            'currency' => $currency,
            'paymentType' => $paymentType,
            'paymentBrand' => "VISA",
            'merchantTransactionId' => uniqid(),
            'card.number' => '4200000000000000',
            'card.expiryMonth' => '05',
            'card.expiryYear' => '2034',
            'card.cvv' => '123',
        ];
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Accept' => 'application/json',
            'Content-' => 'application/json',
        ])->asForm()->post($url, $data);

        $response = $response->json();
        $data = new \stdClass();
        if (isset($response['id'])) {
            $data->status = true;
            $data->link = config('app.url') . 'v1/paymentWidgets.js?checkoutId=' . $response['id'];
            $data->response = $response;
            $data->message = 'success';
            return $data;
        }
        $data->status = false;
        $data->message = 'error';
        return $data;
    }

}
