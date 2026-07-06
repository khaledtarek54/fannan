<?php

namespace App\Http\Resources\Order;

use App\Http\Resources\BiddingOrderOfferResource;
use App\Http\Resources\CounterpartyResource;
use App\Http\Resources\OrderCategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BiddingOrderResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        // [SECURITY][R2-H1] The bidding browse/list is intentionally open to any artist so they can
        // decide whether to bid; expose the client's PII + exact location ONLY to the order owner or
        // an already-accepted artist. Everyone else gets city-level, contact-free data.
        $canSeeDetails = auth()->id() === $this->client_id
            || $this->biddingOrderArtists->where('artist_id', auth()->id())->where('is_accepted', 1)->isNotEmpty();

        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'client' => new CounterpartyResource($this->client, $canSeeDetails),
            'name' => $this->name,
            'number' => $this->number,
            'type' => $this->type,
            'city' => $this->address?->city?->name,
            'latitude' => $canSeeDetails ? $this->address?->latitude : null,
            'longitude' => $canSeeDetails ? $this->address?->longitude : null,
            'address_name' => $canSeeDetails ? $this->address?->name : null,
            'address_description' => $canSeeDetails ? $this->address?->description : null,
            'description' => $this->description,
            'status' => $this->status_value,
            'status_text' => $this->status_text,
            'status_reason' => $this->status_reason,
            'cost' => $this->cost_value,
            'categories' => OrderCategoryResource::collection($this->categories),
            'dates' => $this->dates,
            'offers' => $this->offers->last(),
            'bidding_offers' => BiddingOrderOfferResource::collection($this->biddingOrderArtists->where('artist_id', auth()->id())),
            'days_count' => $this->dates->count(),
            'hours_count' => $this->hours_count,
            'is_paid' => $this->is_paid,
            'create_at' => $this->created_at->format('Y/M/d H:i:s A'),
            'image' => asset('images/image.png')
        ];
    }
}
