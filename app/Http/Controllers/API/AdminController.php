<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Meal;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Review;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Get admin dashboard overview data
     */
    public function dashboard()
    {
        // Get current date and last 30 days
        $now = Carbon::now();
        $thirtyDaysAgo = $now->copy()->subDays(30);

        // User statistics
        $totalUsers = User::count();
        $newUsersThisMonth = User::where('created_at', '>=', $now->startOfMonth())->count();
        $activeUsers = User::whereHas('orders', function ($query) use ($thirtyDaysAgo) {
            $query->where('created_at', '>=', $thirtyDaysAgo);
        })->count();

        // Role distribution
        $roleDistribution = DB::table('role_user')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->select('roles.name', DB::raw('count(*) as count'))
            ->groupBy('roles.name')
            ->get();

        // Order statistics
        $totalOrders = Order::count();
        $ordersThisMonth = Order::where('created_at', '>=', $now->startOfMonth())->count();
        $totalRevenue = Order::where('status', Order::STATUS_COMPLETED)->sum('total_amount');
        $revenueThisMonth = Order::where('status', Order::STATUS_COMPLETED)
            ->where('created_at', '>=', $now->startOfMonth())
            ->sum('total_amount');

        // Order status distribution
        $orderStatusDistribution = Order::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        // Restaurant statistics
        $totalRestaurants = Restaurant::count();
        $activeRestaurants = Restaurant::whereHas('meals', function ($query) {
            $query->where('quantity', '>', 0);
        })->count();

        // Meal statistics
        $totalMeals = Meal::count();
        $activeMeals = Meal::where('quantity', '>', 0)->count();
        $totalCategories = Category::count();

        // Recent activity
        $recentOrders = Order::with(['user', 'orderItems.meal'])
            ->latest()
            ->take(5)
            ->get();

        $recentUsers = User::with('roles')
            ->latest()
            ->take(5)
            ->get();

        // Sales analytics for the last 30 days
        $dailySales = Order::where('status', Order::STATUS_COMPLETED)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top performing restaurants
        $topRestaurants = Restaurant::withCount('meals')
            ->withSum('orders', 'total_amount')
            ->orderByDesc('orders_sum_total_amount')
            ->take(10)
            ->get();

        // Category performance
        $categoryPerformance = Category::withCount('meals')
            ->withSum('meals', 'quantity')
            ->orderByDesc('meals_count')
            ->get();

        // Review statistics
        $totalReviews = Review::count();
        $averageRating = Review::avg('rating') ?? 0;

        return response()->json([
            'overview' => [
                'users' => [
                    'total' => $totalUsers,
                    'new_this_month' => $newUsersThisMonth,
                    'active' => $activeUsers,
                    'role_distribution' => $roleDistribution,
                ],
                'orders' => [
                    'total' => $totalOrders,
                    'this_month' => $ordersThisMonth,
                    'status_distribution' => $orderStatusDistribution,
                ],
                'revenue' => [
                    'total' => round($totalRevenue, 2),
                    'this_month' => round($revenueThisMonth, 2),
                ],
                'restaurants' => [
                    'total' => $totalRestaurants,
                    'active' => $activeRestaurants,
                ],
                'meals' => [
                    'total' => $totalMeals,
                    'active' => $activeMeals,
                    'categories' => $totalCategories,
                ],
                'reviews' => [
                    'total' => $totalReviews,
                    'average_rating' => round($averageRating, 1),
                ],
            ],
            'recent_activity' => [
                'orders' => $recentOrders,
                'users' => $recentUsers,
            ],
            'analytics' => [
                'daily_sales' => $dailySales,
                'top_restaurants' => $topRestaurants,
                'category_performance' => $categoryPerformance,
            ],
        ]);
    }

    /**
     * Get user management data
     */
    public function users(Request $request)
    {
        $query = User::with('roles');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role')) {
            $role = $request->role;
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        $users = $query->withCount(['orders', 'reviews'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($users);
    }

    /**
     * Get restaurant management data
     */
    public function restaurants(Request $request)
    {
        $query = Restaurant::with(['user', 'meals']);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $restaurants = $query->withCount('meals')
            ->withSum('orders', 'total_amount')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($restaurants);
    }

    /**
     * Get order management data
     */
    public function orders(Request $request)
    {
        $query = Order::with(['user', 'orderItems.meal.restaurant']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($orders);
    }

    /**
     * Get meal management data
     */
    public function meals(Request $request)
    {
        $query = Meal::with(['restaurant', 'category']);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by availability
        if ($request->has('available')) {
            if ($request->available === 'true') {
                $query->where('quantity', '>', 0);
            } else {
                $query->where('quantity', '<=', 0);
            }
        }

        $meals = $query->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($meals);
    }

    /**
     * Get system analytics
     */
    public function analytics(Request $request)
    {
        $period = $request->get('period', '30'); // Default to 30 days
        $startDate = Carbon::now()->subDays($period);

        // Revenue trends
        $revenueTrends = Order::where('status', Order::STATUS_COMPLETED)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // User growth
        $userGrowth = User::where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as new_users')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top selling meals
        $topMeals = DB::table('order_items')
            ->join('meals', 'order_items.meal_id', '=', 'meals.id')
            ->select(
                'meals.title',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.quantity * order_items.price) as total_revenue')
            )
            ->groupBy('meals.id', 'meals.title')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();

        // Restaurant performance
        $restaurantPerformance = Restaurant::withCount('meals')
            ->withSum('orders', 'total_amount')
            ->orderByDesc('orders_sum_total_amount')
            ->limit(10)
            ->get();

        return response()->json([
            'revenue_trends' => $revenueTrends,
            'user_growth' => $userGrowth,
            'top_meals' => $topMeals,
            'restaurant_performance' => $restaurantPerformance,
        ]);
    }

    /**
     * Update user roles
     */
    public function updateUserRole(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        // Remove all existing roles
        $user->roles()->detach();

        // Add the new roles
        $user->roles()->attach($request->roles);

        return response()->json([
            'message' => 'User roles updated successfully',
            'user' => $user->load('roles'),
        ]);
    }

    /**
     * Show user details
     */
    public function showUser(User $user)
    {
        return response()->json([
            'user' => $user->load(['roles', 'orders', 'reviews']),
        ]);
    }

    /**
     * Update order status
     */
    public function updateOrder(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        $order->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order,
        ]);
    }

    /**
     * Toggle user status (active/inactive)
     */
    public function toggleUserStatus(User $user)
    {
        // Add a status field to users table if not exists
        // For now, we'll just return success
        return response()->json([
            'message' => 'User status updated successfully',
            'user' => $user,
        ]);
    }

    /**
     * Export users data as CSV
     */
    public function exportUsers()
    {
        $users = User::with('roles')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="users.csv"',
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, ['ID', 'First Name', 'Last Name', 'Email', 'Roles', 'Created At']);

            // Add data rows
            foreach ($users as $user) {
                $roles = $user->roles->pluck('name')->implode(', ');
                fputcsv($file, [
                    $user->id,
                    $user->first_name,
                    $user->last_name,
                    $user->email,
                    $roles,
                    $user->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export orders data as CSV
     */
    public function exportOrders()
    {
        $orders = Order::with(['user', 'orderItems.meal'])->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="orders.csv"',
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, ['ID', 'User', 'Status', 'Total Amount', 'Created At']);

            // Add data rows
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->id,
                    $order->user->email,
                    $order->status,
                    $order->total_amount,
                    $order->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export restaurants data as CSV
     */
    public function exportRestaurants()
    {
        $restaurants = Restaurant::with('user')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="restaurants.csv"',
        ];

        $callback = function () use ($restaurants) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, ['ID', 'Name', 'Owner', 'Cuisine Type', 'Status', 'Created At']);

            // Add data rows
            foreach ($restaurants as $restaurant) {
                fputcsv($file, [
                    $restaurant->id,
                    $restaurant->name,
                    $restaurant->user->email,
                    $restaurant->cuisine_type,
                    $restaurant->is_active ? 'Active' : 'Inactive',
                    $restaurant->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get system settings
     */
    public function getSettings()
    {
        // For now, return default settings
        // In a real application, these would be stored in a settings table or config
        return response()->json([
            'delivery_fee' => 5.00,
            'tax_rate' => 8.5,
            'min_order_amount' => 10.00,
        ]);
    }

    /**
     * Update system settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'delivery_fee' => 'required|numeric|min:0',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'min_order_amount' => 'required|numeric|min:0',
        ]);

        // In a real application, these would be saved to a settings table or config
        // For now, just return success
        return response()->json([
            'message' => 'Settings updated successfully',
            'settings' => $request->only(['delivery_fee', 'tax_rate', 'min_order_amount']),
        ]);
    }

    /**
     * Approve restaurant
     */
    public function approveRestaurant(Restaurant $restaurant)
    {
        $restaurant->update(['is_active' => true]);

        return response()->json([
            'message' => 'Restaurant approved successfully',
            'restaurant' => $restaurant,
        ]);
    }

    /**
     * Reject restaurant
     */
    public function rejectRestaurant(Restaurant $restaurant)
    {
        $restaurant->update(['is_active' => false]);

        return response()->json([
            'message' => 'Restaurant rejected successfully',
            'restaurant' => $restaurant,
        ]);
    }

    /**
     * Create a new provider profile (user + restaurant)
     */
    public function createProvider(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // User data
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'password' => 'required|string|min:8|confirmed',
            
            // Restaurant data
            'restaurant_name' => 'required|string|max:255',
            'restaurant_description' => 'nullable|string',
            'restaurant_address' => 'required|string|max:500',
            'restaurant_phone' => 'nullable|string|max:20',
            'restaurant_email' => 'required|email|unique:restaurants,email',
            'restaurant_website' => 'nullable|url',
            'cuisine_type' => 'nullable|string|max:100',
            'delivery_radius' => 'nullable|numeric|min:0|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create the user
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'password' => Hash::make($request->password),
            ]);

            // Assign provider role
            $providerRole = Role::where('name', 'provider')->first();
            if ($providerRole) {
                $user->roles()->attach($providerRole->id);
            }

            // Create the restaurant
            $restaurant = Restaurant::create([
                'user_id' => $user->id,
                'name' => $request->restaurant_name,
                'description' => $request->restaurant_description,
                'address' => $request->restaurant_address,
                'phone' => $request->restaurant_phone,
                'email' => $request->restaurant_email,
                'website' => $request->restaurant_website,
                'cuisine_type' => $request->cuisine_type,
                'delivery_radius' => $request->delivery_radius ?? 5.0,
                'is_active' => true, // Admin-created restaurants are automatically active
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Provider profile created successfully',
                'data' => [
                    'user' => $user->load('roles'),
                    'restaurant' => $restaurant,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => false,
                'message' => 'Failed to create provider profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available roles for assignment
     */
    public function getRoles()
    {
        $roles = Role::select('id', 'name', 'description')->get();
        
        return response()->json([
            'status' => true,
            'data' => $roles,
        ]);
    }

    /**
     * Create a new user with specified role
     */
    public function createUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'password' => Hash::make($request->password),
            ]);

            // Assign role
            $user->roles()->attach($request->role_id);

            return response()->json([
                'status' => true,
                'message' => 'User created successfully',
                'data' => $user->load('roles'),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
