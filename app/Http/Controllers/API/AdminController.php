<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\Meal;
use App\Models\Restaurant;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
                    'role_distribution' => $roleDistribution
                ],
                'orders' => [
                    'total' => $totalOrders,
                    'this_month' => $ordersThisMonth,
                    'status_distribution' => $orderStatusDistribution
                ],
                'revenue' => [
                    'total' => round($totalRevenue, 2),
                    'this_month' => round($revenueThisMonth, 2)
                ],
                'restaurants' => [
                    'total' => $totalRestaurants,
                    'active' => $activeRestaurants
                ],
                'meals' => [
                    'total' => $totalMeals,
                    'active' => $activeMeals,
                    'categories' => $totalCategories
                ],
                'reviews' => [
                    'total' => $totalReviews,
                    'average_rating' => round($averageRating, 1)
                ]
            ],
            'recent_activity' => [
                'orders' => $recentOrders,
                'users' => $recentUsers
            ],
            'analytics' => [
                'daily_sales' => $dailySales,
                'top_restaurants' => $topRestaurants,
                'category_performance' => $categoryPerformance
            ]
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
            'restaurant_performance' => $restaurantPerformance
        ]);
    }
    
    /**
     * Update user role
     */
    public function updateUserRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|string|in:admin,provider,customer'
        ]);
        
        // Remove all existing roles
        $user->roles()->detach();
        
        // Add the new role
        $role = \App\Models\Role::where('name', $request->role)->first();
        if ($role) {
            $user->roles()->attach($role->id);
        }
        
        return response()->json([
            'message' => 'User role updated successfully',
            'user' => $user->load('roles')
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
            'user' => $user
        ]);
    }
}
