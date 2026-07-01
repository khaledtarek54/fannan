<?php

namespace App\Services\Concerns;

use App\Models\CouponUser;
use App\Services\Contracts\CouponUserRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class CouponUserRepository extends BaseRepository implements CouponUserRepositoryInterface
{
    public function __construct(CouponUser $couponUser)
    {
        $this->setModel($couponUser);
    }

    public function checkIfExists(int $userId, int $couponId): ?Model
    {
        return $this->model->where('user_id', $userId)->where('coupon_id', $couponId)->first();
    }
}
