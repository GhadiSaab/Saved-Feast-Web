<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaultCategories = [
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

        $existingNames = DB::table('categories')
            ->whereIn('name', array_column($defaultCategories, 'name'))
            ->pluck('name')
            ->all();

        $timestamp = Carbon::now();

        $categoriesToInsert = [];

        foreach ($defaultCategories as $category) {
            if (! in_array($category['name'], $existingNames, true)) {
                $categoriesToInsert[] = array_merge($category, [
                    'is_active' => true,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }
        }

        if (! empty($categoriesToInsert)) {
            DB::table('categories')->insert($categoriesToInsert);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('categories')
            ->whereIn('name', [
                'Appetizers',
                'Main Courses',
                'Desserts',
                'Beverages',
                'Salads',
                'Soups',
                'Breakfast',
                'Lunch Specials',
                'Dinner Specials',
                'Vegan Options',
                'Gluten-Free',
                'Kids Menu',
            ])
            ->delete();
    }
};
