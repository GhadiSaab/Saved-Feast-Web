<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class RestaurantInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'period_start',
        'period_end',
        'status',
        'subtotal_sales',
        'commission_rate',
        'commission_total',
        'orders_count',
        'pdf_path',
        'meta',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'subtotal_sales' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_total' => 'decimal:2',
        'meta' => 'array',
    ];

    /**
     * Get the restaurant that owns the invoice
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Get the invoice items for the invoice
     */
    public function items(): HasMany
    {
        return $this->hasMany(RestaurantInvoiceItem::class, 'invoice_id');
    }

    /**
     * Get the orders through invoice items
     */
    public function orders(): HasManyThrough
    {
        return $this->hasManyThrough(Order::class, RestaurantInvoiceItem::class, 'invoice_id', 'id', 'id', 'order_id');
    }

    /**
     * Scope to filter by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by restaurant
     */
    public function scopeForRestaurant($query, int $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    /**
     * Scope to filter by period
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where('period_start', '>=', $startDate)
            ->where('period_end', '<=', $endDate);
    }
}
