<?php

namespace App\Services\Concerns;

use App\Models\Address;
use App\QueryBuilders\AddressQueryBuilder;
use App\Services\Contracts\AddressRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AddressRepository extends BaseRepository implements AddressRepositoryInterface
{

    public function __construct(Address $address, AddressQueryBuilder $addressQueryBuilder)
    {
        $this->setModel($address)->setQueryBuilder($addressQueryBuilder);
    }

    public function index(array $params = [], array $columns = ['*'], int $pagination = 25, array $relations = []): LengthAwarePaginator
    {
        $relations = ['city'];
        return parent::index($params, $columns, $pagination, $relations);
    }
}
