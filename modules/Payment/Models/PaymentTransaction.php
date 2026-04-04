<?php

declare(strict_types=1);

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Order\Models\Order;

class PaymentTransaction extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_AWAITING_CONFIRMATION = 'awaiting_confirmation';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'order_id',
        'gateway',
        'amount',
        'status',
        'reference',
        'callback_data',
        'meta',
        'confirmed_at',
        'refunded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'callback_data' => 'array',
        'meta' => 'array',
        'confirmed_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Order, PaymentTransaction>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
