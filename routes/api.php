<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\MealController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\RestaurantApplicationController; // Import RestaurantApplicationController
use App\Http\Controllers\API\Provider\MealController as ProviderMealController; // Import Provider Meal Controller
use App\Http\Controllers\API\CategoryController; // Import Category Controller

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Apply rate limiting (6 attempts per minute) to login and register routes
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1')->name('api.register');

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('api.login');

Route::get('/meals', [MealController::class, 'index'])->name('api.meals.index');

// Categories Route (Public or Authenticated - accessible to providers for dropdown)
Route::get('/categories', [CategoryController::class, 'index'])->name('api.categories.index');

// Restaurant Application Route (Public)
Route::post('/restaurant-applications', [RestaurantApplicationController::class, 'store'])->name('api.restaurant-applications.store');

// Order routes (protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'index'])->name('api.orders.index');
    Route::post('/orders', [OrderController::class, 'store'])->name('api.orders.store');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('api.orders.show');
    Route::put('/orders/{id}', [OrderController::class, 'update'])->name('api.orders.update'); // General update (e.g., status)
    Route::delete('/orders/{id}', [OrderController::class, 'destroy'])->name('api.orders.destroy');
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel'])->name('api.orders.cancel');
    Route::post('/orders/{id}/complete', [OrderController::class, 'complete'])->name('api.orders.complete');

    // Logout route
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
});

// Restaurant Provider Routes (Protected by auth and role)
Route::middleware(['auth:sanctum', 'role:provider'])->prefix('provider')->name('api.provider.')->group(function () {
    // Placeholder route - we will add meal management routes here later
    Route::get('/dashboard-data', function () {
        return response()->json(['message' => 'Welcome to the Provider Dashboard!']);
    })->name('dashboard');

    // Meal Management Routes for Providers
    Route::apiResource('meals', ProviderMealController::class);
});


// Note: Using array syntax [Controller::class, 'method'] is the modern Laravel standard
// Also corrected AuthController usage in logout route
