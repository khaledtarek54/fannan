<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\RatingRequest;
use App\Services\RatingService;
use Illuminate\Http\JsonResponse;

class RatingController extends Controller
{
    public function __construct(protected readonly RatingService $rateService)
    {
    }

    public function store(RatingRequest $rateRequest): JsonResponse
    {
        $status = $this->rateService->store($rateRequest->all());
        if (!$status)
            return response()->json([
                'status' => false,
                'message' => trans('app.cannot_rate_order')
            ], 400);
        return response()->json([
            'status' => true,
            'message' => trans('app.done')
        ]);
    }

}
