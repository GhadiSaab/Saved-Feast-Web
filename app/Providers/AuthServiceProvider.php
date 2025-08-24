<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\User;
use App\Models\Meal;
use App\Models\Restaurant;
use App\Models\Order;
use App\Policies\MealPolicy;
use App\Policies\RestaurantPolicy;
use App\Policies\OrderPolicy;
use App\Policies\UserPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Meal::class => MealPolicy::class,
        Restaurant::class => RestaurantPolicy::class,
        Order::class => OrderPolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Define custom gates for role-based authorization
        Gate::define('manage-meals', function (User $user) {
            return $user->roles()->where('name', 'provider')->exists();
        });

        Gate::define('manage-restaurants', function (User $user) {
            return $user->roles()->where('name', 'provider')->exists();
        });

        Gate::define('manage-orders', function (User $user) {
            return $user->roles()->where('name', 'provider')->exists();
        });

        Gate::define('admin-access', function (User $user) {
            return $user->roles()->where('name', 'admin')->exists();
        });

        Gate::define('provider-access', function (User $user) {
            return $user->roles()->where('name', 'provider')->exists();
        });

        Gate::define('customer-access', function (User $user) {
            return $user->roles()->where('name', 'customer')->exists();
        });

        // Gate for checking if user owns a specific resource
        Gate::define('own-meal', function (User $user, Meal $meal) {
            if (! $user->roles()->where('name', 'provider')->exists()) {
                return false;
            }
            return $user->restaurants()->where('id', $meal->restaurant_id)->exists();
        });

        Gate::define('own-restaurant', function (User $user, Restaurant $restaurant) {
            if (! $user->roles()->where('name', 'provider')->exists()) {
                return false;
            }
            return $restaurant->user_id === $user->id;
        });

        Gate::define('own-order', function (User $user, Order $order) {
            return $order->user_id === $user->id;
        });
    }
}
