<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\CheckoutOrderRequest;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected readonly PaymentService $paymentService
    )
    {
    }

    public function checkout(CheckoutOrderRequest $request)
    {
        $data = $this->paymentService->checkout($request->all());
        if (!$data['status'])
            return response()->json($data, 400);
        return response()->json($data);
    }

    public function checkPaymentStatus(Request $request)
    {
        $data = $this->paymentService->checkPaymentStatus($request);
        if (!$data['status'])
            return response()->json($data, 400);
        return response()->json($data);
    }

    public function webhook(Request $request)
    {
        $data = $this->paymentService->getPaymentStatus($request);
        if (!$data['status'])
            return response()->json($data, 400);
        return response()->json($data);
    }
}
