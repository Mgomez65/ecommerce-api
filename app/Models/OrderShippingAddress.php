<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderShippingAddress extends Model
{
    protected $fillable = [
        'order_id',
        'recipient_name',
        'phone',
        'address',
        'number',
        'city',
        'state',
        'postal_code',
        'notes',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Orders::class, 'order_id');
    }
}
