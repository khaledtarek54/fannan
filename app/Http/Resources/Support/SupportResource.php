<?php

namespace App\Http\Resources\Support;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportResource extends JsonResource
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
            "user_id" => $this->user_id,
            "reply_user_id" => $this->reply_user_id,
            "reply_user_name" => $this->replyUser?->name,
            "name" => $this->name,
            "phone" => $this->phone,
            "email" => $this->email,
            "description" => $this->description,
            'model_type' => $this->model_type,
            'model_id' => $this->model_id,
            'created_at' => $this->created_at->format('Y-M-d h:i A'),
        ];
    }
}
