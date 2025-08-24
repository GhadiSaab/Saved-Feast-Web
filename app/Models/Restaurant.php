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
        'average_rating'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'delivery_radius' => 'decimal:2',
        'average_rating' => 'decimal:2'
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
        return $this->hasManyThrough(Order::class, Meal::class, 'restaurant_id', 'id', 'id', 'id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
