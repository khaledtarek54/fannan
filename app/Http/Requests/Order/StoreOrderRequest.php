<?php

namespace App\Http\Requests\Order;

use App\Enums\UserRole;
use App\Models\UserDate;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
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
            'artist_id' => 'required|exists:users,id',
            'subcategories' => 'required|array',
            'subcategories.*' => 'required|exists:sub_categories,id',
            'start_date' => 'required',
            'end_date' => 'required',
            'address_id' => 'required|exists:addresses,id',
            'description' => 'nullable',
        ];
    }

    protected function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $artistId = $this->input('artist_id');
            $startDate = Carbon::parse($this->input('start_date'));
            $endDate = Carbon::parse($this->input('end_date'));

            $isBusy = UserDate::query()
                ->where('user_id', $artistId)
                ->where(function ($query) use ($startDate, $endDate) {
                    $query
                        ->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($query) use ($startDate, $endDate) {
                            $query->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                })
                ->exists();
            if ($isBusy) {
                $validator->errors()->add('artist_id', trans('app.artist_not_available'));
            }
        });
    }
}
