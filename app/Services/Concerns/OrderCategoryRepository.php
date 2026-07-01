<?php

namespace App\Services\Concerns;

use App\Models\OrderCategory;
use App\QueryBuilders\OrderCategoryQueryBuilder;
use App\Services\Contracts\OrderCategoryRepositoryInterface;

class OrderCategoryRepository extends BaseRepository implements OrderCategoryRepositoryInterface
{
    public function __construct(OrderCategory $orderCategory, OrderCategoryQueryBuilder $orderCategoryQueryBuilder)
    {
        $this->setModel($orderCategory)->setQueryBuilder($orderCategoryQueryBuilder);
    }
}
