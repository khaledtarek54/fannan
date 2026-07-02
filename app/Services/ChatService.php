<?php

namespace App\Services;

use App\Models\Chat;
use App\Notifications\NewMessageNotification;
use App\Services\Contracts\ChatRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ChatService
{
    public function __construct(protected readonly ChatRepositoryInterface $chatRepository)
    {
    }

    public function chat(array $payload): Collection
    {
        return $this->chatRepository->chatMessages($payload);
    }

    /**
     * @param array $payload
     * @return bool
     */
    public function create(array $payload): bool
    {
        $payload['from_user_id'] = auth()->id();
        /** @var Chat $model */
        $model = $this->chatRepository->create($payload);
        $user = $model->toUser;
        $fromUser = $model->fromUser;
        try {
            $user->notify(new NewMessageNotification($model, $fromUser));
        } catch (\Exception $exception) {
            Log::info("Error while notify the user");
        }
        return true;
    }
}
