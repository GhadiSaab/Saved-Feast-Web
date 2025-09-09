<?php

use App\Http\Controllers\API\Admin\SettlementsController as AdminSettlementsController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\MealController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\Provider\MealController as ProviderMealController; // Import RestaurantApplicationController
use App\Http\Controllers\API\Provider\ProfileController as ProviderProfileController;
use App\Http\Controllers\API\Provider\SettlementsController as ProviderSettlementsController;
use App\Http\Controllers\API\RestaurantApplicationController; // Import Provider Meal Controller
use Illuminate\Http\Request; // Import Provider Profile Controller
use Illuminate\Support\Facades\Gate; // Import Category Controller
use Illuminate\Support\Facades\Route; // Import Gate facade

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
        'timestamp' => now()->toISOString(),
    ]);
});

// Apply rate limiting (20 attempts per minute) to login and register routes
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:20,1')->name('api.register');

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1')->name('api.login');

// Public routes with rate limiting
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/meals', [MealController::class, 'index'])->name('api.meals.index');
    Route::get('/meals/filters', [MealController::class, 'filters'])->name('api.meals.filters');
    Route::get('/categories', [CategoryController::class, 'index'])->name('api.categories.index');
    Route::get('/restaurants', [App\Http\Controllers\API\RestaurantController::class, 'index'])->name('api.restaurants.index');
    Route::get('/restaurants/{restaurant}', [App\Http\Controllers\API\RestaurantController::class, 'show'])->name('api.restaurants.show');
    Route::get('/restaurants/{restaurant}/meals', [App\Http\Controllers\API\RestaurantController::class, 'meals'])->name('api.restaurants.meals');
    Route::get('/restaurants/{restaurant}/ratings', [App\Http\Controllers\API\RestaurantController::class, 'ratings'])->name('api.restaurants.ratings');
});

// Protected routes - require authentication with rate limiting
Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    // Favorite routes
    Route::post('/meals/{id}/favorite', [MealController::class, 'toggleFavorite'])->name('api.meals.toggleFavorite');
    Route::get('/meals/favorites', [MealController::class, 'getFavorites'])->name('api.meals.favorites');

    // Restaurant routes
    Route::post('/restaurants', [App\Http\Controllers\API\RestaurantController::class, 'store'])->name('api.restaurants.store');
    Route::put('/restaurants/{restaurant}', [App\Http\Controllers\API\RestaurantController::class, 'update'])->name('api.restaurants.update');

    // Order routes (protected by policies)
    Route::apiResource('orders', OrderController::class)->names([
        'index' => 'api.orders.index',
        'store' => 'api.orders.store',
        'show' => 'api.orders.show',
        'update' => 'api.orders.update',
        'destroy' => 'api.orders.destroy',
    ]);

    // Additional order actions
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('api.orders.cancel');
    Route::post('/orders/{order}/complete', [OrderController::class, 'complete'])->name('api.orders.complete');

    // Customer order management routes
    Route::get('/me/orders', [OrderController::class, 'getMyOrders'])->name('api.orders.my-orders');
    Route::get('/orders/{order}/details', [OrderController::class, 'getOrder'])->name('api.orders.details');
    Route::post('/orders/{order}/cancel-my-order', [OrderController::class, 'cancelMyOrder'])->name('api.orders.cancel-my-order');
    Route::post('/orders/{order}/resend-code', [OrderController::class, 'resendCode'])->name('api.orders.resend-code');
    Route::get('/orders/{order}/show-code', [OrderController::class, 'showPickupCode'])->name('api.orders.show-code');
    Route::post('/orders/{order}/claim', [OrderController::class, 'claimOrder'])->name('api.orders.claim');

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

    // Restaurant Management Routes for Providers
    Route::post('/restaurants', [App\Http\Controllers\API\RestaurantController::class, 'store'])->name('restaurants.store');
    Route::put('/restaurants/{restaurant}', [App\Http\Controllers\API\RestaurantController::class, 'update'])->name('restaurants.update');
    Route::get('/restaurants', [App\Http\Controllers\API\RestaurantController::class, 'providerRestaurants'])->name('restaurants.index');

    // Provider Settlements Routes
    Route::get('/settlements/summary', [ProviderSettlementsController::class, 'summary'])->name('settlements.summary');
    Route::get('/settlements/invoices', [ProviderSettlementsController::class, 'invoices'])->name('settlements.invoices');
    Route::get('/settlements/invoices/{id}', [ProviderSettlementsController::class, 'showInvoice'])->name('settlements.invoices.show');
    Route::get('/settlements/invoices/{id}/download', [ProviderSettlementsController::class, 'downloadInvoice'])->name('settlements.invoices.download');
    Route::get('/settlements/orders', [ProviderSettlementsController::class, 'recentOrders'])->name('settlements.orders');

    // Provider Order Management Routes
    // Define specific routes BEFORE apiResource to avoid route conflicts
    Route::get('/orders/stats', [\App\Http\Controllers\API\Provider\OrderController::class, 'stats'])->name('orders.stats');
    Route::post('/orders/{order}/accept', [\App\Http\Controllers\API\Provider\OrderController::class, 'accept'])->name('provider.orders.accept');
    Route::post('/orders/{order}/mark-ready', [\App\Http\Controllers\API\Provider\OrderController::class, 'markReady'])->name('provider.orders.mark-ready');
    Route::post('/orders/{order}/complete', [\App\Http\Controllers\API\Provider\OrderController::class, 'complete'])->name('provider.orders.complete');
    Route::post('/orders/{order}/cancel', [\App\Http\Controllers\API\Provider\OrderController::class, 'cancel'])->name('provider.orders.cancel');

    // Define apiResource routes AFTER specific routes
    Route::apiResource('orders', \App\Http\Controllers\API\Provider\OrderController::class)->names([
        'index' => 'provider.orders.index',
        'show' => 'provider.orders.show',
    ]);
});

// Admin routes (Protected by admin-access gate) with rate limiting
Route::middleware(['auth:sanctum', 'throttle:600,1'])->prefix('admin')->name('api.admin.')->group(function () {
    Route::middleware('role:admin')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\API\AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users', [App\Http\Controllers\API\AdminController::class, 'users'])->name('users');
        Route::get('/users/{user}', [App\Http\Controllers\API\AdminController::class, 'showUser'])->name('users.show');
        Route::get('/restaurants', [App\Http\Controllers\API\AdminController::class, 'restaurants'])->name('restaurants');
        Route::get('/orders', [App\Http\Controllers\API\AdminController::class, 'orders'])->name('orders');
        Route::get('/meals', [App\Http\Controllers\API\AdminController::class, 'meals'])->name('meals');
        Route::get('/analytics', [App\Http\Controllers\API\AdminController::class, 'analytics'])->name('analytics');
        Route::put('/users/{user}/roles', [App\Http\Controllers\API\AdminController::class, 'updateUserRole'])->name('users.updateRole');
        Route::put('/users/{user}/status', [App\Http\Controllers\API\AdminController::class, 'toggleUserStatus'])->name('users.toggleStatus');
        Route::put('/orders/{order}', [App\Http\Controllers\API\AdminController::class, 'updateOrder'])->name('orders.update');

        // Export routes
        Route::get('/export/users', [App\Http\Controllers\API\AdminController::class, 'exportUsers'])->name('export.users');
        Route::get('/export/orders', [App\Http\Controllers\API\AdminController::class, 'exportOrders'])->name('export.orders');
        Route::get('/export/restaurants', [App\Http\Controllers\API\AdminController::class, 'exportRestaurants'])->name('export.restaurants');

        // Settings routes
        Route::get('/settings', [App\Http\Controllers\API\AdminController::class, 'getSettings'])->name('settings.get');
        Route::put('/settings', [App\Http\Controllers\API\AdminController::class, 'updateSettings'])->name('settings.update');

        // Restaurant approval routes
        Route::put('/restaurants/{restaurant}/approve', [App\Http\Controllers\API\AdminController::class, 'approveRestaurant'])->name('restaurants.approve');
        Route::put('/restaurants/{restaurant}/reject', [App\Http\Controllers\API\AdminController::class, 'rejectRestaurant'])->name('restaurants.reject');

        // User and provider creation routes
        Route::get('/roles', [App\Http\Controllers\API\AdminController::class, 'getRoles'])->name('roles.get');
        Route::post('/users', [App\Http\Controllers\API\AdminController::class, 'createUser'])->name('users.create');
        Route::post('/providers', [App\Http\Controllers\API\AdminController::class, 'createProvider'])->name('providers.create');

        // Admin Settlements Routes
        Route::post('/settlements/generate', [AdminSettlementsController::class, 'generate'])->name('settlements.generate');
        Route::get('/settlements/invoices', [AdminSettlementsController::class, 'invoices'])->name('settlements.invoices');
        Route::post('/settlements/invoices/{id}/mark-sent', [AdminSettlementsController::class, 'markSent'])->name('settlements.invoices.mark-sent');
        Route::post('/settlements/invoices/{id}/mark-paid', [AdminSettlementsController::class, 'markPaid'])->name('settlements.invoices.mark-paid');
        Route::post('/settlements/invoices/{id}/mark-overdue', [AdminSettlementsController::class, 'markOverdue'])->name('settlements.invoices.mark-overdue');
        Route::get('/settlements/invoices/{id}', [AdminSettlementsController::class, 'show'])->name('settlements.invoices.show');
        Route::get('/settlements/invoices/{id}/download', [AdminSettlementsController::class, 'download'])->name('settlements.invoices.download');
    });
});

// Note: Using array syntax [Controller::class, 'method'] is the modern Laravel standard
// Also corrected AuthController usage in logout route
