<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Appetizers',
                'description' => 'Small dishes served before the main course',
            ],
            [
                'name' => 'Main Courses',
                'description' => 'Primary dishes that form the most substantial part of the meal',
            ],
            [
                'name' => 'Desserts',
                'description' => 'Sweet dishes served after the main course',
            ],
            [
                'name' => 'Beverages',
                'description' => 'Drinks that complement the meal',
            ],
            [
                'name' => 'Salads',
                'description' => 'Fresh vegetable dishes, often served as sides or starters',
            ],
            [
                'name' => 'Soups',
                'description' => 'Hot liquid food typically made by boiling ingredients',
            ],
            [
                'name' => 'Breakfast',
                'description' => 'Morning meals to start the day',
            ],
            [
                'name' => 'Lunch Specials',
                'description' => 'Midday meal offerings at special prices',
            ],
            [
                'name' => 'Dinner Specials',
                'description' => 'Evening meal offerings, often more substantial',
            ],
            [
                'name' => 'Vegan Options',
                'description' => 'Plant-based dishes without any animal products',
            ],
            [
                'name' => 'Gluten-Free',
                'description' => 'Dishes that do not contain gluten',
            ],
            [
                'name' => 'Kids Menu',
                'description' => 'Smaller portions and child-friendly options',
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
