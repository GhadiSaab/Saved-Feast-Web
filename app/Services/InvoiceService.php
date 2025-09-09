<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\RestaurantInvoice;
use App\Models\RestaurantInvoiceItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    protected CommissionService $commissionService;

    public function __construct(CommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }

    /**
     * Generate weekly invoices for all restaurants
     */
    public function generateWeeklyInvoices(Carbon $periodStart, Carbon $periodEnd): array
    {
        $results = [
            'invoices_created' => 0,
            'orders_processed' => 0,
            'errors' => [],
        ];

        try {
            // Get all restaurants and check each one for orders
            $restaurants = Restaurant::all();

            foreach ($restaurants as $restaurant) {
                try {
                    $invoice = $this->generateInvoiceForRestaurant($restaurant, $periodStart, $periodEnd);
                    if ($invoice) {
                        $results['invoices_created']++;
                        $results['orders_processed'] += $invoice->orders_count;
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = "Failed to generate invoice for restaurant {$restaurant->id}: ".$e->getMessage();
                    Log::error("Invoice generation failed for restaurant {$restaurant->id}", [
                        'error' => $e->getMessage(),
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                    ]);
                }
            }

        } catch (\Exception $e) {
            $results['errors'][] = 'Failed to generate invoices: '.$e->getMessage();
            Log::error('Weekly invoice generation failed', [
                'error' => $e->getMessage(),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ]);
        }

        return $results;
    }

    /**
     * Generate invoice for a specific restaurant
     */
    public function generateInvoiceForRestaurant(Restaurant $restaurant, Carbon $periodStart, Carbon $periodEnd): ?RestaurantInvoice
    {
        // Get all completed cash-on-pickup orders for this restaurant in the period
        $orders = Order::whereHas('orderItems.meal', function ($query) use ($restaurant) {
            $query->where('restaurant_id', $restaurant->id);
        })
            ->where('payment_method', 'CASH_ON_PICKUP')
            ->where('orders.status', Order::STATUS_COMPLETED)
            ->where('completed_at', '>=', $periodStart)
            ->where('completed_at', '<=', $periodEnd)
            ->whereNull('invoiced_at')
            ->get();

        if ($orders->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($restaurant, $periodStart, $periodEnd, $orders) {
            // Calculate totals
            $subtotalSales = $orders->sum('total_amount');
            $commissionTotal = $orders->sum('commission_amount');
            $ordersCount = $orders->count();

            // Use the most common commission rate from the orders
            $commissionRate = $orders->groupBy('commission_rate')->sortDesc()->keys()->first();

            // Create the invoice
            $invoice = RestaurantInvoice::create([
                'restaurant_id' => $restaurant->id,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'status' => 'draft',
                'subtotal_sales' => $subtotalSales,
                'commission_rate' => $commissionRate,
                'commission_total' => $commissionTotal,
                'orders_count' => $ordersCount,
                'meta' => [
                    'currency' => 'USD', // You can make this configurable
                    'generated_at' => now()->toISOString(),
                ],
            ]);

            // Create invoice items and mark orders as invoiced
            foreach ($orders as $order) {
                RestaurantInvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'order_id' => $order->id,
                    'order_total' => $order->total_amount,
                    'commission_rate' => $order->commission_rate,
                    'commission_amount' => $order->commission_amount,
                ]);

                // Mark order as invoiced
                $order->update(['invoiced_at' => now()]);
            }

            return $invoice;
        });
    }

    /**
     * Mark invoice as sent
     */
    public function markInvoiceSent(int $invoiceId): bool
    {
        $invoice = RestaurantInvoice::findOrFail($invoiceId);

        if (! in_array($invoice->status, ['draft'])) {
            throw new \Exception('Invoice can only be marked as sent from draft status');
        }

        return $invoice->update(['status' => 'sent']);
    }

    /**
     * Mark invoice as paid
     */
    public function markInvoicePaid(int $invoiceId): bool
    {
        $invoice = RestaurantInvoice::findOrFail($invoiceId);

        if (! in_array($invoice->status, ['sent', 'overdue'])) {
            throw new \Exception('Invoice can only be marked as paid from sent or overdue status');
        }

        return $invoice->update(['status' => 'paid']);
    }

    /**
     * Mark invoice as overdue
     */
    public function markInvoiceOverdue(int $invoiceId): bool
    {
        $invoice = RestaurantInvoice::findOrFail($invoiceId);

        if (! in_array($invoice->status, ['sent'])) {
            throw new \Exception('Invoice can only be marked as overdue from sent status');
        }

        return $invoice->update(['status' => 'overdue']);
    }

    /**
     * Regenerate PDF for an invoice
     */
    public function regeneratePdf(int $invoiceId): ?string
    {
        $invoice = RestaurantInvoice::with(['restaurant', 'items.order'])->findOrFail($invoiceId);

        // This will be implemented when we add PDF generation
        // For now, just return null
        return null;
    }
}
