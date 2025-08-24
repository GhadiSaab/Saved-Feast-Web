<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Category;
use App\Models\Restaurant;
use App\Models\Meal;
use Illuminate\Support\Facades\Hash;

class TestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $providerRole = Role::firstOrCreate(['name' => 'provider']);

        // Create test users
        $admin = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => 'admin@test.com',
                'password' => Hash::make('password123'),
                'phone' => '+1234567890',
                'address' => '123 Admin St'
            ]
        );
        $admin->roles()->sync([$adminRole->id]);

        $user = User::firstOrCreate(
            ['email' => 'user@test.com'],
            [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'user@test.com',
                'password' => Hash::make('password123'),
                'phone' => '+1234567891',
                'address' => '456 User St'
            ]
        );
        $user->roles()->sync([$userRole->id]);

        $provider = User::firstOrCreate(
            ['email' => 'provider@test.com'],
            [
                'first_name' => 'Test',
                'last_name' => 'Provider',
                'email' => 'provider@test.com',
                'password' => Hash::make('password123'),
                'phone' => '+1234567892',
                'address' => '789 Provider St'
            ]
        );
        $provider->roles()->sync([$providerRole->id]);

        // Create categories
        $categories = [
            'Italian' => 'Italian cuisine',
            'Mexican' => 'Mexican cuisine',
            'Asian' => 'Asian cuisine',
            'American' => 'American cuisine',
            'Vegetarian' => 'Vegetarian options'
        ];

        foreach ($categories as $name => $description) {
            Category::firstOrCreate(
                ['name' => $name],
                [
                    'name' => $name,
                    'description' => $description,
                    'is_active' => true
                ]
            );
        }

        // Create test restaurant
        $restaurant = Restaurant::firstOrCreate(
            ['name' => 'Test Restaurant'],
            [
                'name' => 'Test Restaurant',
                'description' => 'A test restaurant for CI/CD',
                'address' => '123 Test St',
                'phone' => '+1234567893',
                'email' => 'restaurant@test.com',
                'user_id' => $provider->id,
                'is_active' => true,
                'cuisine_type' => 'Italian',
                'delivery_radius' => 5.0,
                'average_rating' => 4.5
            ]
        );

        // Create test meals
        $meals = [
            [
                'title' => 'Margherita Pizza',
                'description' => 'Classic margherita pizza with tomato and mozzarella',
                'price' => 15.99,
                'category_id' => Category::where('name', 'Italian')->first()->id,
                'restaurant_id' => $restaurant->id,
                'is_available' => true,
                'preparation_time' => 20,
                'calories' => 800,
                'is_vegetarian' => true
            ],
            [
                'title' => 'Chicken Burger',
                'description' => 'Grilled chicken burger with fresh vegetables',
                'price' => 12.99,
                'category_id' => Category::where('name', 'American')->first()->id,
                'restaurant_id' => $restaurant->id,
                'is_available' => true,
                'preparation_time' => 15,
                'calories' => 650,
                'is_vegetarian' => false
            ],
            [
                'title' => 'Vegetable Stir Fry',
                'description' => 'Fresh vegetables stir-fried in soy sauce',
                'price' => 11.99,
                'category_id' => Category::where('name', 'Vegetarian')->first()->id,
                'restaurant_id' => $restaurant->id,
                'is_available' => true,
                'preparation_time' => 12,
                'calories' => 450,
                'is_vegetarian' => true
            ]
        ];

        foreach ($meals as $mealData) {
            Meal::firstOrCreate(
                ['title' => $mealData['title'], 'restaurant_id' => $mealData['restaurant_id']],
                $mealData
            );
        }
    }
}
