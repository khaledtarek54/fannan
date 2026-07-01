<?php

namespace App\Notifications;

use App\Enums\ModelName;
use App\Models\Notification;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class CompleteOrderNotification extends PushNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Order $order)
    {
        parent::__construct();
    }

    public function toFcm($notifiable)
    {
        $this->notification = Notification::create([
            'type' => 'complete_order',
            'user_id' => $notifiable->id,
            'title' => 'complete_order',
            'body' => 'order_complete_by_artist',
            'model_type' => ModelName::ORDER->value,
            'model_id' => $this->order->id,
        ]);
        return parent::toFcm($notifiable);
    }

}
