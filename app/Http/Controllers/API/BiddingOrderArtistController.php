<?php

namespace App\Http\Controllers\API;

use App\Enums\BiddingOrderArtist;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\OfferIdRequest;
use App\Http\Resources\BiddingOrderOfferResource;
use App\Services\BiddingOrderArtistService;
use Illuminate\Http\JsonResponse;

class BiddingOrderArtistController extends Controller
{
    public function __construct(protected readonly BiddingOrderArtistService $biddingOrderArtistService)
    {
    }

    public function index(): JsonResponse
    {
        $offers = $this->biddingOrderArtistService->all();
        return response()->json([
            'offers' => BiddingOrderOfferResource::collection($offers),
            'status' => true,
        ]);
    }

    public function accept(OfferIdRequest $offerIdRequest): JsonResponse
    {
        $this->biddingOrderArtistService->updateStatus($offerIdRequest->offer_id, BiddingOrderArtist::ACCEPTED->value, OrderStatus::ACCEPTED->value);
        return response()->json([
            'message' => trans('app.success'),
            'status' => true,
        ]);
    }

    public function reject(OfferIdRequest $offerIdRequest): JsonResponse
    {
        $this->biddingOrderArtistService->updateStatus($offerIdRequest->offer_id, BiddingOrderArtist::REJECTED->value, OrderStatus::REJECTED->value);
        return response()->json([
            'message' => trans('app.done'),
            'status' => true,
        ]);
    }
}
