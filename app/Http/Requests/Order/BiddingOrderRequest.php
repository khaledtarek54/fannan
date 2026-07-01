<?php

namespace App\Http\Requests\Order;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class BiddingOrderRequest extends FormRequest
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
            'name' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'address_id' => 'required|exists:addresses,id',
            'description' => 'nullable',
            'talents' => 'required|array',
            'talents.*.subcategory_id' => 'required|exists:sub_categories,id',
            'talents.*.has_budget' => 'required|boolean',
            'talents.*.budget' => 'required_if:talents.*.has_budget,1',
        ];
    }
}
