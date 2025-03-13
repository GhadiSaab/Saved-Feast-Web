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
            
            // Set availability times based on status
            $availableFrom = null;
            $availableUntil = null;
            
            if ($status == 'available') {
                $availableFrom = Carbon::now()->subHours($faker->numberBetween(1, 5));
                $availableUntil = Carbon::now()->addHours($faker->numberBetween(2, 12));
            } elseif ($status == 'expired') {
                $availableFrom = Carbon::now()->subDays($faker->numberBetween(1, 7));
                $availableUntil = Carbon::now()->subHours($faker->numberBetween(1, 24));
            } elseif ($status == 'sold_out') {
                $availableFrom = Carbon::now()->subHours($faker->numberBetween(1, 24));
                $availableUntil = Carbon::now()->addHours($faker->numberBetween(1, 12));
            }
            
            Meal::create([
                'restaurant_id' => $restaurantId,
                'category_id' => $faker->randomElement($categoryIds),
                'title' => $faker->randomElement($mealNames),
                'description' => $faker->paragraph(2),
                'price' => $faker->randomFloat(2, 5, 50),
                'quantity' => $status == 'sold_out' ? 0 : $faker->numberBetween(1, 20),
                'available_from' => $availableFrom,
                'available_until' => $availableUntil,
                'status' => $status,
                'image' => 'meals/meal-' . $faker->numberBetween(1, 10) . '.jpg',
            ]);
        }
    }
}
