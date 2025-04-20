<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Meal extends Model
{
    use HasFactory;

    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var array
     */
    protected $appends = ['image_url'];

    protected $fillable = [
        'title', // Reverted: Database likely uses 'title'
        'description',
        'price',
        'quantity', // Add quantity here
        'category_id',
        'image', // Assuming 'image' is the correct DB column for the URL/path
        // 'restaurant_id' is assigned manually, not needed here
     ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'float', // Cast price to float/double
        // Add other casts if needed, e.g., for dates or boolean status
        // 'available_from' => 'datetime',
        // 'available_until' => 'datetime',
    ];

     public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the full URL for the meal's image.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $attributes['image']
                ? Storage::url($attributes['image'])
                : null // Or return a default image URL
        );
    }
}
