<?php

namespace App\Http\Requests\Order;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class CounterOfferRequest extends FormRequest
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
            'order_id' => 'required|exists:orders,id',
            'cost' => 'required|min:0',
        ];
    }
}
