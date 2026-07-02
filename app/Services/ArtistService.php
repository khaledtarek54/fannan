<?php

namespace App\Services;

use App\Dtos\CategoryDto;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\Contracts\ArtistRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ArtistService
{
    public function __construct(protected ArtistRepositoryInterface $artistRepository)
    {
    }

    public function index(array $params)
    {
        return $this->artistRepository->index($params)->where('completed_profile', true);
    }

    public function getAllArtists(array $params)
    {
        $params['filter'] = array_merge($params['filter'] ?? [], [
            'role' => UserRole::ARTIST->value,
            'completed_profile' => true,
        ]);

        return $this->artistRepository->index($params);
    }

    /**
     * @param array<CategoryDto> $categories
     */
    public function updateCategories(array $categories): bool
    {
        /** @var User $artist */
        $artist = Auth::user();
        return $this->artistRepository->updateCategories($categories, $artist->id);
    }

    public function profile(): Model
    {
        $artist = Auth::user();
        return $this->artistRepository->profile($artist->id);
    }

    /**
     * @param int $modelId
     * @return Model
     */
    public function findById(int $modelId): Model
    {
        $relations = ['ratings', 'userCategories.category', 'userCategories.subcategory', 'userCategories.priceRange'];
        return $this->artistRepository->findById($modelId, ['*'], $relations);
    }

    public function deleteAccount(): bool
    {
        /** @var User $artist */
        $artist = Auth::user();
        return $this->artistRepository->delete($artist->id);
    }
}
