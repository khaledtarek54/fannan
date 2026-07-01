<?php

namespace App\Rules;

use App\Models\Coupon;
use App\Models\CouponUser;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCoupon implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value) {
            $coupon = Coupon::query()->where('code', $value)->first();

            if (!$coupon) {
                $fail('The coupon code is invalid.');
                return;
            }

            $now = Carbon::now();

            if (!$now->between(Carbon::parse($coupon->start_date), Carbon::parse($coupon->end_date))) {
                $fail(trans('app.coupon_date_not_valid'));
            }
        }
    }
}
