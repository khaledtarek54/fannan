<?php

namespace App\Services\Contracts;

use App\Dtos\CategoryDto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface ArtistRepositoryInterface extends BaseRepositoryInterface
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
     * @param array<CategoryDto> $categories
     */
    public function updateCategories(array $categories, int $artistId): bool;

    /**
     * @param int $artistId
     */
    public function profile(int $artistId): Model;


    /**
     * @param int $modelId
     * @param array $columns
     * @param array $relations
     * @return Model
     */
    public function findById(int $modelId, array $columns = ['*'], array $relations = []): Model;
}
