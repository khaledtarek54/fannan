<?php

namespace App\Dtos;

use App\Http\Requests\Artists\ArtistCategoryRequest;

class CategoryDto
{
    public function __construct(
        public int $category_id,
        public int $subcategory_id,
    )
    {
    }

    /**
     * @return static[]
     */
    static function fromRequest(ArtistCategoryRequest $request): array
    {
        $dtos = [];
        foreach ($request->categories as $category) {
            $dtos[] = new static($category['category_id'], $category['subcategory_id']);
        }
        return $dtos;
    }
}
