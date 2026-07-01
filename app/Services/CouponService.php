<?php

namespace App\Services;

use App\Models\Coupon;
use App\Services\Contracts\CouponRepositoryInterface;
use App\Services\Contracts\CouponUserRepositoryInterface;

class CouponService
{

    public function __construct(
        protected readonly CouponRepositoryInterface     $couponRepository,
        protected readonly CouponUserRepositoryInterface $couponUserRepository,
    )
    {
    }

    /**
     * @param string $code
     * @return \stdClass
     */
    public function getCouponByCode(string $code): \stdClass
    {
        $coupon = $this->couponRepository->getCouponByCode($code);
        $usedCoupon = $this->couponUserRepository->checkIfExists(auth()->id(), $coupon->id);
        $data = new \stdClass();
        $data->valid = (bool)$usedCoupon;
        $data->coupon = $coupon;
        $data->message = $usedCoupon ? trans('app.coupon_already_used') : trans('app.done');
        return $data;
    }
}
