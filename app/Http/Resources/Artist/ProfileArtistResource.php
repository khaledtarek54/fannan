<?php

namespace App\Http\Resources\Artist;

use App\Enums\FileType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileArtistResource extends JsonResource
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
            'categories' => $this->categories_names,
            'completed_events' => $this->artistCompletedOrders->count(),
            'average_rate' => (float)$this->rating_value,
            'rates' => $this->ratings->count(),
            'categories_list' => $this->categories_list,
            "works" => $this->works,
            "videos_count" => $this->works->where('type', FileType::VIDEO->value)->count(),
            "images_count" => $this->works->where('type', FileType::IMAGE->value)->count(),
            "completed_profile" => (bool)$this->completed_profile,
            'facebook' => $this->facebook,
            'instagram' => $this->instagram,
            'youtube' => $this->youtube,
            'snapchat' => $this->snapchat,
            'whatsapp' => $this->whatsapp,
        ];
    }

}
