<?php

namespace App\Notifications;

use App\Enums\ModelName;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewBiddingOfferNotification extends PushNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected Order $order, protected User $artist)
    {
        parent::__construct();
    }

    public function toFcm($notifiable)
    {
        $this->notification = Notification::create([
            'type' => 'bidding_order_offer',
            'user_id' => $this->artist->id,
            'to_user_id' => $notifiable->id,
            'title' => 'bidding_offer',
            'body' => 'bidding_order_artist_offer',
            'model_type' => ModelName::ORDER->value,
            'model_id' => $this->order->id,
        ]);
        return parent::toFcm($notifiable);
    }
}
