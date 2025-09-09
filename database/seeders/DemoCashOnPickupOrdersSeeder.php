<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Meal;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\CommissionService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoCashOnPickupOrdersSeeder extends Seeder
{
    protected CommissionService $commissionService;

    public function __construct(CommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create demo data for testing cash-on-pickup orders and invoicing

        // Create a demo user if not exists
        $user = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'first_name' => 'Demo',
                'last_name' => 'User',
                'password' => bcrypt('password'),
            ]
        );

        // Create a demo restaurant if not exists
        $restaurant = Restaurant::firstOrCreate(
            ['email' => 'demo-restaurant@example.com'],
            [
                'user_id' => $user->id,
                'name' => 'Demo Restaurant',
                'description' => 'A demo restaurant for testing',
                'address' => '123 Demo Street, Demo City',
                'phone' => '+1234567890',
                'commission_rate' => 7.0,
                'is_active' => true,
            ]
        );

        // Create a demo category if not exists
        $category = Category::firstOrCreate(
            ['name' => 'Demo Category'],
            ['description' => 'A demo category for testing']
        );

        // Create demo meals if not exist
        $meals = [];
        for ($i = 1; $i <= 3; $i++) {
            $meals[] = Meal::firstOrCreate(
                ['title' => "Demo Meal {$i}"],
                [
                    'restaurant_id' => $restaurant->id,
                    'category_id' => $category->id,
                    'description' => "A delicious demo meal number {$i}",
                    'current_price' => 10.00 + ($i * 2),
                    'original_price' => 15.00 + ($i * 3),
                    'quantity' => 10,
                    'status' => 'available',
                ]
            );
        }

        // Create demo orders for the last week (completed cash-on-pickup orders)
        $lastWeek = Carbon::now()->subWeek();
        $ordersCreated = 0;

        for ($day = 0; $day < 7; $day++) {
            $orderDate = $lastWeek->copy()->addDays($day);

            // Create 1-3 orders per day
            $ordersPerDay = rand(1, 3);

            for ($orderNum = 0; $orderNum < $ordersPerDay; $orderNum++) {
                $orderTime = $orderDate->copy()->setTime(rand(10, 20), rand(0, 59));

                // Calculate order total
                $meal = $meals[array_rand($meals)];
                $quantity = rand(1, 3);
                $totalAmount = $meal->current_price * $quantity;

                // Calculate commission
                $commission = $this->commissionService->calculateOrderCommission($totalAmount, $restaurant);

                // Create the order
                $order = Order::create([
                    'user_id' => $user->id,
                    'total_amount' => $totalAmount,
                    'status' => Order::STATUS_COMPLETED,
                    'payment_method' => 'CASH_ON_PICKUP',
                    'commission_rate' => $commission['rate'],
                    'commission_amount' => $commission['amount'],
                    'completed_at' => $orderTime,
                    'pickup_time' => $orderTime->copy()->addMinutes(30),
                    'notes' => 'Demo order for testing',
                ]);

                // Create order item
                OrderItem::create([
                    'order_id' => $order->id,
                    'meal_id' => $meal->id,
                    'quantity' => $quantity,
                    'price' => $meal->current_price,
                    'original_price' => $meal->original_price,
                ]);

                $ordersCreated++;
            }
        }

        $this->command->info("Created {$ordersCreated} demo cash-on-pickup orders for the last week");
        $this->command->info("Restaurant: {$restaurant->name} (ID: {$restaurant->id})");
        $this->command->info("User: {$user->first_name} {$user->last_name} (ID: {$user->id})");
        $this->command->info("You can now run 'php artisan invoices:generate-weekly --period=previous' to generate invoices for these orders");
    }
}
