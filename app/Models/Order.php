<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    const PAYMENT_METHOD_CASH = 'cash';
    const PAYMENT_METHOD_CARD = 'card';

    const PAYMENT_STATUS_PENDING = 'pending';
    const PAYMENT_STATUS_PAID = 'paid';
    const PAYMENT_STATUS_FAILED = 'failed';

    const ORDER_STATUS_PENDING = 'pending';
    const ORDER_STATUS_PROCESSING = 'processing';
    const ORDER_STATUS_COMPLETED = 'completed';
    const ORDER_STATUS_CANCELLED = 'cancelled';
    const ORDER_STATUS_FAILED = 'failed';
    const ORDER_STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    const ORDER_STATUS_ON_THE_WAY = 'on_the_way';
    const ORDER_STATUS_DELIVERED = 'delivered';
    const ORDER_STATUS_RETURNED = 'returned';

    protected $fillable = [
        'user_id',
        'total_amount',
        'subtotal',
        'discount_amount',
        'coupon_id',
        'coupon_code',
        'shipping_address',
        'phone',
        'payment_method',
        'payment_status',
        'payment_transaction_id',
        'payment_id',
        'order_status',
        'notes',
        'policy_agreement',
        'amount_paid'
    ];

    protected $casts = [
        'total_amount' => 'float',
        'subtotal' => 'float',
        'discount_amount' => 'float',
        'amount_paid' => 'float'
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            $order->uuid = (string) Str::uuid();
            $order->order_number = 'ORD-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);

            // Ensure subtotal is set correctly
            if (!isset($order->subtotal) || $order->subtotal == 0) {
                $order->subtotal = $order->total_amount;
            }

            // Ensure discount_amount is set
            if (!isset($order->discount_amount)) {
                $order->discount_amount = 0;
    }
        });

        static::updating(function ($order) {
            // When updating, recalculate values if needed
            if ($order->isDirty('total_amount') && !$order->isDirty('subtotal') && !$order->isDirty('discount_amount')) {
                // If only total_amount changed, update subtotal
                $order->subtotal = $order->total_amount + ($order->discount_amount ?? 0);
            } elseif ($order->isDirty('subtotal') && $order->isDirty('discount_amount') && !$order->isDirty('total_amount')) {
                // If subtotal and discount changed but total didn't, update total
                $order->total_amount = $order->subtotal - $order->discount_amount;
            }
        });
    }

    /**
     * Set the subtotal attribute.
     *
     * @param  float  $value
     * @return void
     */
    public function setSubtotalAttribute($value)
    {
        $this->attributes['subtotal'] = $value ?? $this->total_amount ?? 0;
    }

    /**
     * Set the discount_amount attribute.
     *
     * @param  float  $value
     * @return void
     */
    public function setDiscountAmountAttribute($value)
    {
        $this->attributes['discount_amount'] = $value ?? 0;
    }

    /**
     * Calculate the total amount based on subtotal and discount.
     *
     * @return float
     */
    public function calculateTotal()
    {
        return $this->subtotal - $this->discount_amount;
    }

    /**
     * Get the user that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the coupon associated with the order.
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Get the formatted discount amount.
     */
    public function getFormattedDiscountAttribute()
    {
        return number_format($this->discount_amount, 2) . ' ر.س';
    }

    /**
     * Get the formatted subtotal amount.
     */
    public function getFormattedSubtotalAttribute()
    {
        return number_format($this->subtotal, 2) . ' ر.س';
    }

    // Helper methods for status checks
    public function isPending(): bool
    {
        return $this->order_status === self::ORDER_STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->order_status === self::ORDER_STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->order_status === self::ORDER_STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->order_status === self::ORDER_STATUS_CANCELLED;
    }

    public function isPaymentPending(): bool
    {
        return $this->payment_status === self::PAYMENT_STATUS_PENDING;
    }

    public function isPaymentPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_STATUS_PAID;
    }

    public function isPaymentFailed(): bool
    {
        return $this->payment_status === self::PAYMENT_STATUS_FAILED;
    }

    // Helper methods for new status checks
    public function isOutForDelivery(): bool
    {
        return $this->order_status === self::ORDER_STATUS_OUT_FOR_DELIVERY;
    }

    public function isOnTheWay(): bool
    {
        return $this->order_status === self::ORDER_STATUS_ON_THE_WAY;
    }

    public function isDelivered(): bool
    {
        return $this->order_status === self::ORDER_STATUS_DELIVERED;
    }

    public function isReturned(): bool
    {
        return $this->order_status === self::ORDER_STATUS_RETURNED;
    }

    // Add this method to use uuid in routes
    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
