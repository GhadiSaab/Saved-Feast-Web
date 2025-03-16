<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Public routes
Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/about', function () {
    return view('about');
})->name('about');
Route::get('/contact', function () {
    return view('contact');
})->name('contact');
Route::get('/how-it-works', function () {
    return view('how-it-works');
})->name('how-it-works');
Route::get('/faq', function () {
    return view('faq');
})->name('faq');

// Authentication views - these just serve the view, actual auth happens via API
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::get('/password/reset', function () {
    return view('auth.passwords.email');
})->name('password.request');

Route::get('/password/reset/{token}', function ($token) {
    return view('auth.passwords.reset', ['token' => $token]);
})->name('password.reset');

// Protected routes with middleware verification
Route::middleware(['auth:api'])->group(function () {
    // Route::get('/dashboard', 'DashboardController@index')->name('dashboard');
    // Route::get('/profile', 'ProfileController@edit')->name('profile.edit');
    // Route::put('/profile', 'ProfileController@update')->name('profile.update');
    
    // // Order routes
    // Route::get('/orders', 'OrderController@index')->name('orders.index');
    // Route::get('/orders/{order}', 'OrderController@show')->name('orders.show');
    // Route::post('/orders', 'OrderController@store')->name('orders.store');
    // Route::patch('/orders/{order}/cancel', 'OrderController@cancel')->name('orders.cancel');
    
    // // Provider specific routes
    // Route::middleware(['role:provider'])->prefix('provider')->name('provider.')->group(function () {
    //     Route::get('/dashboard', 'Provider\DashboardController@index')->name('dashboard');
    //     Route::resource('meals', 'Provider\MealController');
    //     Route::patch('/orders/{order}/status', 'Provider\OrderController@updateStatus')->name('orders.update-status');
    // });
});

// Meal routes (some public, some restricted)
// Route::get('/meals/{meal}', 'MealController@show')->name('meals.show');
// Route::get('/providers/{provider}', 'ProviderController@show')->name('providers.show');
