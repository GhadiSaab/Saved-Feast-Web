<?php

namespace App\Console\Commands;

use App\Services\OrderExpiryService;
use Illuminate\Console\Command;

class OrdersExpireOverdue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:expire-overdue {--dry-run : Show what would be expired without actually expiring}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire orders that have exceeded their pickup window';

    protected OrderExpiryService $orderExpiryService;

    public function __construct(OrderExpiryService $orderExpiryService)
    {
        parent::__construct();
        $this->orderExpiryService = $orderExpiryService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting order expiry process...');

        if ($this->option('dry-run')) {
            $this->info('DRY RUN MODE - No orders will be expired');
            $stats = $this->orderExpiryService->getExpiryStats();

            $this->table(
                ['Metric', 'Count'],
                [
                    ['Overdue Orders', $stats['overdue_orders']],
                    ['Stale Pending Orders', $stats['stale_pending_orders']],
                    ['Active Orders', $stats['active_orders']],
                    ['Expired Today', $stats['expired_today']],
                ]
            );

            return 0;
        }

        $expiredCount = $this->orderExpiryService->expireOverdue();

        if ($expiredCount > 0) {
            $this->info("Successfully expired {$expiredCount} orders.");
        } else {
            $this->info('No orders were expired.');
        }

        return 0;
    }
}
