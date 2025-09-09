<?php

namespace App\Http\Controllers\API\Provider;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderStateService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    protected OrderStateService $orderStateService;

    public function __construct(OrderStateService $orderStateService)
    {
        $this->orderStateService = $orderStateService;
    }

    /**
     * Get orders for the provider's restaurant
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('provider-access');

        $user = auth()->user();
        $restaurantIds = $user->restaurants()->pluck('id')->toArray();

        if (empty($restaurantIds)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'data' => [],
                    'total' => 0,
                ],
            ]);
        }

        $query = Order::with(['user', 'orderItems.meal'])
            ->whereHas('orderItems.meal', function ($q) use ($restaurantIds) {
                $q->whereIn('restaurant_id', $restaurantIds);
            });

        // Filter by status
        if ($request->has('status')) {
            $status = $request->get('status');
            if (is_array($status)) {
                $query->whereIn('status', $status);
            } else {
                $query->where('status', $status);
            }
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Get order details
     */
    public function show(Order $order): JsonResponse
    {
        Gate::authorize('provider-access');

        // Ensure the order belongs to the provider's restaurant
        $this->authorizeOrderAccess($order);

        $order->load(['user', 'orderItems.meal', 'events']);

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * Accept an order
     */
    public function accept(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('provider-access');
        $this->authorizeOrderAccess($order);

        $request->validate([
            'pickup_window_start' => 'nullable|date|after:now',
            'pickup_window_end' => 'nullable|date|after:pickup_window_start',
        ]);

        try {
            $pickupStart = $request->pickup_window_start ? Carbon::parse($request->pickup_window_start) : null;
            $pickupEnd = $request->pickup_window_end ? Carbon::parse($request->pickup_window_end) : null;

            $this->orderStateService->accept($order, auth()->user(), $pickupStart, $pickupEnd);

            $order->refresh();
            $order->load(['user', 'orderItems.meal', 'events']);

            return response()->json([
                'success' => true,
                'message' => 'Order accepted successfully',
                'data' => $order,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mark order as ready for pickup
     */
    public function markReady(Order $order): JsonResponse
    {
        Gate::authorize('provider-access');
        $this->authorizeOrderAccess($order);

        try {
            $this->orderStateService->markReady($order, auth()->user());

            $order->refresh();
            $order->load(['user', 'orderItems.meal', 'events']);

            return response()->json([
                'success' => true,
                'message' => 'Order marked as ready for pickup',
                'data' => $order,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Complete order with claim code
     */
    public function complete(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('provider-access');
        $this->authorizeOrderAccess($order);

        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        try {
            // First try to validate as claim code (new system)
            $claimCodeValid = $this->validateClaimCode($order, $request->code);

            if ($claimCodeValid) {
                // Complete order using claim code
                $this->orderStateService->completeWithCode($order, $request->code, auth()->user());

                $order->refresh();
                $order->load(['user', 'orderItems.meal', 'events']);

                return response()->json([
                    'success' => true,
                    'message' => 'Order completed successfully with claim code',
                    'data' => $order,
                ]);
            }

            // Fallback to old pickup code system for backward compatibility
            $success = $this->orderStateService->completeWithCode($order, $request->code, auth()->user());

            if (! $success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid claim code or pickup code',
                    'attempts_remaining' => config('sf_orders.pickup_code.max_attempts', 5) - $order->pickup_code_attempts,
                ], 400);
            }

            $order->refresh();
            $order->load(['user', 'orderItems.meal', 'events']);

            return response()->json([
                'success' => true,
                'message' => 'Order completed successfully',
                'data' => $order,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Validate claim code for order
     */
    private function validateClaimCode(Order $order, string $code): bool
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
     * Cancel order
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('provider-access');
        $this->authorizeOrderAccess($order);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->orderStateService->cancelByRestaurant($order, auth()->user(), $request->reason);

            $order->refresh();
            $order->load(['user', 'orderItems.meal', 'events']);

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'data' => $order,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get order statistics for dashboard
     */
    public function stats(): JsonResponse
    {
        Gate::authorize('provider-access');

        $user = auth()->user();
        $restaurantIds = $user->restaurants()->pluck('id')->toArray();

        if (empty($restaurantIds)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'pending' => 0,
                    'accepted' => 0,
                    'ready' => 0,
                    'completed_today' => 0,
                ],
            ]);
        }

        $stats = [
            'pending' => Order::whereHas('orderItems.meal', function ($q) use ($restaurantIds) {
                $q->whereIn('restaurant_id', $restaurantIds);
            })->where('status', Order::STATUS_PENDING)->count(),

            'accepted' => Order::whereHas('orderItems.meal', function ($q) use ($restaurantIds) {
                $q->whereIn('restaurant_id', $restaurantIds);
            })->where('status', Order::STATUS_ACCEPTED)->count(),

            'ready' => Order::whereHas('orderItems.meal', function ($q) use ($restaurantIds) {
                $q->whereIn('restaurant_id', $restaurantIds);
            })->where('status', Order::STATUS_READY_FOR_PICKUP)->count(),

            'completed_today' => Order::whereHas('orderItems.meal', function ($q) use ($restaurantIds) {
                $q->whereIn('restaurant_id', $restaurantIds);
            })->where('status', Order::STATUS_COMPLETED)
                ->whereDate('completed_at', today())->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Authorize that the order belongs to the provider's restaurant
     */
    protected function authorizeOrderAccess(Order $order): void
    {
        $user = auth()->user();
        $restaurantIds = $user->restaurants()->pluck('id')->toArray();

        if (empty($restaurantIds)) {
            abort(403, 'You do not have access to any restaurants');
        }

        $hasAccess = $order->orderItems()->whereHas('meal', function ($q) use ($restaurantIds) {
            $q->whereIn('restaurant_id', $restaurantIds);
        })->exists();

        if (! $hasAccess) {
            abort(403, 'You do not have access to this order');
        }
    }
}
