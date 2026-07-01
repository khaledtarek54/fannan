<?php

namespace App\Services;

use App\Services\Concerns\NotificationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class NotificationService
{

    public function __construct(protected readonly NotificationRepository $notificationRepository)
    {
    }

    /**
     * @return  Collection | array
     */
    public function all(): Collection|array
    {
        $this->notificationRepository->markAsRead();
        return $this->notificationRepository->all();
    }

    public function unreadNotificationsCount(): int
    {
        return $this->notificationRepository->getUnreadNotifications();
    }

}
