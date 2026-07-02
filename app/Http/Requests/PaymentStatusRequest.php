<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PaymentStatusRequest extends FormRequest
{
    /**
     * Authorize request
     */
    public function authorize(): bool
    {
        return true; // set auth logic if needed
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:user_transactions,user_id',
        ];
    }

    /**
     * Custom validation messages (optional)
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'Customer reference is required',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $validator->errors()
        ], 422));
    }
}
