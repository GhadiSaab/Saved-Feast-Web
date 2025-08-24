<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\Meal;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RestaurantController extends Controller
{
    /**
     * Get all restaurants with filtering and search
     */
    public function index(Request $request)
    {
        $query = Restaurant::with(['user', 'meals']);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter by cuisine type
        if ($request->has('cuisine')) {
            $query->where('cuisine_type', $request->cuisine);
        }

        // Filter by active status
        $query->where('is_active', true);

        $restaurants = $query->withCount('meals')
            ->withAvg('reviews', 'rating')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'status' => true,
            'message' => 'Restaurants retrieved successfully',
            'data' => $restaurants->items(),
            'pagination' => [
                'current_page' => $restaurants->currentPage(),
                'last_page' => $restaurants->lastPage(),
                'per_page' => $restaurants->perPage(),
                'total' => $restaurants->total(),
                'from' => $restaurants->firstItem(),
                'to' => $restaurants->lastItem(),
                'has_more_pages' => $restaurants->hasMorePages()
            ]
        ]);
    }

    /**
     * Get restaurant details
     */
    public function show(Restaurant $restaurant)
    {
        $restaurant->load(['user', 'meals.category', 'reviews.user']);

        return response()->json([
            'status' => true,
            'message' => 'Restaurant details retrieved successfully',
            'data' => [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'description' => $restaurant->description,
                'address' => $restaurant->address,
                'phone' => $restaurant->phone,
                'email' => $restaurant->email,
                'cuisine_type' => $restaurant->cuisine_type,
                'delivery_radius' => $restaurant->delivery_radius,
                'average_rating' => $restaurant->average_rating,
                'is_active' => $restaurant->is_active,
                'meals' => $restaurant->meals
            ]
        ]);
    }

    /**
     * Get restaurant meals
     */
    public function meals(Restaurant $restaurant)
    {
        $meals = $restaurant->meals()->with('category')->get();

        return response()->json([
            'status' => true,
            'message' => 'Restaurant meals retrieved successfully',
            'data' => $meals->map(function ($meal) {
                return [
                    'id' => $meal->id,
                    'title' => $meal->title,
                    'description' => $meal->description,
                    'current_price' => $meal->current_price,
                    'is_available' => $meal->quantity > 0
                ];
            })
        ]);
    }

    /**
     * Get restaurant ratings
     */
    public function ratings(Restaurant $restaurant)
    {
        $reviews = $restaurant->reviews()->with('user')->get();
        
        $ratingsBreakdown = $reviews->groupBy('rating')
            ->map(function ($group) {
                return count($group);
            });

        return response()->json([
            'status' => true,
            'message' => 'Restaurant ratings retrieved successfully',
            'data' => [
                'average_rating' => $restaurant->average_rating,
                'total_reviews' => $reviews->count(),
                'ratings_breakdown' => $ratingsBreakdown
            ]
        ]);
    }

    /**
     * Create a new restaurant (for providers)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'required|string',
            'phone' => 'nullable|string',
            'email' => 'required|email|unique:restaurants,email',
            'cuisine_type' => 'nullable|string',
            'delivery_radius' => 'nullable|numeric|min:0'
        ]);

        $restaurant = Restaurant::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'description' => $request->description,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
            'cuisine_type' => $request->cuisine_type,
            'delivery_radius' => $request->delivery_radius ?? 5.0,
            'is_active' => false // New restaurants need approval
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Restaurant created successfully',
            'data' => [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'description' => $restaurant->description,
                'address' => $restaurant->address,
                'user_id' => $restaurant->user_id
            ]
        ], 201);
    }

    /**
     * Update restaurant (for providers)
     */
    public function update(Request $request, Restaurant $restaurant)
    {
        // Check if user owns this restaurant
        if ($restaurant->user_id !== Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'cuisine_type' => 'nullable|string',
            'delivery_radius' => 'nullable|numeric|min:0'
        ]);

        $restaurant->update($request->only([
            'name', 'description', 'cuisine_type', 'delivery_radius'
        ]));

        return response()->json([
            'status' => true,
            'message' => 'Restaurant updated successfully'
        ]);
    }

    /**
     * Get provider's restaurants
     */
    public function providerRestaurants()
    {
        $restaurants = Restaurant::where('user_id', Auth::id())
            ->with(['meals', 'reviews'])
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Provider restaurants retrieved successfully',
            'data' => $restaurants
        ]);
    }
}
