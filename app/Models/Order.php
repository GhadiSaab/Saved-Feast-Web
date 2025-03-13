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
}
