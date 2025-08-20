<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'meal_id',
        'quantity',
        'price',
        'original_price', // Added original_price
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'float',
        'original_price' => 'float',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function meal()
    {
        return $this->belongsTo(Meal::class);
    }
}
