<?php

namespace App\Policies;

use App\Models\Meal;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MealPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Anyone can view meals (public listing)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Meal $meal): bool
    {
        // Anyone can view individual meals
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only providers can create meals
        return $user->roles()->where('name', 'provider')->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Meal $meal): bool
    {
        // Only providers can update meals, and only their own meals
        if (!$user->roles()->where('name', 'provider')->exists()) {
            return false;
        }

        // Check if the user owns the restaurant that owns this meal
        return $user->restaurants()->where('id', $meal->restaurant_id)->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Meal $meal): bool
    {
        // Only providers can delete meals, and only their own meals
        if (!$user->roles()->where('name', 'provider')->exists()) {
            return false;
        }

        // Check if the user owns the restaurant that owns this meal
        return $user->restaurants()->where('id', $meal->restaurant_id)->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Meal $meal): bool
    {
        // Only providers can restore meals, and only their own meals
        if (!$user->roles()->where('name', 'provider')->exists()) {
            return false;
        }

        return $user->restaurants()->where('id', $meal->restaurant_id)->exists();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Meal $meal): bool
    {
        // Only admins can permanently delete meals
        return $user->roles()->where('name', 'admin')->exists();
    }
}
