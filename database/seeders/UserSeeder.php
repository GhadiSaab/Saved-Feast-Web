<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User; // Import Role model
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // Import DB facade for role check/creation
use Illuminate\Support\Facades\Hash;

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
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@savedfeast.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => 'admin@savedfeast.com',
                'password' => Hash::make('admin123'),
                'phone' => '+1234567890',
                'address' => 'Admin Address',
            ]
        );
        // Assign Admin Role
        $adminUser->roles()->sync([$adminRole->id]);

        // --- Create Provider User ---
        $providerUser = User::updateOrCreate(
            ['email' => 'provider@savedfeast.com'],
            [
                'first_name' => 'Restaurant',
                'last_name' => 'Provider',
                'email' => 'provider@savedfeast.com',
                'password' => Hash::make('provider123'),
                'phone' => '+1234567891',
                'address' => 'Provider Address',
            ]
        );
        // Assign Provider Role
        $providerUser->roles()->sync([$providerRole->id]);

        // Output login credentials
        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@savedfeast.com');
        $this->command->info('Password: admin123');
        $this->command->info('');
        $this->command->info('Provider user created successfully!');
        $this->command->info('Email: provider@savedfeast.com');
        $this->command->info('Password: provider123');

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
