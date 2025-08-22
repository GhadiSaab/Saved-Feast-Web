<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Users can view their own orders, providers can view orders for their restaurants
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Order $order): bool
    {
        // Users can view their own orders
        if ($order->user_id === $user->id) {
            return true;
        }

        // Providers can view orders for their restaurants through order items and meals
        if ($user->roles()->where('name', 'provider')->exists()) {
            $restaurantIds = $user->restaurants()->pluck('id');
            return $order->orderItems()->whereHas('meal', function($query) use ($restaurantIds) {
                $query->whereIn('restaurant_id', $restaurantIds);
            })->exists();
        }

        // Admins can view all orders
        if ($user->roles()->where('name', 'admin')->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create orders
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Order $order): bool
    {
        // Users can update their own orders (e.g., cancel)
        if ($order->user_id === $user->id) {
            return true;
        }

        // Providers can update orders for their restaurants through order items and meals
        if ($user->roles()->where('name', 'provider')->exists()) {
            $restaurantIds = $user->restaurants()->pluck('id');
            return $order->orderItems()->whereHas('meal', function($query) use ($restaurantIds) {
                $query->whereIn('restaurant_id', $restaurantIds);
            })->exists();
        }

        // Admins can update any order
        if ($user->roles()->where('name', 'admin')->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Order $order): bool
    {
        // Only admins can delete orders
        return $user->roles()->where('name', 'admin')->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Order $order): bool
    {
        // Only admins can restore orders
        return $user->roles()->where('name', 'admin')->exists();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Order $order): bool
    {
        // Only admins can permanently delete orders
        return $user->roles()->where('name', 'admin')->exists();
    }
} 