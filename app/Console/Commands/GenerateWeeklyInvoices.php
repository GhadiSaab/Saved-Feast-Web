<?php

namespace App\Console\Commands;

use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateWeeklyInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate-weekly {--period=previous : The period to generate invoices for (previous, current)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate weekly invoices for all restaurants based on completed cash-on-pickup orders';

    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        parent::__construct();
        $this->invoiceService = $invoiceService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $period = $this->option('period');
        $timezone = config('savedfeast.invoicing.timezone', 'Asia/Beirut');
        
        $this->info("Generating weekly invoices for period: {$period}");
        $this->info("Using timezone: {$timezone}");

        // Calculate the period dates
        $now = Carbon::now($timezone);
        
        if ($period === 'previous') {
            $periodStart = $now->copy()->subWeek()->startOfWeek();
            $periodEnd = $now->copy()->subWeek()->endOfWeek();
        } elseif ($period === 'current') {
            $periodStart = $now->copy()->startOfWeek();
            $periodEnd = $now->copy()->endOfWeek();
        } else {
            $this->error("Invalid period. Use 'previous' or 'current'");
            return 1;
        }

        $this->info("Period: {$periodStart->format('Y-m-d')} to {$periodEnd->format('Y-m-d')}");

        try {
            $results = $this->invoiceService->generateWeeklyInvoices($periodStart, $periodEnd);

            $this->info("Invoice generation completed!");
            $this->info("Invoices created: {$results['invoices_created']}");
            $this->info("Orders processed: {$results['orders_processed']}");

            if (!empty($results['errors'])) {
                $this->warn("Errors encountered:");
                foreach ($results['errors'] as $error) {
                    $this->error("  - {$error}");
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to generate invoices: " . $e->getMessage());
            return 1;
        }
    }
}
