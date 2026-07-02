<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
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
            'password' => "required|min:6",
            // [SECURITY] Require the SMS verification code so a password can't be reset with a
            // phone number alone (account takeover). See docs/CODE_REVIEW_FINDINGS.md B3.
            'verification_code' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'password.required' => trans('auth.password_required'),
            'password.min' => trans('auth.password_min'),
        ];
    }
}
