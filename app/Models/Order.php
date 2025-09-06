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
    const STATUS_PENDING = 'pending';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

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
}
