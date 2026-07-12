<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * [DASH-P2] One row per privileged admin action in the Filament panel. Written only by
 * App\Observers\AdminAuditObserver; read only by the admin AdminActivityLogResource. Never
 * touched by the mobile API.
 */
class AdminActivityLog extends Model
{
    public const UPDATED_AT = null; // immutable log — created_at only

    protected $fillable = [
        'admin_id', 'event', 'auditable_type', 'auditable_id', 'description', 'properties', 'ip_address',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
