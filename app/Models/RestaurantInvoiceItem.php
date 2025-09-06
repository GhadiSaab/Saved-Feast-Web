<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'order_id',
        'order_total',
        'commission_rate',
        'commission_amount',
    ];

    protected $casts = [
        'order_total' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
    ];

    /**
     * Get the invoice that owns the item
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(RestaurantInvoice::class, 'invoice_id');
    }

    /**
     * Get the order that this item references
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
