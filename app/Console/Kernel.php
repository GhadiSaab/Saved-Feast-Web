<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Generate weekly invoices every Monday at 00:05
        $schedule->command('invoices:generate-weekly --period=previous')
            ->weeklyOn(1, '00:05')
            ->timezone(config('savedfeast.invoicing.timezone', 'Asia/Beirut'))
            ->withoutOverlapping()
            ->runInBackground();

        // Expire overdue orders every 5 minutes
        $schedule->command('orders:expire-overdue')
            ->everyFiveMinutes()
            ->timezone(config('sf_orders.timezone', 'Asia/Beirut'))
            ->withoutOverlapping()
            ->runInBackground();

        // Auto-cancel stale pending orders every 10 minutes
        $schedule->command('orders:auto-cancel-pending')
            ->everyTenMinutes()
            ->timezone(config('sf_orders.timezone', 'Asia/Beirut'))
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
