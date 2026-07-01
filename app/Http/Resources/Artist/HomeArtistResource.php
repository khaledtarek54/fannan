<?php

namespace App\Http\Resources\Artist;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeArtistResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'profile' => $this->profile_photo_string,
            'from_range' =>$this->random_range?->from ?? 0,
            'to_range' => $this->random_range?->to ?? 0,
            "created_at" => $this->created_at->format('Y/m/d h:i:s A'),
            'average_rate' => (float)$this->rating_value,
            'rates' => $this->ratings->count(),
            'categories' => $this->categories_names,
        ];
    }
}
