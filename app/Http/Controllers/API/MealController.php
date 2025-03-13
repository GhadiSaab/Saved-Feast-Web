<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Meal;
use Exception;

class MealController extends Controller
{
    public function index()
    {
        try {
            $meals = Meal::all();
            
            return response()->json([
                'status' => true,
                'message' => 'Meals retrieved successfully',
                'data' => $meals
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve meals',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
