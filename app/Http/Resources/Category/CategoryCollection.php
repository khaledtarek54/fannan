<?php

namespace App\Http\Resources\Category;

use App\Http\Resources\BasePaging;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CategoryCollection extends ResourceCollection
{
    use BasePaging;

    public $collects = CategoryResource::class;


    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => $this->collection,
            'links' => $this->paginationLinks(),
            'meta' => $this->meta(),
        ];
    }
}
