<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BiddingOrderOfferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $artist = $this->artist;
        return [
            'id' => $this->id,
            'cost' => $this->cost,
            'status' => $this->status,
            'status_text' => trans('app.' . $this->status),
            'artist_id' => $this->artist_id,
            'artist' => new UserResource($artist),
            'average_rate' => (float)$artist?->rating_value,
            'rates' => $artist?->ratings->count(),
            'is_accepted' => $this->is_accepted,
            'subcategory' => new SubCategoryResource($this->subcategory),
            'subcategory_name' => $this->subcategory?->name,
            'category_name' => $this->subcategory?->category?->name,
            'offer_rate' => $this->rating,
            'created_at' => Carbon::parse($this->created_at)->format('Y-M-d h:i A'),
        ];
    }
}
