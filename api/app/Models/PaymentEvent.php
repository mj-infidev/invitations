<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One Razorpay webhook call, logged as received. The unique event_id stops the
 * same webhook being processed twice.
 */
class PaymentEvent extends Model
{
    protected $fillable = [
        'event_id', 'type', 'payload', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload'      => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
