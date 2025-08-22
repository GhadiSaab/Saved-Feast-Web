<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create {email?} {password?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?: 'admin@savedfeast.com';
        $password = $this->argument('password') ?: 'admin123';

        try {
            // Create admin role if it doesn't exist
            $adminRole = Role::firstOrCreate(['name' => 'admin']);
            $this->info('Admin role created/found with ID: '.$adminRole->id);

            // Check if user already exists
            $existingUser = User::where('email', $email)->first();
            if ($existingUser) {
                $this->warn('User with email '.$email.' already exists!');
                $this->info('User ID: '.$existingUser->id);
                $this->info('User name: '.$existingUser->first_name.' '.$existingUser->last_name);
                
                // Check if user has admin role
                if ($existingUser->hasRole('admin')) {
                    $this->info("User already has admin role!");
                } else {
                    $this->info("Assigning admin role to existing user...");
                    $existingUser->roles()->sync([$adminRole->id]);
                    $this->info("Admin role assigned successfully!");
                }
                return;
            }

            // Create admin user
            $adminUser = User::create([
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => $email,
                'password' => Hash::make($password),
                'phone' => '+1234567890',
                'address' => 'Admin Address'
            ]);

            $this->info("Admin user created successfully!");
            $this->info('User ID: '.$adminUser->id);
            $this->info('Email: '.$adminUser->email);
            $this->info('Name: '.$adminUser->first_name.' '.$adminUser->last_name);

            // Assign admin role
            $adminUser->roles()->attach($adminRole->id);
            $this->info("Admin role assigned successfully!");

            $this->info("\nLogin credentials:");
            $this->info('Email: '.$email);
            $this->info('Password: '.$password);

        } catch (\Exception $e) {
            $this->error('Error creating admin user: '.$e->getMessage());
            $this->error('Stack trace: '.$e->getTraceAsString());
        }
    }
}
