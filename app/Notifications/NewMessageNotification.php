<?php

namespace App\Notifications;

use App\Enums\ModelName;
use App\Models\Chat;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewMessageNotification extends PushNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Chat $chat, protected User $fromUser)
    {
        parent::__construct();
    }

    public function toFcm($notifiable)
    {
        $this->notification = Notification::create([
            'type' => 'new_event',
            'user_id' => $this->fromUser->id,
            'to_user_id' => $notifiable->id,
            'title' => 'new_message',
            'body' => ' ',
            'model_type' => ModelName::CHAT->value,
            'model_id' => $this->chat->id,
        ]);
        return parent::toFcm($notifiable);
    }

}
