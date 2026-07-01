<?php

namespace App\Services\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param array $params
     * @param array $columns
     * @param int $pagination
     * @param int $relations
     * @return LengthAwarePaginator
     */
    public function index(array $params = [], array $columns = ['*'], int $pagination = 25, array $relations=[]): LengthAwarePaginator;
}
