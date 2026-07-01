<?php

namespace App\Http\Resources\Order;

use App\Http\Resources\ArtistResource;
use App\Http\Resources\BiddingOrderOfferResource;
use App\Http\Resources\Client\ClientResource;
use App\Http\Resources\OrderCategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BiddingOrderResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'client' => new ClientResource($this->client),
            'name' => $this->name,
            'number' => $this->number,
            'type' => $this->type,
            'city' => $this->address?->city?->name,
            'latitude' => $this->address?->latitude,
            'longitude' => $this->address?->longitude,
            'address_name' => $this->address?->name,
            'address_description' => $this->address?->description,
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
