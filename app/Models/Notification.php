<?php

namespace App\Models;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Application;

class Notification extends Model
{
    use HasFactory;


    protected $fillable = ["type", "title", "body", "user_id", 'to_user_id', "is_read", "model_type", "model_id"];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function getTitleAttribute(): Application|array|string|Translator|\Illuminate\Contracts\Foundation\Application|null
    {
        return trans('app.' . $this->attributes['title']);
    }

    public function getBodyAttribute(): Application|array|string|Translator|\Illuminate\Contracts\Foundation\Application|null
    {
        return trans('app.' . $this->attributes['body'], [
            'user' => $this->user?->name
        ]);
    }

}
