<?php

namespace App\Rules;

use App\Models\User;
use Illuminate\Contracts\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;

class UniquePhoneNumber implements Rule
{
    public function __construct(protected $userId = null)
    {
    }

    public function passes($attribute, $value)
    {

        if (str_starts_with( $value, '+'))
            $phoneNumber = (new PhoneNumber($value))->formatNational();
        else
            $phoneNumber = $value;
        $phone = str_replace(' ', '', $phoneNumber);
        return !User::query()
            ->where('phone', $phone)
            ->when($this->userId, function ($query) {
                $query->where('id', '!=', $this->userId);
            })
            ->first();
    }

    public function message()
    {
        return trans('app.phone_unique');
    }
}
