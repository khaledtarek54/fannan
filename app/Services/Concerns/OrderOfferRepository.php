<?php

namespace App\Services\Concerns;

use App\Models\OrderOffer;
use App\QueryBuilders\OrderOfferQueryBuilder;
use App\Services\Contracts\OrderOfferRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderOfferRepository extends BaseRepository implements OrderOfferRepositoryInterface
{

    public function __construct(OrderOffer $orderOffer, OrderOfferQueryBuilder $orderOfferQueryBuilder)
    {
        $this->setModel($orderOffer)->setQueryBuilder($orderOfferQueryBuilder);
    }

    public function index(array $params = [], array $columns = ['*'], int $pagination = 25, array $relations = []): LengthAwarePaginator
    {
        return parent::index(relations: ['artist', 'subcategory.category']);
    }
}
