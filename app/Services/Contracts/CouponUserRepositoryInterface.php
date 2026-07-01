<?php

namespace App\Services\Contracts;

use Illuminate\Database\Eloquent\Model;

interface CouponUserRepositoryInterface extends BaseRepositoryInterface
{
    public function checkIfExists(int $userId, int $couponId): ?Model;
}
