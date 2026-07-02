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
        if ($order->is_paid) {
            return [
                'status' => false,
                'message' => trans('app.paid_done')
            ];
        }

//        try {
        $totalCost = $order->total_cost;
        $tax = Setting::query()->where('type', SettingKey::TAX->value)->first();
        $taxValue = ($totalCost * $tax->value) / 100;
        $totalCost += $taxValue;
        $totalCost -= $order->coupon_amount ?? 0;

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
