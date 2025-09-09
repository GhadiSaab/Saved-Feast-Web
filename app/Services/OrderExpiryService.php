<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderExpiryService
{
    protected OrderStateService $orderStateService;

    public function __construct(OrderStateService $orderStateService)
    {
        $this->orderStateService = $orderStateService;
    }

    /**
     * Expire overdue orders
     */
    public function expireOverdue(): int
    {
        $graceMinutes = config('sf_orders.pickup_code.grace_minutes_after_window', 10);
        $cutoffTime = now()->subMinutes($graceMinutes);

        $overdueOrders = Order::overdue()
            ->where('pickup_window_end', '<', $cutoffTime)
            ->get();

        $expiredCount = 0;

        foreach ($overdueOrders as $order) {
            try {
                if ($this->orderStateService->expire($order)) {
                    $expiredCount++;

                    Log::info('Order expired due to overdue pickup window', [
                        'order_id' => $order->id,
                        'pickup_window_end' => $order->pickup_window_end,
                        'expired_at' => now(),
                    ]);

                    // Optionally restock meals here
                    $this->restockMeals($order);
                }
            } catch (\Exception $e) {
                Log::error('Failed to expire order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($expiredCount > 0) {
            Log::info('Order expiry job completed', [
                'expired_count' => $expiredCount,
                'total_checked' => $overdueOrders->count(),
            ]);
        }

        return $expiredCount;
    }

    /**
     * Auto-cancel stale pending orders
     */
    public function autoCancelPending(): int
    {
        $timeoutMinutes = config('sf_orders.timeouts.pending_auto_cancel_minutes', 15);
        $cutoffTime = now()->subMinutes($timeoutMinutes);

        $staleOrders = Order::withStatus(Order::STATUS_PENDING)
            ->where('created_at', '<', $cutoffTime)
            ->get();

        $cancelledCount = 0;

        foreach ($staleOrders as $order) {
            try {
                if ($this->orderStateService->cancelByRestaurant($order, $order->user, 'Auto-cancelled due to timeout')) {
                    $cancelledCount++;

                    Log::info('Order auto-cancelled due to timeout', [
                        'order_id' => $order->id,
                        'created_at' => $order->created_at,
                        'cancelled_at' => now(),
                    ]);

                    // Optionally restock meals here
                    $this->restockMeals($order);
                }
            } catch (\Exception $e) {
                Log::error('Failed to auto-cancel order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($cancelledCount > 0) {
            Log::info('Order auto-cancellation job completed', [
                'cancelled_count' => $cancelledCount,
                'total_checked' => $staleOrders->count(),
            ]);
        }

        return $cancelledCount;
    }

    /**
     * Restock meals for cancelled/expired orders
     */
    protected function restockMeals(Order $order): void
    {
        try {
            foreach ($order->orderItems as $item) {
                $meal = $item->meal;
                if ($meal) {
                    $meal->increment('available_quantity', $item->quantity);

                    Log::info('Meal restocked after order cancellation/expiry', [
                        'order_id' => $order->id,
                        'meal_id' => $meal->id,
                        'quantity_restocked' => $item->quantity,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to restock meals for order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get statistics for monitoring
     */
    public function getExpiryStats(): array
    {
        $graceMinutes = config('sf_orders.pickup_code.grace_minutes_after_window', 10);
        $cutoffTime = now()->subMinutes($graceMinutes);

        $timeoutMinutes = config('sf_orders.timeouts.pending_auto_cancel_minutes', 15);
        $timeoutCutoff = now()->subMinutes($timeoutMinutes);

        return [
            'overdue_orders' => Order::overdue()
                ->where('pickup_window_end', '<', $cutoffTime)
                ->count(),
            'stale_pending_orders' => Order::withStatus(Order::STATUS_PENDING)
                ->where('created_at', '<', $timeoutCutoff)
                ->count(),
            'active_orders' => Order::active()->count(),
            'expired_today' => Order::withStatus(Order::STATUS_EXPIRED)
                ->whereDate('expired_at', today())
                ->count(),
        ];
    }
}
