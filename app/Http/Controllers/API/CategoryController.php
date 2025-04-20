<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Return all categories, ordered by name
        $categories = Category::orderBy('name')->get();
        return response()->json($categories);
    }
}
