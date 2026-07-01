<?php

namespace App\Http\Resources\SubCategory;

use App\Http\Resources\BasePaging;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SubCategoryCollection extends ResourceCollection
{
    use BasePaging;

    public $collects = 'App\Http\Resources\SubCategory\SubCategoryResource';


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
