<?php

namespace App\Services\Concerns;

use App\Models\UserCategory;
use App\Services\Contracts\UserCategoryRepositoryInterface;

class UserCategoryRepository extends BaseRepository implements UserCategoryRepositoryInterface
{
    public function __construct(UserCategory $userCategory)
    {
        $this->setModel($userCategory);
    }

    public function getUserCategories(int $userId, array $subcategories)
    {
        return $this->model->where('user_id', $userId)->whereIn('subcategory_id', $subcategories)->with('priceRange')->get();
    }

}
