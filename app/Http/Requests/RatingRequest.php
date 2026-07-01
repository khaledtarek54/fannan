<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class RatingRequest extends FormRequest
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
            'order_id' => 'nullable|exists:orders,id',
            'offer_id' => 'nullable|exists:bidding_order_artists,id',
            'stars' => 'required|integer|between:1,5',
            'notes' => 'nullable|min:1|max:255',
        ];
    }
}
