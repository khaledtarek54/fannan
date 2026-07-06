<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Supports\StoreSupportRequest;
use App\Http\Resources\Support\SupportResource;
use App\Services\SupportService;
use Illuminate\Http\JsonResponse;

class SupportController extends BaseController
{

    public function __construct(protected readonly SupportService $supportService)
    {
    }

    public function index()
    {
        $supports = $this->supportService->all();
        return response()->json([
            'data' => SupportResource::collection($supports),
            'status' => true,
        ]);
    }


    public function store(StoreSupportRequest $storeSupportRequest): JsonResponse
    {
        $support = $this->supportService->create($storeSupportRequest->all());
        return response()->json([
            // [SECURITY][R2-L5] Return the shaped resource, not the raw Eloquent model.
            'data' => new SupportResource($support),
            'status' => true,
        ]);
    }

}
