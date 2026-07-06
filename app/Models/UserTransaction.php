<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTransaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'user_id',
        'customer_reference',
        'status',
        'easykash_ref',
        'payment_method',
        'product_type',
        'amount_paid',
        'callback_payload',
        'is_paid',
        'amount',
        'name',
        'email',
        'mobile',
    ];

    // [SECURITY][R2-L5] Keep the raw gateway payload + payer PII out of any accidental
    // serialization (e.g. a controller returning the model directly).
    protected $hidden = [
        'callback_payload',
        'easykash_ref',
        'email',
        'mobile',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
