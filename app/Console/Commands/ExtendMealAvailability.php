<?php

namespace App\Console\Commands;

use App\Models\Meal;
use Illuminate\Console\Command;

class ExtendMealAvailability extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meals:extend-availability {--days=7 : Number of days to extend availability}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extend availability for expired meals to make them available again';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $now = now();

        $this->info("Extending meal availability by {$days} days...");

        // Find meals that are expired due to availability_until being in the past
        $expiredMeals = Meal::where('available_until', '<', $now)
            ->where('quantity', '>', 0)
            ->where('status', '!=', 'sold_out')
            ->get();

        $this->info("Found {$expiredMeals->count()} expired meals to extend.");

        if ($expiredMeals->count() === 0) {
            $this->info('No expired meals found to extend.');

            return;
        }

        $extended = 0;
        foreach ($expiredMeals as $meal) {
            // Extend availability_until by the specified number of days
            $meal->available_until = $now->copy()->addDays($days);

            // If available_from is in the past, set it to now
            if ($meal->available_from && $meal->available_from < $now) {
                $meal->available_from = $now;
            }

            // Update status to available if it was expired
            if ($meal->status === 'expired') {
                $meal->status = 'available';
            }

            $meal->save();
            $extended++;

            $this->line("Extended availability for: {$meal->title} (ID: {$meal->id})");
        }

        $this->info("Successfully extended availability for {$extended} meals.");

        // Also handle meals with null availability dates
        $nullAvailabilityMeals = Meal::whereNull('available_from')
            ->orWhereNull('available_until')
            ->where('quantity', '>', 0)
            ->where('status', '!=', 'sold_out')
            ->get();

        if ($nullAvailabilityMeals->count() > 0) {
            $this->info("Found {$nullAvailabilityMeals->count()} meals with null availability dates.");

            foreach ($nullAvailabilityMeals as $meal) {
                if (is_null($meal->available_from)) {
                    $meal->available_from = $now;
                }
                if (is_null($meal->available_until)) {
                    $meal->available_until = $now->copy()->addDays($days);
                }
                if ($meal->status === 'expired') {
                    $meal->status = 'available';
                }
                $meal->save();
                $this->line("Set availability dates for: {$meal->title} (ID: {$meal->id})");
            }
        }

        // Show summary
        $availableMeals = Meal::where('quantity', '>', 0)
            ->where(function ($q) use ($now) {
                $q->whereNull('available_from')->orWhere('available_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('available_until')->orWhere('available_until', '>=', $now);
            })
            ->where('status', '!=', 'expired')
            ->count();

        $this->info("Total available meals after extension: {$availableMeals}");
    }
}
