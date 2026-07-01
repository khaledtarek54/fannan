<?php

namespace App\Http\Requests\Chats;

use App\Enums\MessageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChatRequest extends FormRequest
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
            'to_user_id' => 'required|exists:users,id',
            'type' => ['required', Rule::in(array_column(MessageType::cases(), 'value'))],
            'message' => 'required',
            'reply_to' => 'nullable|exists:chats,id',
        ];
    }
}
