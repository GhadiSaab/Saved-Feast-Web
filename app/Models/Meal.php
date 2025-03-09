<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'image',
        'restaurant_id',
        'category_id',
        'is_available',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }



    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
