<?php

namespace App\Models;

use App\Enums\CouponType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'amount', 'code', 'user_id', 'start_date', 'end_date',
    ];

    protected $casts = [
        'type' => CouponType::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Redemptions of this coupon (one row per user who used it). Backs the admin usage view.
     */
    public function couponUsers(): HasMany
    {
        return $this->hasMany(CouponUser::class, 'coupon_id');
    }
}
