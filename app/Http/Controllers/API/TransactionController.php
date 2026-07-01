<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{

    public function __construct(protected readonly TransactionService $transactionService)
    {
    }

    public function transactions(): JsonResponse
    {
        $data = $this->transactionService->getTransactions();
        return response()->json([
            'data' => $data,
            'status' => true,
        ]);
    }

    public function request(StoreTransactionRequest $storeTransactionRequest)
    {
        $data = $this->transactionService->storeNewRequest($storeTransactionRequest->all());
        if ($data->status)
            return response()->json([
                'message' => $data->message,
                'status' => true,
            ]);

        return response()->json([
            'message' => $data->message,
            'status' => true,
        ], 400);
    }
}
