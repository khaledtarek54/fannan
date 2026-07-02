<?php

namespace App\Services;

use App\Models\BiddingOrderArtist;
use App\Notifications\BiddingOfferStatusNotification;
use App\Observers\UpdateBiddingOrderStatus;
use App\Services\Contracts\BiddingOrderArtistRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class BiddingOrderArtistService
{
    public function __construct(protected readonly BiddingOrderArtistRepositoryInterface $biddingOrderArtistRepository)
    {
    }

    public function all(): LengthAwarePaginator
    {
        return $this->biddingOrderArtistRepository->all();
    }

    public function updateStatus(int $modelId, bool $isAccepted, string $status): bool
    {
        /** @var BiddingOrderArtist $model */
        $model = $this->biddingOrderArtistRepository->findById($modelId, relations: ['artist', 'order']);
        // [SECURITY] Only the client who owns the bidding order may accept/reject its offers (M5 IDOR).
        abort_unless($model->order && (int) $model->order->client_id === (int) auth()->id(), 403);
        $model->is_accepted = $isAccepted;
        $model->save();
        $model->setStatus($status);
        $artist = $model->artist;
        $order = $model->order;
        $client = $model->order?->client;
        try {
            if ($isAccepted) {
                $this->biddingOrderArtistRepository->rejectPendingOffers($order->id, $model->subcategory_id);
                $title = "accept_offer";
                $body = "accept_your_offer_by_client";
            } else {
                $title = "reject_offer";
                $body = "reject_your_offer_by_client";
            }
            $artist->notify(new BiddingOfferStatusNotification($order, $title, $body, $client));

        } catch (\Exception $exception) {
            Log::info("Error in sending notification");
        }
        event(new UpdateBiddingOrderStatus($order));
        return true;

    }

}
