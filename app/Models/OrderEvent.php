<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderEvent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'type',
        'meta',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * Event types constants
     */
    const TYPE_STATUS_CHANGED = 'status_changed';

    const TYPE_CODE_GENERATED = 'code_generated';

    const TYPE_CODE_ATTEMPT = 'code_attempt';

    const TYPE_CODE_VERIFIED = 'code_verified';

    const TYPE_SMS_SENT = 'sms_sent';

    const TYPE_EXPIRED = 'expired';

    const TYPE_CANCELLED = 'cancelled';

    /**
     * Get the order that owns the event
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scope to filter by event type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by order
     */
    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }
}
