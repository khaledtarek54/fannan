<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\SettingKey;
use App\Enums\TransactionType;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
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
        protected readonly OrderPricingService           $pricing,
    )
    {
    }

    /**
     * [SECURITY] Ensure the authenticated user is a participant (client or artist) of the order.
     * Guards against IDOR on order-level actions (see docs/SECURITY_ISSUES.md H3, H4).
     */
    private function authorizeParticipant(?Order $order): Order
    {
        abort_if($order === null, 404);
        $userId = (int) auth()->id();
        abort_unless((int) $order->client_id === $userId || (int) $order->artist_id === $userId, 403);
        return $order;
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
        // [SECURITY] Only a participant (client or artist) may change this order's status (H3 reject).
        $order = $this->orderRepository->findById($modelId, relations: ['client', 'artist']);
        $this->authorizeParticipant($order);
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
        // [SECURITY] Only the assigned artist may accept this order (H2).
        abort_unless((int) $model->artist_id === (int) auth()->id(), 403);
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
        // [SECURITY] Only the order's client may send a counter-offer on it (M3).
        abort_unless((int) $model->client_id === (int) auth()->id(), 403);
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
        // [SECURITY] Only the order's client may check out this order (H5).
        abort_unless((int) $model->client_id === (int) auth()->id(), 403);
        $cost = $model->total_cost;

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

        // [B4] Shared pricing so this quote matches the charge in PaymentService exactly.
        $breakdown = $this->pricing->breakdown((float) $cost, (float) $discount);
        $model->setStatus(OrderStatus::IN_PAYMENT->value);

        return $breakdown + ['applied_coupon' => $appliedCoupon];
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
        // [SECURITY] Only a participant (client or artist) may cancel this order (H4).
        $this->authorizeParticipant($model);
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
            if (! $order->is_complete) {
                continue;
            }
            // [BL1-BL3/BL6] Escrow model: pay the artist(s) their net earnings on completion, then
            // mark COMPLETED. Payout is driven by completion — NOT by the client leaving a rating.
            $this->settleOrder($order);
            $order->setStatus(OrderStatus::COMPLETED->value);
            $client = $order->client;
            try {
                $client->notify(new CompleteOrderNotification($order));
            } catch (\Exception $exception) {
                Log::info('error while notify user' . $exception->getMessage());
            }
        }
        return true;
    }

    /**
     * Credit each artist their net earnings (service cost minus platform fee) when the order
     * completes. Handles direct (single artist) and bidding (each accepted bid). Idempotent —
     * an order is never paid out twice. See docs/BUSINESS_LOGIC_ISSUES.md BL1-BL3.
     */
    private function settleOrder(Order $order): void
    {
        $alreadySettled = Transaction::query()
            ->where('type', TransactionType::INCOME->value)
            ->where('model_type', Order::class)
            ->where('model_id', $order->id)
            ->exists();
        if ($alreadySettled) {
            return;
        }

        if ($order->type == OrderType::BIDDING->value) {
            foreach ($order->acceptedBiddingOrderArtists as $bid) {
                $this->creditArtist($order, $bid->artist, (float) $bid->cost);
            }
        } else {
            $this->creditArtist($order, $order->artist, (float) $order->cost_value);
        }
    }

    private function creditArtist(Order $order, ?User $artist, float $cost): void
    {
        if (! $artist) {
            return;
        }
        $fee = (float) ($artist->platform_fees ?? 0);

        Transaction::create([
            'user_id' => $artist->id,
            'type' => TransactionType::INCOME->value,
            'amount' => $cost - ($cost * $fee / 100),
            'model_type' => Order::class,
            'model_id' => $order->id,
        ]);
    }
}
