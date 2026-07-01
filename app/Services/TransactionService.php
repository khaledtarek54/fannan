<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Services\Contracts\TransactionRepositoryInterface;

class TransactionService
{
    public function __construct(protected TransactionRepositoryInterface $transactionRepository)
    {
    }

    public function storeNewRequest(array $payload)
    {
        $user = auth()->user();
        $netAmount = $user->total_income - $user->total_withdraw;
        $data = new \stdClass();
        if ($netAmount < $payload['amount']) {
            $data->status = false;
            $data->message = trans('app.invalid_amount');
            return $data;
        }
        $payload['user_id'] = $user->id;
        $payload['type'] = TransactionType::WITHDRAW->value;
        $this->transactionRepository->create($payload);
        $data->status = true;
        $data->message = trans('app.done');
        return $data;
    }

    public function getTransactions(): \stdClass
    {
        $transactions = $this->transactionRepository->index()->items();
        $user = auth()->user();
        $totalIncome = $user->total_income;
        $totalWithdraw = $user->total_withdraw;

        $data = new \stdClass();
        $data->transactions = $transactions;
        $data->total_income = $totalIncome;
        $data->total_withdraw = $totalWithdraw;
        $data->net_amount = $totalIncome - $totalWithdraw;
        return $data;
    }

}
