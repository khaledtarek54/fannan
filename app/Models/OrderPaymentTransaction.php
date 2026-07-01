<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPaymentTransaction extends Model
{
    use HasFactory;

    protected $table = "order_payment_transactions";

    protected $fillable = [
        'order_id', 'amount', 'checkout_id', 'buildNumber', 'ndc', 'is_complete'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
