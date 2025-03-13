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

Route::post(uri: '/register', action: 'App\Http\Controllers\API\AuthController@register');

Route::post(uri: '/login', action: 'App\Http\Controllers\API\AuthController@login');

Route::get(uri: '/meals', action: 'App\Http\Controllers\API\MealController@index');
