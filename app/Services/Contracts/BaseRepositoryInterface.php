<?php

namespace App\Services\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    /**
     * @param array $params
     * @param array $columns
     * @param int $pagination
     * @param array $relations
     * @return LengthAwarePaginator
     */
    public function index(array $params = [], array $columns = ['*'], int $pagination = 25, array $relations = []): LengthAwarePaginator;

    /**
     * @param array $payload
     * @return Model|null
     */
    public function create(array $payload): ?Model;


    /**
     * @param int $modelId
     * @return Model
     */
    public function findById(int $modelId): Model;

}
