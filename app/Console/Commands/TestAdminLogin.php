<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class TestAdminLogin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:test-login';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test admin login credentials';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = 'admin@savedfeast.com';
        $password = 'admin123';

        try {
            // Find the admin user
            $user = User::where('email', $email)->first();

            if (! $user) {
                $this->error('Admin user not found!');

                return;
            }

            $this->info('Found admin user:');
            $this->info('ID: '.$user->id);
            $this->info('Email: '.$user->email);
            $this->info('Name: '.$user->first_name.' '.$user->last_name);

            // Check roles
            $roles = $user->roles->pluck('name')->toArray();
            $this->info('Roles: '.implode(', ', $roles));

            // Test password
            $this->info("\nTesting password...");
            if (Hash::check($password, $user->password)) {
                $this->info('✅ Password is correct!');
            } else {
                $this->error('❌ Password is incorrect!');

                // Let's try to reset the password
                $this->info('Attempting to reset password...');
                $user->password = Hash::make($password);
                $user->save();
                $this->info('Password has been reset to: '.$password);
            }

            // Test creating a token
            $this->info("\nTesting token creation...");
            try {
                $token = $user->createToken('test_token')->plainTextToken;
                $this->info('✅ Token created successfully!');
                $this->info('Token: '.substr($token, 0, 20).'...');

                // Clean up the test token
                $user->tokens()->where('name', 'test_token')->delete();
                $this->info('Test token cleaned up.');

            } catch (\Exception $e) {
                $this->error('❌ Token creation failed: '.$e->getMessage());
            }

        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());
        }
    }
}
