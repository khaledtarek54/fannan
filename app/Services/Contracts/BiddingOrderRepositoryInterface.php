<?php

namespace App\Services\Contracts;

interface BiddingOrderRepositoryInterface extends BaseRepositoryInterface
{

    public function artistHomeBiddingOrders(array $payload);
}
