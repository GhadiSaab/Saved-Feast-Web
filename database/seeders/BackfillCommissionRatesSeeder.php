<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BackfillCommissionRatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultRate = config('savedfeast.commission.default_rate', 7.0);
        
        // Update all restaurants that don't have a commission rate set
        $updated = Restaurant::whereNull('commission_rate')
            ->orWhere('commission_rate', 0)
            ->update(['commission_rate' => $defaultRate]);
            
        $this->command->info("Updated {$updated} restaurants with default commission rate of {$defaultRate}%");
    }
}
