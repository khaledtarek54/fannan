<?php

namespace App\Services\Contracts;

interface NotificationRepositoryInterface extends BaseRepositoryInterface
{

    public function markAsRead(): int;

    public function getUnreadNotifications(): int;

}
