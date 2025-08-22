<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run seeders in the correct order to respect dependencies
        $this->call([
            UserSeeder::class,
            AdminSeeder::class,
            CategorySeeder::class,
            RestaurantSeeder::class,
            MealSeeder::class,
        ]);
    }
}
