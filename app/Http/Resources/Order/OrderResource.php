<?php

namespace App\Http\Resources\Order;

use App\Http\Resources\ArtistResource;
use App\Http\Resources\BiddingOrderOfferResource;
use App\Http\Resources\OrderCategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'artist_id' => $this->artist_id,
            'name' => $this->name,
            'number' => $this->number,
            'type' => $this->type,
            'artist_name' => $this->artist?->name,
            'city' => $this->address?->city?->name,
            'latitude' => $this->address?->latitude,
            'longitude' => $this->address?->longitude,
            'address_name' => $this->address?->name,
            'address_description' => $this->address?->description,
            'description' => $this->description,
            'average_rate' => (float)$this->artist?->rating_value ?? 0,
            'rates' => $this->artist?->ratings->count(),
            'status' => $this->status_value,
            'status_text' => $this->status_text,
            'status_reason' => $this->status_reason,
            'cost' => $this->cost_value,
            'is_complete' => $this->is_complete,
            'artist' => new ArtistResource($this->artist),
            'categories' => OrderCategoryResource::collection($this->categories),
            'dates' => $this->dates,
            'create_at' => $this->created_at->format('Y/M/d H:i:s A'),
            'offers' => $this->offers->last(),
            'bidding_offers' => BiddingOrderOfferResource::collection($this->acceptedBiddingOrderArtists),
            'days_count' => $this->dates->count(),
            'hours_count' => $this->hours_count,
            'is_paid' => $this->is_paid,
            'has_rating' => $this->rating,
            'image' => asset('images/image.png')

        ];
    }
}
