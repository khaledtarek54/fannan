<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SocialLoginRequest extends FormRequest
{
    /**
     * [SECURITY][R2-C1] Social login is behind a master switch (config `auth.social_login_enabled`,
     * env SOCIAL_LOGIN_ENABLED). Checked here in authorize() so it short-circuits BEFORE the
     * id_token validation — the current app still posts `{email}` with no token, so gating later
     * would surface a confusing 422 instead of a clean "unavailable" message.
     */
    public function authorize(): bool
    {
        return (bool) config('auth.social_login_enabled');
    }

    /**
     * Return a clean, uniform "temporarily unavailable" response when the switch is off — never
     * the default 403 "unauthorized", and never the old email-trust behaviour.
     */
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'data' => null,
            'message' => trans('auth.social_login_disabled'),
            'errors' => null,
        ], 503));
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
