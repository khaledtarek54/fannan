<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class DeleteUserAccountRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'country_prefix'  => 'required',
            'phone'  => 'required|exists:users,phone',
            // [SECURITY] Require the SMS verification code so an account can't be deleted with a
            // phone number alone (see docs/SECURITY_ISSUES.md M7).
            'verification_code' => 'required',
            'reason'  => 'nullable',
        ];
    }
}
