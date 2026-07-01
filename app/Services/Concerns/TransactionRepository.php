<?php

namespace App\Services\Concerns;

use App\Models\Transaction;
use App\QueryBuilders\TransactionQueryBuilder;
use App\Services\Contracts\TransactionRepositoryInterface;

class TransactionRepository extends BaseRepository implements TransactionRepositoryInterface
{

    public function __construct(Transaction $transaction, TransactionQueryBuilder $transactionQueryBuilder)
    {
        $this->setModel($transaction)->setQueryBuilder($transactionQueryBuilder);
    }
}
