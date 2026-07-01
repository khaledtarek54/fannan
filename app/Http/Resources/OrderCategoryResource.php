<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $category = $this->subcategory?->category;
        $subcategory = $this->subcategory;
        return [
            'id' => $this->id,
            'category_id' => $category?->id,
            'category_name' => $category?->name,
            'subcategory_id' => $subcategory?->id,
            'subcategory_name' => $subcategory?->name,
            'from_range' => $this?->from_range,
            'to_range' => $this?->to_range,
            'order_id' => $this->order_id,
            'has_budget' => $this->has_budget,
            'budget' => $this->budget,
        ];
    }
}
