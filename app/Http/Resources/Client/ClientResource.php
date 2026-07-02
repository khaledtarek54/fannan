<?php

namespace App\Http\Resources\Client;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ClientResource extends JsonResource
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
            "dob" => Carbon::parse($this->dob)->format('d-M-Y'),
            "gender" => $this->gender,
            "city" => $this->city ?: $this->cityRelation?->name,
            "vat_number" => $this->name,
            "cr_number" => $this->name,
            "completed_profile" => (bool)$this->completed_profile,
            "profile" => $this->profile_photo ? Storage::url($this->profile_photo) : "",
            'facebook' => $this->facebook,
            'instagram' => $this->instagram,
            'youtube' => $this->youtube,
            'snapchat' => $this->snapchat,
            'whatsapp' => $this->whatsapp,
        ];
    }
}
