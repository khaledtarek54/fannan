<?php

namespace App\Services;

use App\Enums\ModelName;
use App\Enums\SettingKey;
use App\Enums\TransactionType;
use App\Models\Order;
use App\Models\Setting;
use App\Services\Concerns\BiddingOrderArtistRepository;
use App\Services\Concerns\OrderRepository;
use App\Services\Concerns\TransactionRepository;
use App\Services\Contracts\RatingRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class RatingService
{
    public function __construct(
        protected readonly RatingRepositoryInterface    $rateRepository,
        protected readonly OrderRepository              $orderRepository,
        protected readonly BiddingOrderArtistRepository $biddingOrderArtistRepository,
        protected readonly TransactionRepository        $transactionRepository,
    )
    {
    }

    /**
     * @param array $payload
     * @return bool
     */
    public function store(array $payload): bool
    {
        if ($payload['offer_id']) {
            $offer = $this->biddingOrderArtistRepository->findById($payload['offer_id'], relations: ['artist', 'order']);
            $artist = $offer->artist;
            $order = $offer->order;
            $payload['model_type'] = ModelName::BIDDING_ORDER_ARTIST->value;
            $payload['model_id'] = $offer->id;
            $cost = $offer->cost;
        } else {
            /** @var Order $model */
            $order = $this->orderRepository->findById($payload['order_id'], relations: ['artist']);
            if (!$order->is_complete)
                return false;
            $payload['model_type'] = ModelName::ORDER->value;
            $payload['model_id'] = $order->id;
            $artist = $order->artist;
            $cost = $order->cost_value;
        }

        $payload['client_id'] = auth()->id();
        $payload['artist_id'] = $artist->id;
        $this->rateRepository->create($payload);

        $defaultPatFormFees = Setting::query()->where('type', SettingKey::VAT->value)->first();
        $platformFees = $artist->platform_fees ?? $defaultPatFormFees->value;

        $cost -= ($cost * $platformFees) / 100;

        $transactionPayload['user_id'] = $artist->id;
        $transactionPayload['type'] = TransactionType::INCOME->value;
        $transactionPayload['amount'] = $cost;
        $transactionPayload['model_type'] = ModelName::ORDER->name;
        $transactionPayload['model_id'] = $order->id;
        $this->transactionRepository->create($transactionPayload);
        return true;
    }
}
