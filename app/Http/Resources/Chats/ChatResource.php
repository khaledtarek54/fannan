<?php

namespace App\Http\Resources\Chats;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_user_id' => $this->from_user_id,
            'from_user_name' => $this->fromUser?->name,
            'from_user_profile' => $this->fromUser?->profile_photo_string,
            'to_user_id' => $this->to_user_id,
            'to_user_name' => $this->toUser?->name,
            'to_user_profile' => $this->toUser?->profile_photo_string,
            'type' => $this->type,
            'message' => $this->message,
            'is_read' => $this->is_read,
            'reply_to' => $this->reply_to,
            'reply' => $this->reply,
        ];
    }
}
