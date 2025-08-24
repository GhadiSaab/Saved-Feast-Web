<?php

namespace Database\Seeders;

use App\Models\Meal;
use App\Models\Restaurant;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;

class MealSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get all restaurant IDs
        $restaurantIds = Restaurant::pluck('id')->toArray();
        
        // Get all category IDs
        $categoryIds = Category::pluck('id')->toArray();
        
        // If no restaurants or categories exist, we can't create meals
        if (empty($restaurantIds) || empty($categoryIds)) {
            $this->command->info('Please seed restaurants and categories first!');
            return;
        }
        
        // Sample meal names for more realistic data
        $mealNames = [
            'Chicken Biryani', 'Veggie Pizza', 'Beef Burger', 'Caesar Salad', 
            'Sushi Platter', 'Pasta Carbonara', 'Grilled Salmon', 'Chicken Alfredo',
            'Margherita Pizza', 'Vegetable Stir Fry', 'Fish and Chips', 'Beef Steak',
            'Vegetable Curry', 'Mushroom Risotto', 'Chicken Wings', 'Lamb Chops',
            'Vegetable Lasagna', 'Shrimp Scampi', 'Beef Tacos', 'Greek Salad'
        ];
        
        // Statuses with weighted distribution
        $statuses = ['available' => 7, 'expired' => 2, 'sold_out' => 1];
        $statusOptions = [];
        foreach ($statuses as $status => $weight) {
            for ($i = 0; $i < $weight; $i++) {
                $statusOptions[] = $status;
            }
        }
        
        // Create 50 meals
        for ($i = 0; $i < 50; $i++) {
            $restaurantId = $faker->randomElement($restaurantIds);
            $status = $faker->randomElement($statusOptions);

            // Generate valid availability times regardless of status to meet validation rules
            // For available meals: available_from should be <= now (already started)
            // For expired meals: available_until should be < now (already ended)
            if ($status == 'available') {
                $availableFrom = Carbon::now()->subMinutes($faker->numberBetween(0, 60)); // Start time between 1 hour ago and now
                $availableUntil = Carbon::now()->addHours($faker->numberBetween(1, 6)); // End time 1-6 hours from now
            } elseif ($status == 'expired') {
                $availableFrom = Carbon::now()->subHours($faker->numberBetween(2, 8)); // Start time 2-8 hours ago
                $availableUntil = Carbon::now()->subMinutes($faker->numberBetween(0, 60)); // End time between 1 hour ago and now
            } else { // sold_out
                $availableFrom = Carbon::now()->subMinutes($faker->numberBetween(0, 30)); // Start time between 30 minutes ago and now
                $availableUntil = Carbon::now()->addHours($faker->numberBetween(1, 4)); // End time 1-4 hours from now
            }

            // Generate prices
            $currentPrice = $faker->randomFloat(2, 5, 50);
            // 70% chance of having an original price higher than the current price
            $originalPrice = $faker->optional(0.7, null)->randomFloat(2, $currentPrice + 1, $currentPrice + 20);

            Meal::create([
                'restaurant_id' => $restaurantId,
                'category_id' => $faker->randomElement($categoryIds),
                'title' => $faker->randomElement($mealNames),
                'description' => $faker->paragraph(2),
                'current_price' => $currentPrice, // Use current_price
                'original_price' => $originalPrice, // Add original_price (can be null)
                'quantity' => $status == 'sold_out' ? 0 : ($status == 'available' ? $faker->numberBetween(1, 20) : $faker->numberBetween(0, 5)),
                'available_from' => $availableFrom,
                'available_until' => $availableUntil,
                'status' => $status,
                'image' => 'meals/meal-' . $faker->numberBetween(1, 10) . '.jpg',
            ]);
        }
    }
}
