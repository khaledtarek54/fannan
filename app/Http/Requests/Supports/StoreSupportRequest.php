<?php

namespace App\Http\Requests\Supports;

use App\Enums\OrderType;
use App\Enums\SupportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportRequest extends FormRequest
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
            'type' => ['required', Rule::in(array_column(SupportType::cases(), 'value'))],
            'order_id' => [
                Rule::requiredIf(function () {
                    return in_array($this->type, [SupportType::DIRECT_ORDER->value, SupportType::BIDDING_ORDER->value]);
                }),
                Rule::when($this->type === OrderType::DIRECT->value, 'exists:orders,id'),
//                Rule::when($this->type ===  OrderType::BIDDING->value, 'exists:bidding_orders,id'),
            ],
            "name" => ["required_if:type,==," . SupportType::GENERAL->value],
            "phone" => ["required_if:type,==," . SupportType::GENERAL->value],
            "email" => ["required_if:type,==," . SupportType::GENERAL->value],
            'description' => 'required',
        ];
    }
}
