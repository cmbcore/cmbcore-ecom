<?php

declare(strict_types=1);

namespace Modules\Returns\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;

class ReturnRequest extends Model
{
    protected $fillable = [
        'order_id',
        'order_item_id',
        'user_id',
        'status',
        'requested_quantity',
        'refund_amount',
        'reason',
        'resolution_note',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<Order, ReturnRequest>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<OrderItem, ReturnRequest>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    /**
     * @return BelongsTo<User, ReturnRequest>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
