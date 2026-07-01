<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class CheckPhoneNumberRequest extends FormRequest
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
            "phone" => "required|exists:users,phone"
        ];
    }

    public function messages()
    {
        return [
            'phone.required' => trans('auth.phone_required'),
            'phone.exists' => trans('auth.phone_exists'),
        ];
    }
}
