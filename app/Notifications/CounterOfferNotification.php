<?php

namespace App\Notifications;

use App\Enums\ModelName;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class CounterOfferNotification extends PushNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Order $order, protected User $client)
    {
        parent::__construct();
    }

    public function toFcm($notifiable)
    {
        $this->notification = Notification::create([
            'type' => 'counter_offer',
            'user_id' => $this->client->id,
            'to_user_id' => $notifiable->id,
            'title' => 'counter_offer',
            'body' => 'counter_offer_by_client',
            'model_type' => ModelName::ORDER->value,
            'model_id' => $this->order->id,
        ]);
        return parent::toFcm($notifiable);
    }
}
