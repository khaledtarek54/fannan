<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Support extends Model
{
    use HasFactory;

    protected $fillable = ["user_id", 'reply_user_id', "name", "email", "phone", "description", "model_type", "model_id", 'is_complete'];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }

    public function replyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reply_user_id');
    }
}
