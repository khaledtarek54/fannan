<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\SettingKey;
use App\Models\Order;
use App\Models\Setting;
use App\Notifications\AcceptOrderNotification;
use App\Notifications\CancelOrderNotification;
use App\Notifications\CompleteOrderNotification;
use App\Notifications\CounterOfferNotification;
use App\Notifications\NewOrderNotification;
use App\Services\Contracts\CouponRepositoryInterface;
use App\Services\Contracts\CouponUserRepositoryInterface;
use App\Services\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        protected OrderRepositoryInterface               $orderRepository,
        protected readonly CouponRepositoryInterface     $couponRepository,
        protected readonly CouponUserRepositoryInterface $couponUserRepository,
    )
    {
    }

    /**
     * @param array $columns
     * @param int $pagination
     * @return LengthAwarePaginator
     */
    public function index(array $params = [], array $columns = ['*'], int $pagination = 25): LengthAwarePaginator
    {
        return $this->orderRepository->index($params);
    }

    public function artistOrders()
    {
        return $this->orderRepository->artistOrders();
    }

    /**
     * @param array $payload
     * @return Model|null
     */
    public function store(array $payload): ?Model
    {
        /** @var Order $model */
        $model = $this->orderRepository->create($payload);
        $artist = $model->artist;
        $client = $model->client;
        try {
            $artist->notify(new NewOrderNotification($model, $client));
        } catch (\Exception $exception) {
            Log::info("Error in sending notification");
        }
        return $model;
    }

    /**
     * @param int $modelId
     * @param string $status
     * @param string|null $reason
     * @return mixed
     */
    public function updateStatus(int $modelId, string $status, string $reason = null): mixed
    {
        return $this->orderRepository->updateStatus($modelId, $status, $reason);
    }

    /**
     * @param array $payload
     * @return bool
     */
    public function acceptOrder(array $payload): bool
    {
        /** @var Order $model */
        $model = $this->orderRepository->findById($payload['order_id'], relations: ['client', 'artist']);
        $this->orderRepository->update($model->id, $payload);
        $this->orderRepository->updateStatus($payload['order_id'], OrderStatus::ACCEPTED->value);
        $client = $model->client;
        $artist = $model->artist;
        try {
            $client->notify(new AcceptOrderNotification($model,$artist));
        } catch (\Exception $exception) {
            Log::info('error while notify user' . $exception->getMessage());
        }
        return true;
    }

    /**
     * @param array $payload
     * @return Order
     */
    public function counterOffer(array $payload): Order
    {
        /** @var Order $model */
        $model = $this->orderRepository->findById($payload['order_id'], relations: ['client', 'artist']);
        $artist = $model->artist;
        $client = $model->client;
        try {
            $artist->notify(new CounterOfferNotification($model,$client));
        } catch (\Exception $exception) {
            Log::info('error while notify user' . $exception->getMessage());
        }
        return $this->orderRepository->counterOffer($payload);
    }


    /**
     * @param array $payload
     * @return array
     */
    public function checkout(array $payload): array
    {
        /** @var Order $model */
        $model = $this->orderRepository->findById($payload['order_id'], ['*'], ['offers', 'acceptedBiddingOrderArtists']);
        $cost = $model->total_cost;
        (float)$tax = Setting::query()->where('type', SettingKey::TAX->value)->first()?->text_en ?? 0;

        $taxAmount = ($cost * $tax) / 100;

        $discount = 0;
        $appliedCoupon = false;
        if (isset($payload['code'])) {
            $coupon = $this->couponRepository->getCouponByCode($payload['code']);
            $usedCoupon = $this->couponUserRepository->checkIfExists(auth()->id(), $coupon->id);
            if (!$usedCoupon) {
                $discount += $this->orderRepository->calculateCouponAmount($coupon, (float)$cost);

                $this->couponUserRepository->create(['user_id' => auth()->id(), 'coupon_id' => $coupon->id,]);

                $model->coupon_id = $coupon->id;
                $model->coupon_amount = $discount;
                $model->save();
                $appliedCoupon = true;
            }
        }

        $totalCost = $cost + $taxAmount - $discount;
        $vat = Setting::query()->where('type', SettingKey::VAT)->first();
        $vatAmount = ($totalCost * $vat?->value ?? 0) / 100;
        $totalCost += $vatAmount;
        $model->setStatus(OrderStatus::IN_PAYMENT->value);
        return [
            'cost' => $cost,
            'tax' => $taxAmount,
            'vat' => $vatAmount,
            'discount' => $discount,
            'total_cost' => $totalCost,
            'applied_coupon' => $appliedCoupon,
        ];
    }

    /**
     * @param int $modelId
     * @param string $status
     * @return bool
     */
    public function cancel(int $modelId, string $status): bool
    {
        /** @var Order $model */
        $model = $this->orderRepository->findById($modelId, relations: ['artist', 'client']);
        $this->orderRepository->updateStatus($model->id, $status);
        $artist = $model->artist;
        $client = $model->client;
        try {
            $artist->notify(new CancelOrderNotification($model, $client));
        } catch (\Exception $exception) {
            Log::info('error while notify user' . $exception->getMessage());
        }
        return true;
    }

    public function notifyCompletedOrders(): bool
    {
        $orders = $this->orderRepository->getCompletedOrders();
        foreach ($orders as $order) {
            if ($order->is_complete) {
                $order->setStatus(OrderStatus::COMPLETED->value);
                $client = $order->client;
                try {
                    $client->notify(new CompleteOrderNotification($order));
                } catch (\Exception $exception) {
                    Log::info('error while notify user' . $exception->getMessage());
                }
            }
        }
        return true;
    }
}
