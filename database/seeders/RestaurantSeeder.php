<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class RestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get all user IDs
        $userIds = User::pluck('id')->toArray();
        
        // If no users exist, we can't create restaurants
        if (empty($userIds)) {
            $this->command->info('Please seed users first!');
            return;
        }
        
        // Create 10 sample restaurants
        for ($i = 0; $i < 10; $i++) {
            Restaurant::create([
                'name' => $faker->company() . ' ' . $faker->randomElement(['Restaurant', 'Bistro', 'Café', 'Diner', 'Eatery']),
                'description' => $faker->paragraph(3),
                'phone' => $faker->phoneNumber(),
                'address' => $faker->address(),
                'email' => $faker->email(),
                'website' => $faker->url(),
                'image' => 'restaurants/restaurant-' . $faker->numberBetween(1, 10) . '.jpg',
                'user_id' => $faker->randomElement($userIds),
            ]);
        }
    }
}
