<?php

declare(strict_types=1);

namespace Modules\Order\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Payment\Models\PaymentTransaction;

class Order extends Model
{
    public const SOURCE_GUEST = 'guest';
    public const SOURCE_ACCOUNT = 'account';

    public const PAYMENT_METHOD_COD = 'cod';

    public const PAYMENT_STATUS_UNPAID = 'unpaid';
    public const PAYMENT_STATUS_COD_PENDING = 'cod_pending';
    public const PAYMENT_STATUS_PAID = 'paid';

    public const ORDER_STATUS_PENDING = 'pending';
    public const ORDER_STATUS_CONFIRMED = 'confirmed';
    public const ORDER_STATUS_CANCELLED = 'cancelled';

    public const FULFILLMENT_STATUS_PENDING = 'pending';
    public const FULFILLMENT_STATUS_PROCESSING = 'processing';
    public const FULFILLMENT_STATUS_SHIPPING = 'shipping';
    public const FULFILLMENT_STATUS_DELIVERED = 'delivered';

    protected $fillable = [
        'order_number',
        'user_id',
        'guest_email',
        'customer_name',
        'customer_phone',
        'shipping_recipient_name',
        'shipping_phone',
        'shipping_method_code',
        'shipping_method_name',
        'shipping_full_address',
        'shipping_meta',
        'note',
        'payment_method',
        'payment_status',
        'payment_meta',
        'fulfillment_status',
        'order_status',
        'subtotal',
        'discount_total',
        'coupon_code',
        'coupon_snapshot',
        'shipping_total',
        'tax_total',
        'grand_total',
        'source',
    ];

    protected $casts = [
        'shipping_meta' => 'array',
        'payment_meta' => 'array',
        'coupon_snapshot' => 'array',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<User, Order>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    /**
     * @return HasMany<OrderStatusHistory>
     */
    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderByDesc('id');
    }

    /**
     * @return HasMany<PaymentTransaction>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class)->orderByDesc('id');
    }
}
