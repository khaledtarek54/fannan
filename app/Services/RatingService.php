<?php

namespace App\Services;

use App\Enums\ModelName;
use App\Enums\SettingKey;
use App\Enums\TransactionType;
use App\Models\Order;
use App\Models\Rating;
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
        if (!empty($payload['offer_id'])) {
            $offer = $this->biddingOrderArtistRepository->findById($payload['offer_id'], relations: ['artist', 'order']);
            // [SECURITY] Only the client who owns the bidding order may rate it (B2 IDOR).
            abort_unless($offer->order && (int) $offer->order->client_id === (int) auth()->id(), 403);
            // [BUG] The offer branch previously skipped the completion check — require the bid to be accepted.
            if (! $offer->is_accepted)
                return false;
            $artist = $offer->artist;
            $order = $offer->order;
            $payload['model_type'] = ModelName::BIDDING_ORDER_ARTIST->value;
            $payload['model_id'] = $offer->id;
            $cost = $offer->cost;
        } else {
            /** @var Order $order */
            $order = $this->orderRepository->findById($payload['order_id'], relations: ['artist']);
            // [SECURITY] Only the order's client may rate it (B2 IDOR).
            abort_unless((int) $order->client_id === (int) auth()->id(), 403);
            if (!$order->is_complete)
                return false;
            $payload['model_type'] = ModelName::ORDER->value;
            $payload['model_id'] = $order->id;
            $artist = $order->artist;
            $cost = $order->cost_value;
        }

        // [SECURITY] One rating — and one wallet credit — per model per client. Without this,
        // a client could call this repeatedly to inflate any artist's withdrawable balance (B2).
        $alreadyRated = Rating::query()
            ->where('client_id', auth()->id())
            ->where('model_type', $payload['model_type'])
            ->where('model_id', $payload['model_id'])
            ->exists();
        if ($alreadyRated)
            return false;

        $payload['client_id'] = auth()->id();
        $payload['artist_id'] = $artist->id;
        $this->rateRepository->create($payload);

        // [BUG] Was reading the VAT setting as the platform-fee %. Use the artist's platform_fees
        // accessor, which already falls back to the PLATFORM_FEES setting (B2).
        $platformFees = (float) ($artist->platform_fees ?? 0);

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
