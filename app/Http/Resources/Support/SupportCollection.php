<?php

namespace App\Http\Resources\Support;

use App\Http\Resources\BasePaging;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SupportCollection extends ResourceCollection
{
    use BasePaging;

    public $collects = 'App\Http\Resources\Support\AdResource';


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
