<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderStateService
{
    protected PickupCodeService $pickupCodeService;

    protected NotificationService $notificationService;

    public function __construct(
        PickupCodeService $pickupCodeService,
        NotificationService $notificationService
    ) {
        $this->pickupCodeService = $pickupCodeService;
        $this->notificationService = $notificationService;
    }

    /**
     * Accept an order and generate pickup code
     */
    public function accept(Order $order, User $provider, ?Carbon $pickupStart = null, ?Carbon $pickupEnd = null): bool
    {
        if ($order->status !== Order::STATUS_PENDING) {
            throw new \InvalidArgumentException('Order must be in PENDING status to be accepted');
        }

        return DB::transaction(function () use ($order, $provider, $pickupStart, $pickupEnd) {
            // Generate pickup code
            $code = $this->pickupCodeService->generate();
            $encryptedCode = $this->pickupCodeService->encrypt($code);

            // Update order
            $order->update([
                'status' => Order::STATUS_ACCEPTED,
                'accepted_at' => now(),
                'pickup_window_start' => $pickupStart,
                'pickup_window_end' => $pickupEnd,
                'pickup_code_encrypted' => $encryptedCode,
                'pickup_code_last_sent_at' => now(),
            ]);

            // Log events
            $this->logEvent($order, OrderEvent::TYPE_STATUS_CHANGED, [
                'from' => Order::STATUS_PENDING,
                'to' => Order::STATUS_ACCEPTED,
                'provider_id' => $provider->id,
            ]);

            $this->logEvent($order, OrderEvent::TYPE_CODE_GENERATED, [
                'provider_id' => $provider->id,
            ]);

            // Send notifications
            $this->notificationService->sendPickupCode($order);

            return true;
        });
    }

    /**
     * Mark order as ready for pickup
     */
    public function markReady(Order $order, User $provider): bool
    {
        return DB::transaction(function () use ($order, $provider) {
            // Lock the order row for update to prevent race conditions
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();

            if (! $lockedOrder || $lockedOrder->status !== Order::STATUS_ACCEPTED) {
                throw new \InvalidArgumentException('Order must be in ACCEPTED status to be marked ready');
            }

            $lockedOrder->update([
                'status' => Order::STATUS_READY_FOR_PICKUP,
                'ready_at' => now(),
            ]);

            // Log event
            $this->logEvent($lockedOrder, OrderEvent::TYPE_STATUS_CHANGED, [
                'from' => Order::STATUS_ACCEPTED,
                'to' => Order::STATUS_READY_FOR_PICKUP,
                'provider_id' => $provider->id,
            ]);

            // Send notifications
            $this->notificationService->sendPickupCode($lockedOrder);

            return true;
        });
    }

    /**
     * Complete order with pickup code or claim code verification
     */
    public function completeWithCode(Order $order, string $code, User $provider): bool
    {
        if ($order->status !== Order::STATUS_READY_FOR_PICKUP) {
            throw new \InvalidArgumentException('Order must be in READY_FOR_PICKUP status to be completed');
        }

        // Check if this is a claim code (new system) or pickup code (old system)
        $isClaimCode = $this->isClaimCode($order, $code);

        if ($isClaimCode) {
            // For claim codes, the validation was already done in the controller
            // Just complete the order without additional verification
            return DB::transaction(function () use ($order, $provider) {
                // Complete the order
                $order->update([
                    'status' => Order::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);

                // Log successful verification and completion
                $this->logEvent($order, OrderEvent::TYPE_CODE_VERIFIED, [
                    'provider_id' => $provider->id,
                    'code_type' => 'claim_code',
                ]);

                $this->logEvent($order, OrderEvent::TYPE_STATUS_CHANGED, [
                    'from' => Order::STATUS_READY_FOR_PICKUP,
                    'to' => Order::STATUS_COMPLETED,
                    'provider_id' => $provider->id,
                ]);

                return true;
            });
        }

        // Legacy pickup code system
        if (! $order->pickup_code_encrypted) {
            throw new \InvalidArgumentException('Order does not have a pickup code');
        }

        $maxAttempts = config('sf_orders.pickup_code.max_attempts', 5);
        if ($order->pickup_code_attempts >= $maxAttempts) {
            throw new \InvalidArgumentException('Maximum pickup code attempts exceeded');
        }

        return DB::transaction(function () use ($order, $code, $provider) {
            // Increment attempts
            $order->increment('pickup_code_attempts');

            // Log attempt
            $this->logEvent($order, OrderEvent::TYPE_CODE_ATTEMPT, [
                'provider_id' => $provider->id,
                'attempt_number' => $order->pickup_code_attempts,
            ]);

            // Verify code
            if (! $this->pickupCodeService->verify($order->pickup_code_encrypted, $code)) {
                return false;
            }

            // Complete the order
            $order->update([
                'status' => Order::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            // Log successful verification and completion
            $this->logEvent($order, OrderEvent::TYPE_CODE_VERIFIED, [
                'provider_id' => $provider->id,
                'attempt_number' => $order->pickup_code_attempts,
                'code_type' => 'pickup_code',
            ]);

            $this->logEvent($order, OrderEvent::TYPE_STATUS_CHANGED, [
                'from' => Order::STATUS_READY_FOR_PICKUP,
                'to' => Order::STATUS_COMPLETED,
                'provider_id' => $provider->id,
            ]);

            return true;
        });
    }

    /**
     * Check if the provided code is a valid claim code for this order
     */
    private function isClaimCode(Order $order, string $code): bool
    {
        // Find the most recent claim code event for this order
        $claimEvent = \App\Models\OrderEvent::where('order_id', $order->id)
            ->where('type', 'claim_code_generated')
            ->where('meta->code', $code)
            ->latest()
            ->first();

        if (! $claimEvent) {
            return false;
        }

        // Check if code has expired (5 minutes)
        $expiresAt = $claimEvent->meta['expires_at'] ?? null;
        if ($expiresAt && now()->gt($expiresAt)) {
            return false;
        }

        // Mark claim code as used
        $claimEvent->update([
            'type' => 'claim_code_used',
            'meta' => array_merge($claimEvent->meta ?? [], [
                'used_at' => now()->toISOString(),
            ]),
        ]);

        return true;
    }

    /**
     * Cancel order by customer
     */
    public function cancelByCustomer(Order $order, User $customer, ?string $reason = null): bool
    {
        if (! $order->canBeCancelledByCustomer()) {
            throw new \InvalidArgumentException('Order cannot be cancelled by customer in current status');
        }

        return DB::transaction(function () use ($order, $customer, $reason) {
            $order->update([
                'status' => Order::STATUS_CANCELLED_BY_CUSTOMER,
                'cancelled_at' => now(),
                'cancelled_by' => Order::CANCELLED_BY_CUSTOMER,
                'cancel_reason' => $reason,
            ]);

            // Log event
            $this->logEvent($order, OrderEvent::TYPE_CANCELLED, [
                'from' => $order->getOriginal('status'),
                'to' => Order::STATUS_CANCELLED_BY_CUSTOMER,
                'cancelled_by' => Order::CANCELLED_BY_CUSTOMER,
                'customer_id' => $customer->id,
                'reason' => $reason,
            ]);

            return true;
        });
    }

    /**
     * Cancel order by restaurant
     */
    public function cancelByRestaurant(Order $order, User $provider, string $reason): bool
    {
        return DB::transaction(function () use ($order, $provider, $reason) {
            // Lock the order row for update to prevent race conditions
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();

            if (! $lockedOrder || ! $lockedOrder->canBeCancelledByRestaurant()) {
                throw new \InvalidArgumentException('Order cannot be cancelled by restaurant in current status');
            }

            $lockedOrder->update([
                'status' => Order::STATUS_CANCELLED_BY_RESTAURANT,
                'cancelled_at' => now(),
                'cancelled_by' => Order::CANCELLED_BY_RESTAURANT,
                'cancel_reason' => $reason,
            ]);

            // Log event
            $this->logEvent($lockedOrder, OrderEvent::TYPE_CANCELLED, [
                'from' => $lockedOrder->getOriginal('status'),
                'to' => Order::STATUS_CANCELLED_BY_RESTAURANT,
                'cancelled_by' => Order::CANCELLED_BY_RESTAURANT,
                'provider_id' => $provider->id,
                'reason' => $reason,
            ]);

            return true;
        });
    }

    /**
     * Expire an order (system action)
     */
    public function expire(Order $order): bool
    {
        if (!in_array($order->status, [Order::STATUS_ACCEPTED, Order::STATUS_READY_FOR_PICKUP])) {
            return false;
        }

        return DB::transaction(function () use ($order) {
            $order->update([
                'status' => Order::STATUS_EXPIRED,
                'expired_at' => now(),
                'cancelled_by' => Order::CANCELLED_BY_SYSTEM,
                'cancel_reason' => 'Order expired after pickup window',
            ]);

            // Log event
            $this->logEvent($order, OrderEvent::TYPE_EXPIRED, [
                'from' => $order->getOriginal('status'),
                'to' => Order::STATUS_EXPIRED,
                'pickup_window_end' => $order->pickup_window_end?->toISOString(),
            ]);

            return true;
        });
    }

    /**
     * Log an order event
     */
    protected function logEvent(Order $order, string $type, array $meta = []): void
    {
        try {
            OrderEvent::create([
                'order_id' => $order->id,
                'type' => $type,
                'meta' => $meta,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log order event', [
                'order_id' => $order->id,
                'type' => $type,
                'meta' => $meta,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

