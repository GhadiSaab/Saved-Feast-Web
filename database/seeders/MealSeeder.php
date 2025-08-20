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
            // available_from should be >= now
            $availableFrom = Carbon::now()->addMinutes($faker->numberBetween(0, 120)); // Start time between now and 2 hours from now
            // available_until should be > available_from
            $availableUntil = $availableFrom->copy()->addHours($faker->numberBetween(2, 8)); // End time 2-8 hours after start time

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
                'quantity' => $status == 'sold_out' ? 0 : $faker->numberBetween(1, 20),
                'available_from' => $availableFrom,
                'available_until' => $availableUntil,
                'status' => $status,
                'image' => 'meals/meal-' . $faker->numberBetween(1, 10) . '.jpg',
            ]);
        }
    }
}
