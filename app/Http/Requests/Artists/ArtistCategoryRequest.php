<?php

namespace App\Http\Requests\Artists;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ArtistCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->role == UserRole::ARTIST->value;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'categories' => 'required|array',
            'categories.*.category_id' => 'required|exists:categories,id',
            'categories.*.subcategory_id' => 'required|exists:sub_categories,id',
            'categories.*.range_id' => 'required|exists:price_ranges,id',
        ];
    }
}
