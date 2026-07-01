<?php

namespace App\Services\Concerns;


use App\Models\User;
use App\QueryBuilders\ArtistQueryBuilder;
use App\Services\Contracts\ArtistRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class ArtistRepository extends BaseRepository implements ArtistRepositoryInterface
{
    public function __construct(User $user, ArtistQueryBuilder $artistQueryBuilder)
    {
        $this->setModel($user)->setQueryBuilder($artistQueryBuilder);
    }

    public function updateCategories(array $categories, int $artistId): bool
    {
        /** @var User $user */
        $user = $this->findById($artistId);
        $user->userCategories()->delete();
        $user->userCategoriesList()->sync($categories);
        return true;
    }

    /**
     * @param $artistId
     * @return Model
     */
    public function profile($artistId): Model
    {
        return $this->findById($artistId, relations: ['userCategories.category', 'dates', 'works']);
    }
}
