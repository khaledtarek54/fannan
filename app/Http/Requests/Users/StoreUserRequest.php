<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => "required|min:3|max:100",
            'phone_prefix' => "required",
            'phone' => "required|unique:users,phone",
            'role' => "required|in:client,artist",
            'password' => "required",
        ];
    }

    public function messages()
    {
        return [
            "name.required" => trans('auth.name_required'),
            "name.min" => trans('auth.name_min'),
            "name.max" => trans('auth.name_max'),
            "phone_prefix.required" => trans('auth.phone_prefix_required'),
            "phone.required" => trans('auth.phone_required'),
            "phone.unique" => trans('auth.phone_unique'),
            'role.in' => trans('auth.role_in'),
            'categories.required' => trans('auth.categories_required'),
        ];
    }
}
