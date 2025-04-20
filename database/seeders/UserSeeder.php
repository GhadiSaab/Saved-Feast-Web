<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role; // Import Role model
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB; // Import DB facade for role check/creation
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // --- Create Roles ---
        // Check if roles exist before creating to avoid duplicates if seeder runs multiple times
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $providerRole = Role::firstOrCreate(['name' => 'provider']);
        $customerRole = Role::firstOrCreate(['name' => 'customer']);

        // --- Create Admin User ---
        $adminUser = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@savedfeast.com', // Fixed email for easy login
            'password' => Hash::make('password'),
            'phone' => $faker->phoneNumber(),
            'address' => $faker->address(),
        ]);
        // Assign Admin Role
        $adminUser->roles()->attach($adminRole->id);


        // --- Create Provider User ---
        $providerUser = User::create([
            'first_name' => 'Restaurant',
            'last_name' => 'Provider',
            'email' => 'provider@savedfeast.com', // Fixed email for easy login
            'password' => Hash::make('password'),
            'phone' => $faker->phoneNumber(),
            'address' => $faker->address(),
        ]);
        // Assign Provider Role
        $providerUser->roles()->attach($providerRole->id);


        // --- Create Regular Customer Users ---
        for ($i = 0; $i < 10; $i++) {
            $customerUser = User::create([
                'first_name' => $faker->firstName(),
                'last_name' => $faker->lastName(),
                'email' => $faker->unique()->safeEmail(), // Use faker for unique emails
                'password' => Hash::make('password'),
            'phone' => $faker->phoneNumber(),
            'address' => $faker->address(),
            ]);
            // Assign Customer Role
            $customerUser->roles()->attach($customerRole->id);
        }
    }
}
