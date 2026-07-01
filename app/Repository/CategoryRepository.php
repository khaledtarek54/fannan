<?php

namespace App\Repository;

use App\Http\Resources\Category\CategoryCollection;
use App\Models\Category;

class CategoryRepository
{
    public function getCategories()
    {
        return Category::all();
    }

    public function getAll()
    {
        $query = Category::query();
        return new CategoryCollection($query->paginate(10));
    }
}
