<?php

namespace App\Services\Contracts;

interface UserCategoryRepositoryInterface extends BaseRepositoryInterface
{

    public function getUserCategories(int $userId, array $subcategories);

}
