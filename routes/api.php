<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\MealController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\RestaurantApplicationController; // Import RestaurantApplicationController
use App\Http\Controllers\API\Provider\MealController as ProviderMealController; // Import Provider Meal Controller
use App\Http\Controllers\API\Provider\ProfileController as ProviderProfileController; // Import Provider Profile Controller
use App\Http\Controllers\API\CategoryController; // Import Category Controller
use Illuminate\Support\Facades\Gate; // Import Gate facade

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

// Health check endpoint for API testing
Route::get('/', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'SavedFeast API is running',
        'version' => '1.0.0',
        'timestamp' => now()->toISOString()
    ]);
});

// Apply rate limiting (6 attempts per minute) to login and register routes
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1')->name('api.register');

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('api.login');

// Public routes with rate limiting
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/meals', [MealController::class, 'index'])->name('api.meals.index');
    Route::get('/meals/filters', [MealController::class, 'filters'])->name('api.meals.filters');
    Route::get('/categories', [CategoryController::class, 'index'])->name('api.categories.index');
});

// Protected routes - require authentication with rate limiting
Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    // Favorite routes
    Route::post('/meals/{id}/favorite', [MealController::class, 'toggleFavorite'])->name('api.meals.toggleFavorite');
    Route::get('/meals/favorites', [MealController::class, 'getFavorites'])->name('api.meals.favorites');
    
    // Order routes (protected by policies)
    Route::apiResource('orders', OrderController::class)->names([
        'index' => 'api.orders.index',
        'store' => 'api.orders.store',
        'show' => 'api.orders.show',
        'update' => 'api.orders.update',
        'destroy' => 'api.orders.destroy',
    ]);
    
    // Additional order actions
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('api.orders.cancel');
    Route::post('/orders/{order}/complete', [OrderController::class, 'complete'])->name('api.orders.complete');

    // User Profile Update (protected by UserPolicy)
    Route::post('/user/profile', [AuthController::class, 'updateProfile'])->name('api.user.updateProfile');

    // Password Change (protected by UserPolicy)
    Route::post('/user/change-password', [AuthController::class, 'changePassword'])->name('api.user.changePassword');

    // Logout route
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
});

// Restaurant Application Route (Public) with rate limiting
Route::post('/restaurant-applications', [RestaurantApplicationController::class, 'store'])
    ->middleware('throttle:3,1') // 3 attempts per minute for applications
    ->name('api.restaurant-applications.store');

// Restaurant Provider Routes (Protected by auth and policies) with rate limiting
Route::middleware(['auth:sanctum', 'throttle:300,1'])->prefix('provider')->name('api.provider.')->group(function () {
    // Dashboard data (protected by provider-access gate)
    Route::get('/dashboard-data', function () {
        Gate::authorize('provider-access');
        return response()->json(['message' => 'Welcome to the Provider Dashboard!']);
    })->name('dashboard');

    // Meal Management Routes for Providers (protected by MealPolicy)
    Route::apiResource('meals', ProviderMealController::class);

    // Provider Profile Routes (protected by UserPolicy)
    Route::get('/profile', [ProviderProfileController::class, 'show'])->name('profile.show');
    Route::post('/profile/picture', [ProviderProfileController::class, 'updatePicture'])->name('profile.updatePicture');
});

// Admin routes (Protected by admin-access gate) with rate limiting
Route::middleware(['auth:sanctum', 'throttle:600,1'])->prefix('admin')->name('api.admin.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\API\AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [App\Http\Controllers\API\AdminController::class, 'users'])->name('users');
    Route::get('/restaurants', [App\Http\Controllers\API\AdminController::class, 'restaurants'])->name('restaurants');
    Route::get('/orders', [App\Http\Controllers\API\AdminController::class, 'orders'])->name('orders');
    Route::get('/meals', [App\Http\Controllers\API\AdminController::class, 'meals'])->name('meals');
    Route::get('/analytics', [App\Http\Controllers\API\AdminController::class, 'analytics'])->name('analytics');
    Route::put('/users/{user}/role', [App\Http\Controllers\API\AdminController::class, 'updateUserRole'])->name('users.updateRole');
    Route::put('/users/{user}/status', [App\Http\Controllers\API\AdminController::class, 'toggleUserStatus'])->name('users.toggleStatus');
});

// Note: Using array syntax [Controller::class, 'method'] is the modern Laravel standard
// Also corrected AuthController usage in logout route
