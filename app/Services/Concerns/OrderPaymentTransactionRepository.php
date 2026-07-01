<?php

namespace App\Services\Concerns;

use App\Models\OrderPaymentTransaction;
use App\Services\Contracts\OrderPaymentTransactionRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class OrderPaymentTransactionRepository extends BaseRepository implements OrderPaymentTransactionRepositoryInterface
{
    public function __construct(OrderPaymentTransaction $orderPaymentTransaction)
    {
        $this->setModel($orderPaymentTransaction);
    }

    public function findByCheckoutId(string $checkoutId): Model|null
    {
        return $this->model->where('checkout_id', $checkoutId)->first();
    }
}
