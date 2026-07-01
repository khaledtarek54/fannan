<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Spatie\ModelStatus\HasStatuses;

class Ad extends Model
{
    use HasFactory, HasStatuses;

    protected $fillable = [
        'name', 'link', 'image',
    ];


    public function adable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->where('role', 'artist');
    }

    public function getStatusAttribute()
    {
        return $this->status;
    }

    public function getImageUrlAttribute(): string
    {
        return Storage::url($this->attributes['image']);
    }

    public function getModelTypeStringAttribute(): string
    {
        return $this->attributes['adable_type'] ? strtoupper(class_basename($this->attributes['adable_type'])) : "NORMAL";
    }


    public function scopeStatus(Builder $query, $value)
    {
        return $query->currentStatus($value);
    }
}
