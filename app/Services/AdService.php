<?php

namespace App\Services;

use App\Services\Contracts\AdRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdService
{
    public function __construct(protected AdRepositoryInterface $adRepositoryInterface)
    {
    }

    /**
     * @param array $columns
     * @param int $pagination
     * @return LengthAwarePaginator
     */
    public function index(array $params = [],array $columns = ['*'], int $pagination = 25): LengthAwarePaginator
    {
        return $this->adRepositoryInterface->index($params);
    }

}
