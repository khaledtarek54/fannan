<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\ModelStatus\HasStatuses;

class OrderOffer extends Model
{
    use HasFactory, SoftDeletes, HasStatuses;

    protected $fillable = [
        'artist_id', 'order_id', 'cost', 'counter_to', 'subcategory_id',
    ];


    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class, 'subcategory_id');
    }

    public function rate(): BelongsTo
    {
        return $this->belongsTo(Rating::class, 'offer_id');
    }

    public function scopeStatus(Builder $query, $value)
    {
        return $query->currentStatus($value);
    }
}
