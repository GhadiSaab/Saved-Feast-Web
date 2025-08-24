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
        'current_price', // Renamed from 'price'
        'original_price', // Added original_price
        'quantity', // Add quantity here
        'category_id',
        'restaurant_id', // Add restaurant_id to fillable
        'image', // Assuming 'image' is the correct DB column for the URL/path
        'available_from', // Add available_from
        'available_until', // Add available_until
     ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'current_price' => 'float', // Cast current_price to float/double
        'original_price' => 'float', // Cast original_price to float/double
        // Add other casts if needed, e.g., for dates or boolean status
        'available_from' => 'datetime', // Cast available_from to datetime
        'available_until' => 'datetime', // Cast available_until to datetime
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

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
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
                : null, // Or return a default image URL
        );
    }
}
