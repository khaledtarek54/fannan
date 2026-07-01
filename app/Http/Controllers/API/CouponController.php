<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CheckCouponValidRequest;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;

class CouponController extends Controller
{

    public function __construct(protected readonly CouponService $couponService)
    {
    }

    public function checkValidCoupon(CheckCouponValidRequest $checkCouponValidRequest): JsonResponse
    {
        $data = $this->couponService->getCouponByCode($checkCouponValidRequest->code);
        if (!$data->valid)
            return response()->json([
                'code' => $data->coupon,
                'status' => true,
            ]);

        return response()->json([
            'code' => $data->message,
            'status' => true,
        ], 422);
    }
}
