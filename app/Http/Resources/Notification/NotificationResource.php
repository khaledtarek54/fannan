<?php

namespace App\Http\Resources\Notification;

use App\Enums\ModelName;
use App\Http\Resources\Order\BiddingOrderResource;
use App\Http\Resources\Order\OrderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $model = null;
        if ($this->model_type == ModelName::ORDER->value)
            $model = new OrderResource($this->model);
        elseif ($this->model_type == ModelName::BIDDING_ORDER_ARTIST->value)
            $model = new BiddingOrderResource($this->model);
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'model_type' => $this->model_type,
            'model_id' => $this->model_id,
           'is_read' => $this->is_read,
            'order' => $model,
        ];
    }
}
