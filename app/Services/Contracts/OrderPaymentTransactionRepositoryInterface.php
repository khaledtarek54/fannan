<?php

namespace App\Services\Contracts;

use Illuminate\Database\Eloquent\Model;

interface OrderPaymentTransactionRepositoryInterface extends BaseRepositoryInterface
{
    public function findByCheckoutId(string $checkoutId): Model|null;

}
