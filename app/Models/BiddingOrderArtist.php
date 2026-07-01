<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\ModelStatus\HasStatuses;

class BiddingOrderArtist extends Model
{
    use HasFactory, SoftDeletes, HasStatuses;

    protected $fillable = ['order_id', 'artist_id', 'subcategory_id', 'cost', 'is_accepted'];


    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class, 'subcategory_id');
    }

    public function rating(): MorphOne
    {
        return $this->morphOne(Rating::class, 'model');
    }

    public function scopeStatus(Builder $query, $value)
    {
        return $query->currentStatus($value);
    }
}
