<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class SocialLoginRequest extends FormRequest
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
            // [SECURITY][R2-C1] The Firebase ID token is the ONLY trusted input. The account is
            // resolved from the token's verified email server-side — a client-supplied `email` is
            // never trusted (previously it was, which was a full authentication bypass).
            'id_token' => 'required|string',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_token.required' => trans('auth.social_token_required'),
        ];
    }
}
