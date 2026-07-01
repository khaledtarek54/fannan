<?php

namespace App\Services\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface ArtistGalleryRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param array $params
     * @param array $columns
     * @param int $pagination
     * @param array $relations
     * @return LengthAwarePaginator
     */
    public function index(array $params = [], array $columns = ['*'], int $pagination = 25, array $relations=[]): LengthAwarePaginator;

    /**
     * @param array $payload
     * @return Model
     */
    public function create(array $payload): ?Model;
}
