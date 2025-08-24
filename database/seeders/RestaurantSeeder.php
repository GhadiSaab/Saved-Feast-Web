<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // --- Create Restaurant for the specific Provider User ---
        $providerUser = User::where('email', 'provider@savedfeast.com')->first();

        if ($providerUser) {
            Restaurant::create([
                'name' => 'The Provider\'s Place', // Specific name for easy identification
                'description' => $faker->paragraph(3),
                'phone' => $faker->phoneNumber(),
                'address' => $faker->address(),
                'email' => $faker->email(),
                'website' => $faker->url(),
                'image' => 'restaurants/restaurant-'.$faker->numberBetween(1, 10).'.jpg', // Keep random image for now
                'user_id' => $providerUser->id, // Assign to the specific provider user
            ]);
            $this->command->info('Created restaurant for provider@savedfeast.com.');
        } else {
            $this->command->warn('Provider user (provider@savedfeast.com) not found. Skipping provider restaurant creation.');
        }

        // --- Create other sample restaurants for random users (optional) ---

        // Get IDs of users who are NOT the specific provider or admin, assuming they are customers
        $customerUserIds = User::whereDoesntHave('roles', function ($query) {
            $query->whereIn('name', ['provider', 'admin']);
        })
            ->pluck('id')->toArray();

        // If no customer users exist, we can't create more restaurants
        if (empty($customerUserIds)) {
            $this->command->info('No customer users found to assign additional restaurants.');

            return;
        }

        // Create 9 more sample restaurants assigned to random customer users
        $numOtherRestaurants = 9;
        for ($i = 0; $i < $numOtherRestaurants; $i++) {
            // Ensure we don't try to pick from an empty array if fewer customers than restaurants
            if (empty($customerUserIds)) {
                break;
            }

            Restaurant::create([
                'name' => $faker->company().' '.$faker->randomElement(['Bistro', 'Café', 'Diner', 'Eatery']),
                'description' => $faker->paragraph(2),
                'phone' => $faker->phoneNumber(),
                'address' => $faker->address(),
                'email' => $faker->unique()->companyEmail(), // Use unique company email
                'website' => $faker->url(),
                'image' => 'restaurants/restaurant-'.$faker->numberBetween(1, 10).'.jpg',
                'user_id' => $faker->randomElement($customerUserIds), // Assign to a random customer user
            ]);
        }
        $this->command->info("Created {$numOtherRestaurants} additional sample restaurants for customer users.");

    }
}
