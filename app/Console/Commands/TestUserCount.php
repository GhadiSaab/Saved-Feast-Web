<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Meal;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Review;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestUserCount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:test-counts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test dashboard counts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info("=== Testing Dashboard Counts ===\n");

            // Test user counts
            $totalUsers = User::count();
            $this->info('Total Users: '.$totalUsers);

            $users = User::all(['id', 'first_name', 'last_name', 'email']);
            if ($users->count() > 0) {
                $this->info('Users in database:');
                foreach ($users as $user) {
                    $roles = $user->roles->pluck('name')->toArray();
                    $this->info('- '.$user->first_name.' '.$user->last_name.' ('.$user->email.') - Roles: '.implode(', ', $roles));
                }
            } else {
                $this->warn('No users found in database!');
            }

            // Test other counts
            $totalOrders = Order::count();
            $this->info("\nTotal Orders: ".$totalOrders);

            $totalRestaurants = Restaurant::count();
            $this->info('Total Restaurants: '.$totalRestaurants);

            $totalMeals = Meal::count();
            $this->info('Total Meals: '.$totalMeals);

            $totalCategories = Category::count();
            $this->info('Total Categories: '.$totalCategories);

            $totalReviews = Review::count();
            $this->info('Total Reviews: '.$totalReviews);

            // Test role distribution
            $this->info("\n=== Role Distribution ===");
            $roleDistribution = DB::table('role_user')
                ->join('roles', 'role_user.role_id', '=', 'roles.id')
                ->select('roles.name', DB::raw('count(*) as count'))
                ->groupBy('roles.name')
                ->get();

            foreach ($roleDistribution as $role) {
                $this->info($role->name.': '.$role->count);
            }

            // Test API endpoint
            $this->info("\n=== Testing API Endpoint ===");
            $this->info('You can test the API endpoint manually:');
            $this->info('GET /api/admin/dashboard');
            $this->info('Make sure to include the Authorization header with your admin token.');

        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());
            $this->error('Stack trace: '.$e->getTraceAsString());
        }
    }
}
