<?php

namespace App\Http\Resources;

use App\Enums\FileType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ArtistResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "email" => $this->email,
            "phone" => $this->phone,
            "phone_prefix" => $this->phone_prefix,
            'role' => $this->role,
            "dob" => Carbon::parse($this->dob)->format('Y-M-d H:i A'),
            "gender" => $this->gender,
            "city" => $this->city->name,
            "vat_number" => $this->vat_number,
            "cr_number" => $this->cr_number,
            'from_range' => $this->random_range?->from ?? 0,
            'to_range' => $this->random_range?->to ?? 0,
            "completed_profile" => (bool)$this->completed_profile,
            "profile_photo" => Storage::url($this->profile_photo),
            "booked_times" => $this->dates->select(['id', 'start_date', 'end_date']),
            "videos_count" => $this->works->where('type', FileType::VIDEO->value)->count(),
            "images_count" => $this->works->where('type', FileType::IMAGE->value)->count(),
            'categories' => $this->categories_names,
            'average_rate' => (float)$this->rating_value,
            'facebook' => $this->facebook,
            'instagam' => $this->instagam,
            'twiteer' => $this->twiteer,
            'snapchat' => $this->snapchat,
            'whatsapp' => $this->whatsapp,
        ];
    }
}
