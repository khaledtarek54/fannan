<?php

namespace App\Services\Concerns;

use App\Models\Coupon;
use App\QueryBuilders\CouponQueryBuilder;
use App\Services\Contracts\CouponRepositoryInterface;

class CouponRepository extends BaseRepository implements CouponRepositoryInterface
{
    public function __construct(Coupon $coupon, CouponQueryBuilder $couponQueryBuilder)
    {
        $this->setModel($coupon)->setQueryBuilder($couponQueryBuilder);
    }

    public function getCouponByCode(string $code): Coupon
    {
        return $this->model->where('code', $code)->first();
    }
}
