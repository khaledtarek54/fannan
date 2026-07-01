<?php

namespace App\Services\Contracts;

use App\Models\Coupon;

interface CouponRepositoryInterface extends BaseRepositoryInterface
{
    public function getCouponByCode(string $code): Coupon;
}
