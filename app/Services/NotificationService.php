<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderEvent;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send pickup code to customer
     */
    public function sendPickupCode(Order $order): bool
    {
        if (! $order->pickup_code_encrypted) {
            return false;
        }

        // Send in-app notification (always enabled)
        $this->sendInAppNotification($order);

        // Log the notification event
        $this->logNotificationEvent($order, true);

        return true;
    }

    /**
     * Send in-app notification
     */
    protected function sendInAppNotification(Order $order): bool
    {
        try {
            // Here you would typically create a notification record
            // or trigger a real-time event via broadcasting
            // For now, we'll just log it

            Log::info('In-app pickup code notification sent', [
                'order_id' => $order->id,
                'customer_id' => $order->user_id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send in-app notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Log notification event
     */
    protected function logNotificationEvent(Order $order, bool $success): void
    {
        try {
            OrderEvent::create([
                'order_id' => $order->id,
                'type' => OrderEvent::TYPE_SMS_SENT,
                'meta' => [
                    'success' => $success,
                    'timestamp' => now()->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log notification event', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
