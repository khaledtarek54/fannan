<?php

namespace App\Http\Resources\Order;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Http\Resources\BiddingOrderOfferResource;
use App\Http\Resources\CounterpartyResource;
use App\Http\Resources\OrderCategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArtistOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // [SECURITY][R2-H3] Expose the client's contact (email/phone/whatsapp) only for a confirmed
        // engagement — not in the artist's browse/incoming-orders list.
        $confirmed = (bool) $this->is_paid || in_array($this->status_value, [
            OrderStatus::ACCEPTED->value,
            OrderStatus::IN_PAYMENT->value,
            OrderStatus::COMPLETED->value,
        ], true);

        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'client' => new CounterpartyResource($this->client, $confirmed),
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
            'status' => $this->status_value,
            'status_text' => $this->status_text,
            'status_reason' => $this->status_reason,
            'days_count' => $this->dates->count(),
            'dates' => $this->dates,
            'hours_count' => $this->hours_count,
            'cost' => $this->cost_value,
            'create_at' => $this->created_at->format('Y/M/d H:i:s A'),
            'is_complete' => $this->is_complete,
            'categories' => OrderCategoryResource::collection($this->categories),
            'offers' => $this->offers->last(),
            'bidding_offers' =>  BiddingOrderOfferResource::collection($this->biddingOrderArtists->where('artist_id', auth()->id())),
            'is_paid' => $this->is_paid,
            'image' => asset('images/image.png')
        ];
    }
}
