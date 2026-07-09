<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\SettingKey;
use App\Http\Requests\Order\OrderIdRequest;
use App\Models\Order;
use App\Models\OrderPaymentTransaction;
use App\Models\Setting;
use App\Services\Concerns\OrderRepository;
use App\Services\Contracts\OrderPaymentTransactionRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PaymentService
{

    public function __construct(
        protected readonly OrderPaymentTransactionRepositoryInterface $orderPaymentTransactionRepository,
        protected readonly HyperPayService                            $hyperPayService,
        protected readonly OrderRepository                            $orderRepository,
        protected readonly OrderPricingService                        $pricing,
    )
    {
    }

    /**
     * @param array $payload
     * @return array
     */
    public function checkout(array $payload): array
    {
        /** @var Order $order */
        $order = $this->orderRepository->findById($payload['order_id']);
        // [SECURITY] Only the order's own client may initiate payment for it (H5).
        abort_if($order === null, 404);
        abort_unless((int) $order->client_id === (int) auth()->id(), 403);
        if ($order->is_paid) {
            return [
                'status' => false,
                'message' => trans('app.paid_done')
            ];
        }

//        try {
        // [B4] Shared pricing so the charge matches the quote returned by OrderService::checkout.
        $totalCost = $this->pricing->breakdown(
            (float) $order->total_cost,
            (float) ($order->coupon_amount ?? 0)
        )['total_cost'];

        $data = $this->hyperPayService->createPaymentWidget($order->id, $totalCost, 'SAR', 'DB', $payload['payment_method']);
        if (!$data->status)
            return [
                'status' => $data->status,
                'message' => $data->message
            ];

        $response = $data->response;
        $transaction = [
            'order_id' => $order->id,
            'amount' => $totalCost,
            'checkout_id' => $response['id'],
            'buildNumber' => $response['buildNumber'],
            'ndc' => $response['ndc'],
        ];
        $this->orderPaymentTransactionRepository->create($transaction);

        $htmlContent = view('payments.copyandpay', [
            'paymentUrl' => $data->link,
        ])->render();
        $fileName = 'payment_links/payment_' . $order->id . '_' . time() . '.html';
        Storage::disk('public')->put($fileName, $htmlContent);
        $fileUrl = Storage::disk('public')->url($fileName);

        return [
            'status' => $data->status,
            'id' => $data->response['id'],
            'link' => $fileUrl,
            'message' => $data->message
        ];
//        }catch (\Exception $exception){
//            Log::info("Error in generating payment link: ". $exception->getMessage());
//            return [
//                'status' => false,
//                'message' => "Server Error"
//            ];
//        }

    }

    /**
     * @param Request $request
     * @return array
     */
    public function checkPaymentStatus(Request $request): array
    {
        return $this->hyperPayService->getPaymentStatus($request);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function getPaymentStatus(Request $request): array
    {
        Log::info('start Webhook');
        $data = $this->hyperPayService->getPaymentStatus($request);
        if ($data['status']) {
            /** @var OrderPaymentTransaction $model */
            $model = $this->orderPaymentTransactionRepository->findByCheckoutId($data['checkoutId']);
            if ($model) {
                // [SECURITY][R2-H6] Idempotency — never settle the same checkout twice (avoids
                // re-flipping status / re-running downstream effects on a replayed confirmation).
                if ($model->is_complete) {
                    Log::info('Webhook already processed', ['checkoutId' => $data['checkoutId']]);
                    return $data;
                }

                // [SECURITY][R2-H6] Bind the verified success to THIS order. getPaymentStatus reads
                // the status from the (validated) resourcePath, but the record is looked up by the
                // client-supplied checkout `id`; without this check a genuine success for a cheap
                // or unrelated checkout could be pointed at another order's id to mark it paid.
                $expectedMtx = 'Transaction' . $model->order_id;
                if (($data['merchantTransactionId'] ?? null) !== $expectedMtx) {
                    Log::warning('HyperPay merchantTransactionId mismatch — not settling', [
                        'checkoutId' => $data['checkoutId'],
                        'expected'   => $expectedMtx,
                        'got'        => $data['merchantTransactionId'] ?? null,
                    ]);
                    return [
                        'status'        => false,
                        'message'       => trans('app.payment_error'),
                        'status_string' => 'Not paid',
                        'checkoutId'    => $data['checkoutId'],
                    ];
                }

                // [SECURITY][R2-H6] The charge was fixed server-side at checkout, so the captured
                // amount must cover it (1-unit tolerance absorbs gateway integer rounding).
                if (isset($data['amount']) && (float) $data['amount'] + 1.0 < (float) $model->amount) {
                    Log::warning('HyperPay captured amount below charge — not settling', [
                        'checkoutId' => $data['checkoutId'],
                        'expected'   => (float) $model->amount,
                        'paid'       => (float) $data['amount'],
                    ]);
                    return [
                        'status'        => false,
                        'message'       => trans('app.payment_error'),
                        'status_string' => 'Not paid',
                        'checkoutId'    => $data['checkoutId'],
                    ];
                }

                $model->is_complete = true;
                $model->save();
                $order = $model->order;
                $order->is_paid = true;
                $order->save();
                $this->orderRepository->updateStatus($order->id, OrderStatus::ACCEPTED->value);
            }
            Log::info('Success Webhook');

            return $data;
        }
        Log::info('Failed Webhook');

        return $data;
    }
}
