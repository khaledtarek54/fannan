<?php

namespace App\Http\Controllers\API;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\AcceptOrderRequest;
use App\Http\Requests\Order\CheckoutRequest;
use App\Http\Requests\Order\CounterOfferRequest;
use App\Http\Requests\Order\OrderIdRequest;
use App\Http\Requests\Order\RejectOrderRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\Order\ArtistOrderResource;
use App\Http\Resources\Order\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService)
    {
    }

    public function index(): JsonResponse
    {
        $orders = OrderResource::collection($this->orderService->index());
        return response()->json([
            'orders' => $orders,
            'status' => true,
        ]);
    }

    public function artistOrders(): JsonResponse
    {
        $orders = ArtistOrderResource::collection($this->orderService->artistOrders());
        return response()->json([
            'orders' => $orders,
            'status' => true,
        ]);
    }

    public function store(StoreOrderRequest $storeOrderRequest): JsonResponse
    {
        $order = $this->orderService->store($storeOrderRequest->all());
        return response()->json([
            'order' => new OrderResource($order->load('dates')),
            'status' => true,
        ]);
    }

    public function accept(AcceptOrderRequest $acceptOrderRequest): JsonResponse
    {
        $data = $this->orderService->acceptOrder($acceptOrderRequest->all());
        return response()->json([
            'message' => trans('app.done'),
            'status' => true
        ]);
    }

    public function offer(CounterOfferRequest $counterOfferRequest): JsonResponse
    {
        $data = $this->orderService->counterOffer($counterOfferRequest->all());
        return response()->json([
            'data' => $data,
            'status' => true
        ]);
    }

    public function reject(RejectOrderRequest $rejectOrderRequest): JsonResponse
    {
        $data = $this->orderService->updateStatus($rejectOrderRequest->order_id, OrderStatus::REJECTED->value, $rejectOrderRequest->reason);
        return response()->json([
            'data' => $data,
            'status' => true
        ]);
    }

    public function checkout(CheckoutRequest $paymentRequest): JsonResponse
    {
        $data = $this->orderService->checkout($paymentRequest->all());
        return response()->json([
            'data' => $data,
            'status' => true
        ]);
    }

    public function cancel(OrderIdRequest $orderIdRequest): JsonResponse
    {
        $this->orderService->cancel($orderIdRequest->order_id, OrderStatus::CANCELED->value);
        return response()->json([
            'message' => trans('app.done'),
            'status' => true
        ]);
    }
}
