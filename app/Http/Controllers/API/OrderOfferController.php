<?php

namespace App\Http\Controllers\API;

use App\Enums\BiddingOrderArtist;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\OfferIdRequest;
use App\Http\Resources\BiddingOrderOfferResource;
use App\Services\OrderOfferService;
use Illuminate\Http\JsonResponse;

class OrderOfferController extends Controller
{
    public function __construct(protected readonly OrderOfferService $orderOfferService)
    {
    }

    public function index(): JsonResponse
    {
        $offers = $this->orderOfferService->all();
        return response()->json([
            'offers' => BiddingOrderOfferResource::collection($offers),
            'status' => true,
        ]);
    }

    public function accept(OfferIdRequest $offerIdRequest): JsonResponse
    {
        $this->orderOfferService->updateStatus($offerIdRequest->offer_id, BiddingOrderArtist::ACCEPTED->value);
        return response()->json([
            'message' => trans('app.success'),
            'status' => true,
        ]);
    }

    public function reject(OfferIdRequest $offerIdRequest): JsonResponse
    {
        $this->orderOfferService->updateStatus($offerIdRequest->offer_id, BiddingOrderArtist::REJECTED->value);
        return response()->json([
            'message' => trans('app.done'),
            'status' => true,
        ]);
    }
}
