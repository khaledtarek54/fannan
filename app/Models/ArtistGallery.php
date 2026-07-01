<?php

namespace App\Models;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ArtistGallery extends Model
{
    use HasFactory;

    protected $table = "user_gallery_works";

    protected $fillable = ["user_id", "video", "is_pin", 'type'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function setVideoAttribute($value): void
    {
        $extension = $value->extension();
        $path = 'artist/' . auth()->user()?->first()?->name;
            $storedPath = Storage::disk('public')->putFileAs($path, $value, time() . "." . $extension, 'public');
        $this->attributes['video'] = $storedPath;
    }

    public function getVideoAttribute(): string
    {
        return Storage::url($this->attributes['video']);
    }

    public function getVideoUrlAttribute(): string
    {
        return $this->attributes['video'];
    }
}
