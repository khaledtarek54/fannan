<?php

namespace App\Http\Resources\Category;

use App\Http\Resources\SubCategory\SubCategoryCollection;
use App\Http\Resources\SubCategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CategoryResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "photo" => Storage::url($this->photo),
            "sub_categories" => SubCategoryResource::collection($this->subCategory),
        ];
    }
}
