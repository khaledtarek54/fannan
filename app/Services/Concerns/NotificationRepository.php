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
        // [BUG] Group the OR so the is_read filter applies to both sides — otherwise this parsed as
        // `user_id=me OR (to_user_id=me AND is_read=false)` and over-matched. See CODE_REVIEW_FINDINGS.md B6.
        return $this->model->newQuery()
            ->where(function ($q) {
                $q->where('user_id', auth()->id())
                    ->orWhere('to_user_id', auth()->id());
            })
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
            ->where('is_read', false)
            ->count();
    }
}
