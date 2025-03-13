<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Create an admin user
        User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@savedfeast.com',
            'password' => Hash::make('password'),
            'phone' => $faker->phoneNumber(),
            'address' => $faker->address(),
        ]);
        
        // Create regular users
        for ($i = 0; $i < 10; $i++) {
            User::create([
                'first_name' => $faker->firstName(),
                'last_name' => $faker->lastName(),
                'email' => $faker->unique()->safeEmail(),
                'password' => Hash::make('password'),
                'phone' => $faker->phoneNumber(),
                'address' => $faker->address(),
            ]);
        }
    }
}

