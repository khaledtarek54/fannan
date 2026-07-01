<?php

namespace App\Observers;

use App\Enums\OrderStatus;
use App\Models\BiddingOrderArtist;
use Illuminate\Support\Facades\Log;

class UpdateBiddingOrderStatus
{
    /**
     * Handle the BiddingOrderArtist "created" event.
     */
    public function created(BiddingOrderArtist $biddingOrderArtist): void
    {

    }

    /**
     * Handle the BiddingOrderArtist "updated" event.
     */
    public function updated(BiddingOrderArtist $biddingOrderArtist): void
    {
        $order = $biddingOrderArtist->order;
        $categories = $order->categories->count();
        $acceptedBiddingCategories = BiddingOrderArtist::query()->where('order_id', $order->id)->where('is_accepted', 1)->count();
        Log::info('acceptedBiddingCategories : ' . $acceptedBiddingCategories);
        Log::info('categories : ' . $categories);
        if ($acceptedBiddingCategories >= $categories)
            $order->setStatus(OrderStatus::ACCEPTED->value);
        Log::info("Order updated to accepted");
    }

    /**
     * Handle the BiddingOrderArtist "deleted" event.
     */
    public function deleted(BiddingOrderArtist $biddingOrderArtist): void
    {
        //
    }

    /**
     * Handle the BiddingOrderArtist "restored" event.
     */
    public function restored(BiddingOrderArtist $biddingOrderArtist): void
    {
        //
    }

    /**
     * Handle the BiddingOrderArtist "force deleted" event.
     */
    public function forceDeleted(BiddingOrderArtist $biddingOrderArtist): void
    {
        //
    }
}
