<?php

namespace App\Models;

use App\Enums\MessageType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Chat extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        "from_user_id", "to_user_id", "is_read", "type", "message", "reply_to",
    ];

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function reply(): BelongsTo
    {
        return $this->belongsTo(Chat::class, 'reply_to');
    }

    public function setMessageAttribute($value): void
    {
        if (is_file($value)) {
            $path = Storage::disk('public')->put('chats', $value);
            $this->attributes['message'] = $path;
        } else
            $this->attributes['message'] = $value;
    }

    public function getMessageAttribute($value)
    {
        if ($this->attributes['type'] == MessageType::FILE->value)
            return Storage::url($this->attributes['message']);
        else
            return $this->attributes['message'];
    }
}
