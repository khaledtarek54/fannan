<?php

namespace App\Http\Requests\Addresses;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->role == UserRole::CLIENT->value;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'city_id' => 'required|exists:cities,id',
            'name' => 'required|max:255',
            'description' => 'nullable',
            'latitude' => 'required',
            'longitude' => 'required',
        ];
    }
}
