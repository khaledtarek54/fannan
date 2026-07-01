<?php

namespace App\Http\Requests\Users;

use App\Rules\Iban;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ClientCompleteProfileRequest extends FormRequest
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
        $user = Auth::user();
        $isArtist = $user->role == 'artist';

        return [
            "phone" => "required|unique:users,phone," . Auth::user()->id,
            "phone_prefix" => "nullable",
            "name" => "required",
            "email" => "required|unique:users,email," . Auth::user()->id,
            "dob" => "required",
            "gender" => "required|in:male,female",
            "city_id" => "required|exists:cities,id",
            "vat_number" => "nullable|digits_between:1,16",
            "cr_number" => "nullable|digits_between:1,16",
            "iban" => $isArtist ? ["required",  new Iban()] : ["nullable"],
            "start_date" => $isArtist ? "required" : "nullable",
            "end_date" => $isArtist ? "required" : "nullable",
        ];
    }
}
