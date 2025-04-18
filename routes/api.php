<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\MealController;

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

Route::post('/register', 'App\Http\Controllers\API\AuthController@register')->name('api.register');

Route::post('/login', 'App\Http\Controllers\API\AuthController@login')->name('api.login');

Route::get('/meals', 'App\Http\Controllers\API\MealController@index')->name('api.meals.index');

// Logout route (protected)
Route::middleware('auth:sanctum')->post('/logout', 'App\Http\Controllers\API\AuthController@logout')->name('api.logout');
