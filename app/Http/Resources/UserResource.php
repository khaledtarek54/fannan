<?php

namespace App\Http\Resources;

use App\Enums\FileType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'phone' => $this->phone,
            'phone_prefix' => $this->phone_prefix,
            'role' => $this->role,
            'fcm_token' => $this->fcm_token,
            'email' => $this->email,
            'city' => $this->city ?: $this->cityRelation?->name,
            'city_id' => $this->city_id,
            'date_of_birth' => $this->dob,
            'gender' => $this->gender,
            'profile' => $this->profile_photo ? Storage::url($this->profile_photo) : "",
            "completed_profile" => (bool)$this->completed_profile,
            'latitude' => (float)$this->latitude,
            'longitude' => (float)$this->longitude,
            'vat_number' => $this->vat_number ?? 0,
            'cr_number' => $this->cr_number ?? 0,
            'from_range' => $this->random_range?->from ?? 0,
            'to_range' => $this->random_range?->to ?? 0,
            "videos_count" => $this->works->where('type', FileType::VIDEO->value)->count(),
            "images_count" => $this->works->where('type', FileType::IMAGE->value)->count(),
            'categories' => $this->categories_names,
            'subcategories_names' => $this->subcategories_names,
            'average_rate' => (float)$this->rating_value,
            'rates' => $this->ratings->count(),
            'categories_list' => $this->categories_list,
            'facebook' => $this->facebook,
            'instagram' => $this->instagram,
            'youtube' => $this->youtube,
            'snapchat' => $this->snapchat,
            'whatsapp' => $this->whatsapp,
        ];
    }
}
