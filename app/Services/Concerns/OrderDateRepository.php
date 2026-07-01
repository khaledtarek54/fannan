<?php

namespace App\Services\Concerns;

use App\Models\OrderDate;
use App\QueryBuilders\OrderDateQueryBuilder;
use App\Services\Contracts\OrderDateRepositoryInterface;

class OrderDateRepository extends BaseRepository implements OrderDateRepositoryInterface
{
    public function __construct(OrderDate $orderDate, OrderDateQueryBuilder $orderDateQueryBuilder)
    {
        $this->setModel($orderDate)->setQueryBuilder($orderDateQueryBuilder);
    }

}
