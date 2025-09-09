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

            return $order->orderItems()->whereHas('meal', function ($query) use ($restaurantIds) {
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

            return $order->orderItems()->whereHas('meal', function ($query) use ($restaurantIds) {
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

    /**
     * Determine whether the user can cancel the order.
     */
    public function cancel(User $user, Order $order): bool
    {
        // Users can cancel their own orders if allowed by business rules
        if ($order->user_id === $user->id) {
            return $order->canBeCancelledByCustomer();
        }

        // Providers can cancel orders for their restaurants if allowed by business rules
        if ($user->roles()->where('name', 'provider')->exists()) {
            $restaurantIds = $user->restaurants()->pluck('id');
            $hasAccess = $order->orderItems()->whereHas('meal', function ($query) use ($restaurantIds) {
                $query->whereIn('restaurant_id', $restaurantIds);
            })->exists();

            return $hasAccess && $order->canBeCancelledByRestaurant();
        }

        // Admins can cancel any order
        if ($user->roles()->where('name', 'admin')->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can accept the order.
     */
    public function accept(User $user, Order $order): bool
    {
        // Only providers can accept orders
        if (! $user->roles()->where('name', 'provider')->exists()) {
            return false;
        }

        // Check if order belongs to provider's restaurant
        $restaurantIds = $user->restaurants()->pluck('id');
        $hasAccess = $order->orderItems()->whereHas('meal', function ($query) use ($restaurantIds) {
            $query->whereIn('restaurant_id', $restaurantIds);
        })->exists();

        return $hasAccess && $order->status === Order::STATUS_PENDING;
    }

    /**
     * Determine whether the user can mark the order as ready.
     */
    public function markReady(User $user, Order $order): bool
    {
        // Only providers can mark orders as ready
        if (! $user->roles()->where('name', 'provider')->exists()) {
            return false;
        }

        // Check if order belongs to provider's restaurant
        $restaurantIds = $user->restaurants()->pluck('id');
        $hasAccess = $order->orderItems()->whereHas('meal', function ($query) use ($restaurantIds) {
            $query->whereIn('restaurant_id', $restaurantIds);
        })->exists();

        return $hasAccess && $order->status === Order::STATUS_ACCEPTED;
    }

    /**
     * Determine whether the user can complete the order.
     */
    public function complete(User $user, Order $order): bool
    {
        // Only providers can complete orders
        if (! $user->roles()->where('name', 'provider')->exists()) {
            return false;
        }

        // Check if order belongs to provider's restaurant
        $restaurantIds = $user->restaurants()->pluck('id');
        $hasAccess = $order->orderItems()->whereHas('meal', function ($query) use ($restaurantIds) {
            $query->whereIn('restaurant_id', $restaurantIds);
        })->exists();

        return $hasAccess && $order->status === Order::STATUS_READY_FOR_PICKUP;
    }

    /**
     * Determine whether the user can view pickup code.
     */
    public function viewPickupCode(User $user, Order $order): bool
    {
        // Users can view pickup code for their own orders
        if ($order->user_id === $user->id) {
            return in_array($order->status, [Order::STATUS_ACCEPTED, Order::STATUS_READY_FOR_PICKUP]);
        }

        // Providers can view pickup code for orders in their restaurants
        if ($user->roles()->where('name', 'provider')->exists()) {
            $restaurantIds = $user->restaurants()->pluck('id');
            $hasAccess = $order->orderItems()->whereHas('meal', function ($query) use ($restaurantIds) {
                $query->whereIn('restaurant_id', $restaurantIds);
            })->exists();

            return $hasAccess && in_array($order->status, [Order::STATUS_ACCEPTED, Order::STATUS_READY_FOR_PICKUP]);
        }

        // Admins can view pickup code for any order
        if ($user->roles()->where('name', 'admin')->exists()) {
            return true;
        }

        return false;
    }
}
