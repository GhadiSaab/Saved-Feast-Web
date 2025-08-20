<?php

namespace App\Http\Controllers\API\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Restaurant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Meal;

class ProfileController extends Controller
{
    /**
     * Display the provider's profile information and statistics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // Eager load meals and their associated order items
        $restaurant = Restaurant::with(['meals.orderItems'])
                                ->where('user_id', $user->id)
                                ->firstOrFail();

        $mealsSold = 0;
        $foodSavedQuantity = 0;
        $totalRevenue = 0;

        // Calculate stats by iterating through the restaurant's meals and their order items
        foreach ($restaurant->meals as $meal) {
            foreach ($meal->orderItems as $item) {
                // Consider only completed orders for accurate stats
                if ($item->order->status === 'completed') {
                    $mealsSold += $item->quantity; // Assuming quantity represents individual meals sold
                    $foodSavedQuantity += $item->quantity; // Assuming quantity also represents saved food items
                    $totalRevenue += $item->price * $item->quantity;
                }
            }
        }

        // Format revenue
        $totalRevenue = number_format($totalRevenue, 2, '.', '');

        // Construct the response data
        $profileData = $restaurant->toArray(); // Get basic restaurant data
        $profileData['stats'] = [
            'meals_sold' => $mealsSold,
            'food_saved_quantity' => $foodSavedQuantity,
            'total_revenue' => $totalRevenue,
        ];
         // Add full URL for profile picture if it exists
        if ($restaurant->profile_picture_path) {
            $profileData['profile_picture_url'] = Storage::url($restaurant->profile_picture_path);
        } else {
            $profileData['profile_picture_url'] = null; // Or a default image URL
        }


        return response()->json($profileData);
    }

    /**
     * Update the provider's profile picture.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePicture(Request $request)
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Max 2MB
        ]);

        $user = $request->user();
        $restaurant = Restaurant::where('user_id', $user->id)->firstOrFail();

        // Delete old picture if exists
        if ($restaurant->profile_picture_path) {
            Storage::disk('public')->delete($restaurant->profile_picture_path);
        }

        // Store the new picture
        $path = $request->file('profile_picture')->store('provider_profiles', 'public');

        // Update the restaurant record
        $restaurant->profile_picture_path = $path;
        $restaurant->save();

        return response()->json([
            'message' => 'Profile picture updated successfully.',
            'profile_picture_url' => Storage::url($path) // Return the new URL
        ]);
    }
}
