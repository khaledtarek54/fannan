<?php

namespace App\Http\Resources\Ad;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdResource extends JsonResource
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
            "link" => $this->link,
            "image" => $this->image_url,
            "model_type" => $this->model_type_string,
            "model_id" => $this->adable_id,
            "created_at" => $this->created_at->format('Y/m/d h:i:s A'),
        ];
    }
}
