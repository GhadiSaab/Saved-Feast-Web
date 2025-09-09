<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Meal;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MealController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Validate query parameters
            $validator = Validator::make($request->all(), [
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'category_id' => 'nullable|integer|exists:categories,id',
                'restaurant_id' => 'nullable|integer|exists:restaurants,id',
                'min_price' => 'nullable|numeric|min:0',
                'max_price' => 'nullable|numeric|min:0',
                'available' => 'nullable|string|in:true,false,1,0',
                'search' => 'nullable|string|max:255',
                'sort_by' => 'nullable|string|in:title,current_price,created_at',
                'sort_order' => 'nullable|string|in:asc,desc',
            ]);

            // Add custom validation for max_price >= min_price when both are provided
            $validator->after(function ($validator) use ($request) {
                if ($request->filled('min_price') && $request->filled('max_price')) {
                    if ($request->max_price < $request->min_price) {
                        $validator->errors()->add('max_price', 'The maximum price must be greater than or equal to the minimum price.');
                    }
                }
            });

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Start with base query
            $query = Meal::with(['category', 'restaurant']);

            // Apply filters
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->filled('restaurant_id')) {
                $query->where('restaurant_id', $request->restaurant_id);
            }

            if ($request->filled('min_price')) {
                $query->where('current_price', '>=', $request->min_price);
            }

            if ($request->filled('max_price')) {
                $query->where('current_price', '<=', $request->max_price);
            }

            // Availability filter
            if ($request->has('available')) {
                $now = now();
                $available = $request->string('available');
                $isAvailable = in_array($available, ['true', '1']);

                if ($isAvailable) {
                    // Show only available meals
                    $query->where(function ($q) use ($now) {
                        // Available from: null means always available, or date is in the past/now
                        $q->whereNull('available_from')
                            ->orWhere('available_from', '<=', $now);
                    })->where(function ($q) use ($now) {
                        // Available until: null means always available, or date is in the future/now
                        $q->whereNull('available_until')
                            ->orWhere('available_until', '>=', $now);
                    })->where('quantity', '>', 0)
                        ->where('status', '!=', 'expired'); // Exclude explicitly expired meals
                } else {
                    // Show unavailable meals
                    $query->where(function ($q) use ($now) {
                        $q->where('available_from', '>', $now)
                            ->orWhere('available_until', '<', $now)
                            ->orWhere('quantity', '<=', 0)
                            ->orWhere('status', 'expired');
                    });
                }
            }

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('category', function ($categoryQuery) use ($search) {
                            $categoryQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('restaurant', function ($restaurantQuery) use ($search) {
                            $restaurantQuery->where('name', 'like', "%{$search}%");
                        });
                });
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Apply pagination
            $perPage = $request->get('per_page', 15);
            $meals = $query->paginate($perPage);

            // Transform the response to include additional metadata
            $response = [
                'status' => true,
                'message' => 'Meals retrieved successfully',
                'data' => $meals->items(),
                'pagination' => [
                    'current_page' => $meals->currentPage(),
                    'last_page' => $meals->lastPage(),
                    'per_page' => $meals->perPage(),
                    'total' => $meals->total(),
                    'from' => $meals->firstItem(),
                    'to' => $meals->lastItem(),
                    'has_more_pages' => $meals->hasMorePages(),
                ],
                'filters_applied' => [
                    'category_id' => $request->category_id,
                    'restaurant_id' => $request->restaurant_id,
                    'min_price' => $request->min_price,
                    'max_price' => $request->max_price,
                    'available' => $request->available,
                    'search' => $request->search,
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder,
                ],
            ];

            return response()->json($response, 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve meals',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available filters for meals
     */
    public function filters()
    {
        try {
            $filters = [
                'categories' => Category::select('id', 'name')->get(),
                'price_range' => [
                    'min' => Meal::min('current_price'),
                    'max' => Meal::max('current_price'),
                ],
                'sort_options' => [
                    ['value' => 'title', 'label' => 'Name'],
                    ['value' => 'current_price', 'label' => 'Price'],
                    ['value' => 'created_at', 'label' => 'Date Added'],
                ],
                'sort_orders' => [
                    ['value' => 'asc', 'label' => 'Ascending'],
                    ['value' => 'desc', 'label' => 'Descending'],
                ],
            ];

            return response()->json([
                'status' => true,
                'message' => 'Filters retrieved successfully',
                'data' => $filters,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve filters',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getCategories()
    {
        $categories = Category::all();

        return response()->json([
            'status' => true,
            'message' => 'Categories retrieved successfully',
            'data' => $categories,
        ], 200);
    }

    public function toggleFavorite(Request $request, $id)
    {
        $user = $request->user();
        $meal = Meal::findOrFail($id);

        $favorite = Favorite::where('user_id', $user->id)
            ->where('meal_id', $meal->id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            $isFavorited = false;
            $message = 'Meal removed from favorites';
        } else {
            Favorite::create([
                'user_id' => $user->id,
                'meal_id' => $meal->id,
            ]);
            $isFavorited = true;
            $message = 'Meal added to favorites';
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => [
                'is_favorited' => $isFavorited,
                'meal_id' => $meal->id,
            ],
        ], 200);
    }

    public function getFavorites(Request $request)
    {
        $user = $request->user();

        $favorites = $user->favoriteMeals()
            ->with(['restaurant', 'category'])
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Favorites retrieved successfully',
            'data' => $favorites,
        ], 200);
    }
}
