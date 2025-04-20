<?php

namespace App\Http\Controllers\API\Provider;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Category; // Add Category model import

class MealController extends Controller
{
    /**
     * Display a listing of the provider's meals.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        // Assuming the provider user has one associated restaurant
        $restaurant = Restaurant::where('user_id', $user->id)->first();

        if (!$restaurant) {
            return response()->json(['message' => 'Restaurant not found for this provider.'], 404);
        }

        // Fetch meals associated with the provider's restaurant
        // Eager load category for efficiency if needed later
        $meals = Meal::where('restaurant_id', $restaurant->id)->with('category')->get();

        return response()->json($meals);
    }

    /**
     * Store a newly created meal in storage for the provider's restaurant.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $restaurant = Restaurant::where('user_id', $user->id)->first();

        if (!$restaurant) {
            return response()->json(['message' => 'Restaurant not found for this provider.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0', // Add validation for quantity
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id'), // Ensure category exists
            ],
            'image_url' => 'nullable|url|max:2048', // Optional image URL
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422); // Unprocessable Entity
        }

        $validatedData = $validator->validated();

        $meal = new Meal();
        // Map 'name' from request to 'title' in model
        $mealDataToFill = $validatedData;
        if (isset($mealDataToFill['name'])) {
            $mealDataToFill['title'] = $mealDataToFill['name'];
            unset($mealDataToFill['name']); // Remove 'name' before filling
        }
        // Map 'image_url' from request to 'image' in model if necessary
        if (isset($mealDataToFill['image_url'])) {
            $mealDataToFill['image'] = $mealDataToFill['image_url'];
            unset($mealDataToFill['image_url']);
        }

        $meal->fill($mealDataToFill);
        $meal->restaurant_id = $restaurant->id; // Associate with the provider's restaurant
        $meal->save();

        // Optionally load the category relationship for the response
        $meal->load('category');

        return response()->json($meal, 201); // Created
    }

    /**
     * Display the specified meal belonging to the provider.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $restaurant = Restaurant::where('user_id', $user->id)->first();

        if (!$restaurant) {
            return response()->json(['message' => 'Restaurant not found for this provider.'], 404);
        }

        $meal = Meal::with('category')->find($id);

        if (!$meal) {
            return response()->json(['message' => 'Meal not found.'], 404);
        }

        // Authorization check: Ensure the meal belongs to the provider's restaurant
        if ($meal->restaurant_id !== $restaurant->id) {
            return response()->json(['message' => 'Unauthorized access to this meal.'], 403); // Forbidden
        }

        return response()->json($meal);
    }

    /**
     * Update the specified meal in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $restaurant = Restaurant::where('user_id', $user->id)->first();

        if (!$restaurant) {
            return response()->json(['message' => 'Restaurant not found for this provider.'], 404);
        }

        $meal = Meal::find($id);

        if (!$meal) {
            return response()->json(['message' => 'Meal not found.'], 404);
        }

        // Authorization check
        if ($meal->restaurant_id !== $restaurant->id) {
            return response()->json(['message' => 'Unauthorized access to update this meal.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255', // sometimes: only validate if present
            'description' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric|min:0',
            'category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('categories', 'id'),
            ],
            'image_url' => 'nullable|url|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $validatedData = $validator->validated();

        // Map 'name' from request to 'title' in model for update
        $mealDataToFill = $validatedData;
        if (isset($mealDataToFill['name'])) {
            $mealDataToFill['title'] = $mealDataToFill['name'];
            unset($mealDataToFill['name']);
        }
        // Map 'image_url' from request to 'image' in model if necessary
        if (array_key_exists('image_url', $mealDataToFill)) { // Check existence even if null
            $mealDataToFill['image'] = $mealDataToFill['image_url'];
            unset($mealDataToFill['image_url']);
        }

        $meal->fill($mealDataToFill);
        $meal->save();

        $meal->load('category'); // Reload relationship if category_id was updated

        return response()->json($meal);
    }

    /**
     * Remove the specified meal from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $restaurant = Restaurant::where('user_id', $user->id)->first();

        if (!$restaurant) {
            return response()->json(['message' => 'Restaurant not found for this provider.'], 404);
        }

        $meal = Meal::find($id);

        if (!$meal) {
            return response()->json(['message' => 'Meal not found.'], 404);
        }

        // Authorization check
        if ($meal->restaurant_id !== $restaurant->id) {
            return response()->json(['message' => 'Unauthorized access to delete this meal.'], 403);
        }

        $meal->delete();

        return response()->json(null, 204); // No content on successful deletion
    }
}
