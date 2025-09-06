<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'phone',
        'address',
        'email',
        'website',
        'image',
        'user_id',
        'cuisine_type',
        'delivery_radius',
        'is_active',
        'average_rating',
        'commission_rate',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'delivery_radius' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'commission_rate' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function meals()
    {
        return $this->hasMany(Meal::class);
    }

    public function orders()
    {
        return $this->hasManyThrough(
            Order::class,
            Meal::class,
            'restaurant_id', // Foreign key on meals table
            'id', // Foreign key on orders table
            'id', // Local key on restaurants table
            'id' // Local key on meals table
        );
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function invoices()
    {
        return $this->hasMany(RestaurantInvoice::class);
    }
}
