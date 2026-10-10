<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    public const STATUSES = ['created', 'paid', 'failed', 'refunded'];

    protected $fillable = [
        'user_id', 'invitation_id', 'amount_paise', 'currency', 'status',
        'razorpay_order_id', 'razorpay_payment_id', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_paise' => 'integer',
            'paid_at'      => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** withTrashed: an order still shows its invitation after the customer deletes it. */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class)->withTrashed();
    }
}
