<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\BiddingOfferRequest;
use App\Http\Requests\Order\BiddingOrderRequest;
use App\Http\Requests\Order\OrderIdRequest;
use App\Http\Resources\Order\BiddingOrderResource;
use App\Services\BiddingOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BiddingOrderController extends Controller
{

    public function __construct(protected readonly BiddingOrderService $biddingOrderService)
    {
    }

    public function index(): JsonResponse
    {
        $orders = $this->biddingOrderService->all();
        return response()->json([
            'orders' => BiddingOrderResource::collection($orders),
            'status' => true,
        ]);
    }

    public function show(OrderIdRequest $orderIdRequest): JsonResponse
    {
        $order = $this->biddingOrderService->show($orderIdRequest->order_id);
        return response()->json([
            'orders' => new BiddingOrderResource($order),
            'status' => true,
        ]);
    }

    public function store(BiddingOrderRequest $biddingOrderRequest): JsonResponse
    {
        $order = $this->biddingOrderService->store($biddingOrderRequest->all());
        return response()->json([
            'order' => new BiddingOrderResource($order->load('dates')),
            'status' => true,
        ]);
    }

    public function offer(BiddingOfferRequest $biddingOfferRequest): JsonResponse
    {
        $data = $this->biddingOrderService->storeOffer($biddingOfferRequest->all());
        if (!$data->status) {
            return response()->json([
                'status' => true,
                'message' => $data->message,
            ], 400);
        }
        return response()->json([
            'order' => $data->model,
            'status' => true,
        ]);
    }

    public function available(Request $request)
    {
        $biddings = BiddingOrderResource::collection($this->biddingOrderService->artistHomeBiddingOrders($request->all()));
        return response()->json([
            'biddings' => $biddings,
            'status' => true,
        ]);
    }
}
