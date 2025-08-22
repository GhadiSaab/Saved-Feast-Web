<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;

class AssignDefaultRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:assign-default-roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign default consumer role to users who have no roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $consumerRole = Role::where('name', 'consumer')->first();
        
        if (! $consumerRole) {
            $this->error('Consumer role not found. Please run the roles migration first.');
            return 1;
        }

        $usersWithoutRoles = User::whereDoesntHave('roles')->get();
        
        if ($usersWithoutRoles->isEmpty()) {
            $this->info('All users already have roles assigned.');
            return 0;
        }

        $this->info('Found '.$usersWithoutRoles->count().' users without roles.');

        $bar = $this->output->createProgressBar($usersWithoutRoles->count());
        $bar->start();

        foreach ($usersWithoutRoles as $user) {
            $user->roles()->attach($consumerRole->id);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Successfully assigned consumer role to all users without roles.');

        return 0;
    }
}