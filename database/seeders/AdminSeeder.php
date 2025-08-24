<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin role if it doesn't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Create admin user if it doesn't exist
        $adminUser = User::firstOrCreate(
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

        // Assign admin role to user
        $adminUser->roles()->sync([$adminRole->id]);

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@savedfeast.com');
        $this->command->info('Password: admin123');
    }
}
