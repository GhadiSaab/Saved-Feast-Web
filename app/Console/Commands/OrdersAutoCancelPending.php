<?php

namespace App\Console\Commands;

use App\Services\OrderExpiryService;
use Illuminate\Console\Command;

class OrdersAutoCancelPending extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:auto-cancel-pending {--dry-run : Show what would be cancelled without actually cancelling}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-cancel stale pending orders that have exceeded timeout';

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
        $this->info('Starting auto-cancellation of stale pending orders...');

        if ($this->option('dry-run')) {
            $this->info('DRY RUN MODE - No orders will be cancelled');
            $stats = $this->orderExpiryService->getExpiryStats();

            $this->table(
                ['Metric', 'Count'],
                [
                    ['Stale Pending Orders', $stats['stale_pending_orders']],
                    ['Active Orders', $stats['active_orders']],
                ]
            );

            return 0;
        }

        $cancelledCount = $this->orderExpiryService->autoCancelPending();

        if ($cancelledCount > 0) {
            $this->info("Successfully auto-cancelled {$cancelledCount} stale pending orders.");
        } else {
            $this->info('No stale pending orders were found.');
        }

        return 0;
    }
}
