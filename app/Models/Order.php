<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * Order status constants
     */
    const STATUS_PENDING = 'PENDING';

    const STATUS_ACCEPTED = 'ACCEPTED';

    const STATUS_READY_FOR_PICKUP = 'READY_FOR_PICKUP';

    const STATUS_COMPLETED = 'COMPLETED';

    const STATUS_CANCELLED_BY_CUSTOMER = 'CANCELLED_BY_CUSTOMER';

    const STATUS_CANCELLED_BY_RESTAURANT = 'CANCELLED_BY_RESTAURANT';

    const STATUS_EXPIRED = 'EXPIRED';

    /**
     * Cancelled by constants
     */
    const CANCELLED_BY_CUSTOMER = 'customer';

    const CANCELLED_BY_RESTAURANT = 'restaurant';

    const CANCELLED_BY_SYSTEM = 'system';

    protected $fillable = [
        'user_id',
        'total_amount',
        'status',
        'pickup_time',
        'notes',
        'payment_method',
        'commission_rate',
        'commission_amount',
        'completed_at',
        'invoiced_at',
        'pickup_window_start',
        'pickup_window_end',
        'accepted_at',
        'ready_at',
        'cancelled_at',
        'expired_at',
        'cancel_reason',
        'cancelled_by',
        'pickup_code_encrypted',
        'pickup_code_attempts',
        'pickup_code_last_sent_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'total_amount' => 'decimal:2',
        'pickup_time' => 'datetime',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'completed_at' => 'datetime',
        'invoiced_at' => 'datetime',
        'pickup_window_start' => 'datetime',
        'pickup_window_end' => 'datetime',
        'accepted_at' => 'datetime',
        'ready_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expired_at' => 'datetime',
        'pickup_code_last_sent_at' => 'datetime',
    ];

    /**
     * Get the user that owns the order
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order items for the order
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the order events for the order
     */
    public function orderEvents()
    {
        return $this->hasMany(\App\Models\OrderEvent::class);
    }

    /**
     * Get the payment record associated with the order
     */
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Get the invoice item associated with the order
     */
    public function invoiceItem()
    {
        return $this->hasOne(RestaurantInvoiceItem::class);
    }

    /**
     * Get the restaurant through order items and meals
     */
    public function restaurant()
    {
        return $this->hasOneThrough(
            Restaurant::class,
            Meal::class,
            'id',
            'id',
            'id',
            'restaurant_id'
        )->join('order_items', 'order_items.meal_id', '=', 'meals.id')
            ->where('order_items.order_id', $this->id);
    }

    /**
     * Get the order events for the order
     */
    public function events()
    {
        return $this->hasMany(OrderEvent::class);
    }

    /**
     * Scope to filter by payment method
     */
    public function scopePaymentMethod($query, string $paymentMethod)
    {
        return $query->where('payment_method', $paymentMethod);
    }

    /**
     * Scope to filter by completion status
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to filter by invoiced status
     */
    public function scopeInvoiced($query)
    {
        return $query->whereNotNull('invoiced_at');
    }

    /**
     * Scope to filter by not invoiced status
     */
    public function scopeNotInvoiced($query)
    {
        return $query->whereNull('invoiced_at');
    }

    /**
     * Scope to filter by status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by active statuses (not completed, cancelled, or expired)
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_ACCEPTED,
            self::STATUS_READY_FOR_PICKUP,
        ]);
    }

    /**
     * Scope to filter by overdue orders
     */
    public function scopeOverdue($query)
    {
        return $query->where('pickup_window_end', '<', now())
            ->whereIn('status', [
                self::STATUS_ACCEPTED,
                self::STATUS_READY_FOR_PICKUP,
            ]);
    }

    /**
     * Check if order can be cancelled by customer
     */
    public function canBeCancelledByCustomer(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_ACCEPTED,
        ]);
    }

    /**
     * Check if order can be cancelled by restaurant
     */
    public function canBeCancelledByRestaurant(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_ACCEPTED,
        ]);
    }

    /**
     * Check if order is in pickup window
     */
    public function isInPickupWindow(): bool
    {
        if (! $this->pickup_window_start || ! $this->pickup_window_end) {
            return false;
        }

        $now = now();

        return $now->between($this->pickup_window_start, $this->pickup_window_end);
    }

    /**
     * Check if order has exceeded pickup window
     */
    public function hasExceededPickupWindow(): bool
    {
        if (! $this->pickup_window_end) {
            return false;
        }

        return now()->isAfter($this->pickup_window_end);
    }

    /**
     * Get masked pickup code for display
     */
    public function getMaskedPickupCode(): ?string
    {
        if (! $this->pickup_code_encrypted) {
            return null;
        }

        try {
            $code = decrypt($this->pickup_code_encrypted);

            return substr($code, 0, 2).'••••'.substr($code, -2);
        } catch (\Exception $e) {
            return null;
        }
    }
}
