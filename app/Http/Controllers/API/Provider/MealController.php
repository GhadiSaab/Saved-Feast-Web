<?php

namespace App\Http\Controllers\API\Provider;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Meal;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Add Storage facade
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule; // Add Category model import

class MealController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->authorizeResource(Meal::class, 'meal');
    }

    /**
     * Display a listing of the provider's meals.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Check if user can manage meals
        $this->authorize('manage-meals');

        // Assuming the provider user has one associated restaurant
        $restaurant = Restaurant::where('user_id', $user->id)->first();

        if (! $restaurant) {
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

        // Check if user can create meals
        $this->authorize('create', Meal::class);

        $restaurant = Restaurant::where('user_id', $user->id)->first();

        if (! $restaurant) {
            return response()->json(['message' => 'Restaurant not found for this provider.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255', // Changed from name
            'description' => 'required|string',
            'current_price' => 'required|numeric|min:0', // Changed from price
            'original_price' => 'nullable|numeric|min:0|gte:current_price', // Added original_price, must be >= current_price if present
            'quantity' => 'required|integer|min:0',
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id'), // Ensure category exists
            ],
            // 'image_url' => 'nullable|url|max:2048', // Optional image URL - REMOVED
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Optional image file upload
            'available_from' => 'required|date|after_or_equal:now', // Must be a date, now or in the future
            'available_until' => 'required|date|after:available_from', // Must be a date after available_from
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422); // Unprocessable Entity
        }

        $validatedData = $validator->validated();

        $meal = new Meal;
        // Directly use validated data, no need to map 'name' anymore
        $mealDataToFill = $validatedData;
        // // Map 'image_url' from request to 'image' in model if necessary - REMOVED
        // if (isset($mealDataToFill['image_url'])) {
        //     $mealDataToFill['image'] = $mealDataToFill['image_url'];
        //     unset($mealDataToFill['image_url']);
        // }

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('public/meals'); // Store in storage/app/public/meals
            // Store the relative path accessible via the public disk link
            $mealDataToFill['image'] = Storage::url($path); // Get URL like /storage/meals/filename.jpg
        } elseif (isset($mealDataToFill['image'])) {
            // Ensure 'image' key is removed if no file is uploaded but was in validated data (shouldn't happen with file validation)
            unset($mealDataToFill['image']);
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

        if (! $restaurant) {
            return response()->json(['message' => 'Restaurant not found for this provider.'], 404);
        }

        $meal = Meal::with('category')->find($id);

        if (! $meal) {
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

        if (! $restaurant) {
            return response()->json(['message' => 'Restaurant not found for this provider.'], 404);
        }

        $meal = Meal::find($id);

        if (! $meal) {
            return response()->json(['message' => 'Meal not found.'], 404);
        }

        // Authorization check
        if ($meal->restaurant_id !== $restaurant->id) {
            return response()->json(['message' => 'Unauthorized access to update this meal.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255', // Changed from name
            'description' => 'sometimes|required|string',
            'current_price' => 'sometimes|required|numeric|min:0', // Changed from price
            'original_price' => 'nullable|numeric|min:0|gte:current_price', // Added original_price validation
            'quantity' => 'sometimes|required|integer|min:0', // Added quantity validation
            'category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('categories', 'id'),
            ],
            // 'image_url' => 'nullable|url|max:2048', // REMOVED
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Optional image file upload for update
            'available_from' => 'sometimes|required|date', // Allow updating start time
            'available_until' => 'sometimes|required|date|after:available_from', // Allow updating end time, must be after start
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $validatedData = $validator->validated();

        // Directly use validated data, no need to map 'name' anymore
        $mealDataToFill = $validatedData;
        // // Map 'image_url' from request to 'image' in model if necessary - REMOVED
        // if (array_key_exists('image_url', $mealDataToFill)) { // Check existence even if null
        //     $mealDataToFill['image'] = $mealDataToFill['image_url'];
        //     unset($mealDataToFill['image_url']);
        // }

        // Handle image update
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($meal->image) {
                // Convert URL back to storage path if needed
                $oldPath = str_replace('/storage/', 'public/', $meal->image);
                Storage::delete($oldPath);
            }
            // Store new image
            $path = $request->file('image')->store('public/meals');
            $mealDataToFill['image'] = Storage::url($path); // Store the URL
        } elseif (isset($mealDataToFill['image'])) {
            // Ensure 'image' key is removed if no file is uploaded but was in validated data
            unset($mealDataToFill['image']);
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

        if (! $restaurant) {
            return response()->json(['message' => 'Restaurant not found for this provider.'], 404);
        }

        $meal = Meal::find($id);

        if (! $meal) {
            return response()->json(['message' => 'Meal not found.'], 404);
        }

        // Authorization check
        if ($meal->restaurant_id !== $restaurant->id) {
            return response()->json(['message' => 'Unauthorized access to delete this meal.'], 403);
        }

        // Delete associated image file before deleting the meal record
        if ($meal->image) {
            // Convert URL back to storage path
            $imagePath = str_replace('/storage/', 'public/', $meal->image);
            Storage::delete($imagePath);
        }

        $meal->delete();

        return response()->json(null, 204); // No content on successful deletion
    }
}
