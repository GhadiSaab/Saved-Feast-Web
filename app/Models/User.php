<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['roles']; // Add this line to always load roles

    public function restaurants()
    {
        return $this->hasMany(Restaurant::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Check if the user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Check if the user has any of the specified roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Check if the user has all of the specified roles
     */
    public function hasAllRoles(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->count() === count($roles);
    }

    /**
     * Check if the user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if the user is a provider
     */
    public function isProvider(): bool
    {
        return $this->hasRole('provider');
    }

    /**
     * Check if the user is a consumer
     */
    public function isConsumer(): bool
    {
        return $this->hasRole('consumer');
    }

    /**
     * Check if the user owns a specific meal
     */
    public function ownsMeal(Meal $meal): bool
    {
        if (!$this->isProvider()) {
            return false;
        }
        return $this->restaurants()->where('id', $meal->restaurant_id)->exists();
    }

    /**
     * Check if the user owns a specific restaurant
     */
    public function ownsRestaurant(Restaurant $restaurant): bool
    {
        if (!$this->isProvider()) {
            return false;
        }
        return $restaurant->user_id === $this->id;
    }

    /**
     * Check if the user owns a specific order
     */
    public function ownsOrder(Order $order): bool
    {
        return $order->user_id === $this->id;
    }
}
