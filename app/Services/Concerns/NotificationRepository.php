<?php

namespace App\Services\Concerns;


use App\Models\Notification;
use App\QueryBuilders\NotificationQueryBuilder;
use App\Services\Contracts\NotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class NotificationRepository extends BaseRepository implements NotificationRepositoryInterface
{
    public function __construct(Notification $notification, NotificationQueryBuilder $notificationQueryBuilder)
    {
        $this->setModel($notification)->setQueryBuilder($notificationQueryBuilder);
    }

    public function all(): Collection|array
    {
        return $this->model->newQuery()
            ->where('user_id', auth()->id())
            ->orWhere('to_user_id', auth()->id())
            ->get();
    }

    public function markAsRead(): int
    {
        return $this->model->newQuery()
            ->where('user_id', auth()->id())
            ->orWhere('to_user_id', auth()->id())
            ->where('is_read', false)
            ->update([
                'is_read' => true
            ]);
    }


    public function getUnreadNotifications(): int
    {
        if (!auth()->id())
            return 0;

       return $this->model->newQuery()
    ->where(function ($q) {
        $q->where('user_id', auth()->id())
          ->orWhere('to_user_id', auth()->id());
    })
    ->where('is_read', 0)
    ->count();

    }
}
