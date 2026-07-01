<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'start_date', 'end_date', 'start_time', 'end_time', 'is_completed',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
