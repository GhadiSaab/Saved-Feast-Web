<?php

namespace App\Http\Controllers\API; // Corrected namespace

use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Models\Order;
use App\Services\CommissionService;
use App\Services\OrderStateService;
use App\Services\PickupCodeService;
use Illuminate\Http\Request; // Import Meal model
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log; // Import Log facade

class OrderController extends Controller
{
    protected CommissionService $commissionService;

    protected OrderStateService $orderStateService;

    protected PickupCodeService $pickupCodeService;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        CommissionService $commissionService,
        OrderStateService $orderStateService,
        PickupCodeService $pickupCodeService
    ) {
        $this->middleware('auth:sanctum');
        $this->authorizeResource(Order::class, 'order');
        $this->commissionService = $commissionService;
        $this->orderStateService = $orderStateService;
        $this->pickupCodeService = $pickupCodeService;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        // Check if user can view orders
        $this->authorize('viewAny', Order::class);

        // Fetch orders based on user role
        if ($user->isProvider()) {
            // Providers see orders for their restaurants through order items and meals
            $restaurantIds = $user->restaurants()->pluck('id');
            $orders = Order::whereHas('orderItems.meal', function ($query) use ($restaurantIds) {
                $query->whereIn('restaurant_id', $restaurantIds);
            })->with('orderItems.meal')
                ->get();
        } else {
            // Consumers see their own orders
            $orders = Order::where('user_id', $user->id)
                ->with('orderItems.meal')
                ->get();
        }

        return response()->json([
            'status' => true,
            'message' => 'Orders retrieved successfully',
            'data' => $orders,
        ], 200);
    }

    public function show(Order $order, Request $request)
    {
        $user = $request->user();

        // Check if user can view this specific order
        $this->authorize('view', $order);

        // Load relationships
        $order->load('orderItems.meal');

        return response()->json([
            'status' => true,
            'message' => 'Order retrieved successfully',
            'data' => $order,
        ], 200);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // Check if user can create orders
        $this->authorize('create', Order::class);

        // Validate the request data
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.meal_id' => 'required|exists:meals,id',
            'items.*.quantity' => 'required|integer|min:1',
            'pickup_time' => 'nullable|date|after:now',
            'notes' => 'nullable|string|max:500',
            'payment_method' => 'nullable|in:CASH_ON_PICKUP,ONLINE',
        ]);

        // Start database transaction
        \DB::beginTransaction();

        try {
            $totalAmount = 0;
            $orderItems = [];
            $restaurantNotifications = [];

            // Validate meal availability and calculate total
            foreach ($request->items as $itemData) {
                $meal = Meal::with('restaurant')->findOrFail($itemData['meal_id']);

                // Check if meal is available
                if (! $meal->quantity || $meal->quantity < $itemData['quantity']) {
                    throw new \Exception("Insufficient quantity available for meal: {$meal->title}");
                }

                // Check if meal is within pickup time window
                $now = now();
                if ($meal->available_until && $now->gt($meal->available_until)) {
                    throw new \Exception("Meal {$meal->title} is no longer available for pickup");
                }

                $itemTotal = $meal->current_price * $itemData['quantity'];
                $totalAmount += $itemTotal;

                $orderItems[] = [
                    'meal_id' => $meal->id,
                    'quantity' => $itemData['quantity'],
                    'price' => $meal->current_price,
                    'original_price' => $meal->original_price ?? $meal->current_price,
                ];

                // Track restaurant for notifications
                if ($meal->restaurant && ! isset($restaurantNotifications[$meal->restaurant->id])) {
                    $restaurantNotifications[$meal->restaurant->id] = [
                        'restaurant' => $meal->restaurant,
                        'meals' => [],
                    ];
                }
                if ($meal->restaurant) {
                    $restaurantNotifications[$meal->restaurant->id]['meals'][] = [
                        'meal' => $meal,
                        'quantity' => $itemData['quantity'],
                    ];
                }
            }

            // Determine payment method (default to CASH_ON_PICKUP)
            $paymentMethod = $request->payment_method ?? 'CASH_ON_PICKUP';

            // Get the primary restaurant for commission calculation
            // For now, we'll use the first restaurant from the order items
            $primaryRestaurant = null;
            if (! empty($restaurantNotifications)) {
                $primaryRestaurant = reset($restaurantNotifications)['restaurant'];
            }

            // Calculate commission
            $commission = $this->commissionService->calculateOrderCommission($totalAmount, $primaryRestaurant);

            // Create the order
            $order = Order::create([
                'user_id' => $user->id,
                'total_amount' => $totalAmount,
                'status' => Order::STATUS_PENDING,
                'pickup_time' => $request->pickup_time ?? now()->addMinutes(30),
                'notes' => $request->notes,
                'payment_method' => $paymentMethod,
                'commission_rate' => $commission['rate'],
                'commission_amount' => $commission['amount'],
            ]);

            // Log the initial order creation event
            \App\Models\OrderEvent::create([
                'order_id' => $order->id,
                'type' => \App\Models\OrderEvent::TYPE_STATUS_CHANGED,
                'meta' => [
                    'from' => null,
                    'to' => Order::STATUS_PENDING,
                    'created_by' => $user->id,
                ],
            ]);

            // Create order items and update meal quantities
            foreach ($orderItems as $itemData) {
                $order->orderItems()->create($itemData);

                // Decrease meal quantity
                $meal = Meal::find($itemData['meal_id']);
                $meal->decrement('quantity', $itemData['quantity']);

                // If meal is now out of stock, log it
                if ($meal->fresh()->quantity <= 0) {
                    Log::info("Meal {$meal->title} is now out of stock after order {$order->id}");
                }
            }

            // Send notifications to restaurants
            foreach ($restaurantNotifications as $restaurantData) {
                $this->notifyRestaurant($order, $restaurantData);
            }

            // Commit transaction
            \DB::commit();

            // Reload the order with items to return the complete data
            $order->load('orderItems.meal.restaurant');

            return response()->json([
                'status' => true,
                'message' => 'Order created successfully',
                'data' => $order,
            ], 201);

        } catch (\Exception $e) {
            // Rollback transaction on error
            \DB::rollBack();

            Log::error('Order creation failed: '.$e->getMessage(), [
                'user_id' => $user->id,
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    private function notifyRestaurant($order, $restaurantData)
    {
        try {
            $restaurant = $restaurantData['restaurant'];
            $meals = $restaurantData['meals'];

            // Log the notification (in a real app, you'd send push notifications, emails, etc.)
            Log::info("New order notification for restaurant: {$restaurant->name}", [
                'order_id' => $order->id,
                'restaurant_id' => $restaurant->id,
                'meals' => $meals,
                'total_amount' => $order->total_amount,
                'pickup_time' => $order->pickup_time,
            ]);

            // Here you could implement:
            // - Push notifications to restaurant staff
            // - Email notifications
            // - SMS notifications
            // - Webhook calls to restaurant systems

        } catch (\Exception $e) {
            Log::error('Failed to notify restaurant: '.$e->getMessage());
        }
    }

    public function update(Request $request, Order $order)
    {
        $user = $request->user();

        // Check if user can update this order
        $this->authorize('update', $order);

        // Validate the request data
        $request->validate([
            'status' => 'required|in:PENDING,ACCEPTED,READY_FOR_PICKUP,COMPLETED,CANCELLED_BY_CUSTOMER,CANCELLED_BY_RESTAURANT,EXPIRED',
        ]);

        // Update the order status
        $order->update(['status' => $request->status]);

        return response()->json([
            'status' => true,
            'message' => 'Order updated successfully',
            'data' => $order,
        ], 200);
    }

    public function destroy($id, Request $request)
    {
        $user = $request->user();

        // Delete the order
        $order = Order::where('user_id', $user->id)->findOrFail($id);
        $order->delete();

        return response()->json([
            'status' => true,
            'message' => 'Order deleted successfully',
        ], 200);
    }

    public function cancel(Order $order, Request $request)
    {
        $user = $request->user();

        // Check if user can cancel this order
        if ($order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Check if order can be cancelled
        if (! in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_ACCEPTED])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel order in current status',
            ], 422);
        }

        try {
            $this->orderStateService->cancelByCustomer($order, $user, $request->reason);

            $order->refresh();
            $order->load(['orderItems.meal.restaurant', 'events']);

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'data' => $order,
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function complete($id, Request $request)
    {
        $user = $request->user();

        // Complete the order
        $order = Order::where('user_id', $user->id)->findOrFail($id);
        $order->update([
            'status' => Order::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Order completed successfully',
            'data' => $order,
        ], 200);
    }

    /**
     * Get customer's orders with filtering
     */
    public function getMyOrders(Request $request)
    {
        $user = $request->user();

        $query = Order::where('user_id', $user->id)
            ->with(['orderItems.meal.restaurant', 'events']);

        // Filter by status
        if ($request->has('status')) {
            $statusFilter = $request->input('status');

            $normalizedStatuses = collect(Arr::wrap($statusFilter))
                ->flatMap(function ($status) {
                    if (! is_string($status)) {
                        return [];
                    }

                    $status = strtoupper($status);

                    return match ($status) {
                        'IN_PROGRESS' => [
                            Order::STATUS_PENDING,
                            Order::STATUS_ACCEPTED,
                            Order::STATUS_READY_FOR_PICKUP,
                        ],
                        'COMPLETED' => [Order::STATUS_COMPLETED],
                        'CANCELLED' => [
                            Order::STATUS_CANCELLED_BY_CUSTOMER,
                            Order::STATUS_CANCELLED_BY_RESTAURANT,
                            Order::STATUS_EXPIRED,
                        ],
                        default => [$status],
                    };
                })
                ->unique()
                ->values()
                ->all();

            if (! empty($normalizedStatuses)) {
                if (count($normalizedStatuses) === 1) {
                    $query->where('status', $normalizedStatuses[0]);
                } else {
                    $query->whereIn('status', $normalizedStatuses);
                }
            }
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Get order details with pickup code if applicable
     */
    public function getOrder(Order $order)
    {
        $user = auth()->user();

        // Ensure user owns this order
        if ($order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to order',
            ], 403);
        }

        $order->load(['orderItems.meal.restaurant', 'events']);

        // Add pickup code information if order is accepted or ready
        if (in_array($order->status, [Order::STATUS_ACCEPTED, Order::STATUS_READY_FOR_PICKUP])) {
            $order->pickup_code_masked = $order->getMaskedPickupCode();
            $order->can_show_code = true;
        }

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * Cancel order by customer
     */
    public function cancelMyOrder(Request $request, Order $order)
    {
        $user = auth()->user();

        // Ensure user owns this order
        if ($order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to order',
            ], 403);
        }

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->orderStateService->cancelByCustomer($order, $user, $request->reason);

            $order->refresh();
            $order->load(['orderItems.meal.restaurant', 'events']);

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
     * Resend pickup code
     */
    public function resendCode(Order $order)
    {
        $user = auth()->user();

        // Ensure user owns this order
        if ($order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to order',
            ], 403);
        }

        // Check if order is in a state where code can be resent
        if (! in_array($order->status, [Order::STATUS_ACCEPTED, Order::STATUS_READY_FOR_PICKUP])) {
            return response()->json([
                'success' => false,
                'message' => 'Pickup code cannot be resent for this order status',
            ], 400);
        }

        // Check cooldown period
        $cooldownSeconds = config('sf_orders.pickup_code.resend_cooldown_seconds', 90);
        if ($order->pickup_code_last_sent_at &&
            $order->pickup_code_last_sent_at->addSeconds($cooldownSeconds)->isFuture()) {
            $remainingSeconds = $order->pickup_code_last_sent_at->addSeconds($cooldownSeconds)->diffInSeconds(now());

            return response()->json([
                'success' => false,
                'message' => "Please wait {$remainingSeconds} seconds before requesting another code",
            ], 429);
        }

        try {
            // Update last sent timestamp
            $order->update(['pickup_code_last_sent_at' => now()]);

            // Send notification (this will log the event)
            app(\App\Services\NotificationService::class)->sendPickupCode($order);

            return response()->json([
                'success' => true,
                'message' => 'Pickup code resent successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend pickup code',
            ], 500);
        }
    }

    /**
     * Get unmasked pickup code
     */
    public function showPickupCode(Order $order)
    {
        $user = auth()->user();

        // Ensure user owns this order
        if ($order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to order',
            ], 403);
        }

        // Check if order is in a state where code can be shown
        if (! in_array($order->status, [Order::STATUS_ACCEPTED, Order::STATUS_READY_FOR_PICKUP])) {
            return response()->json([
                'success' => false,
                'message' => 'Pickup code is not available for this order status',
            ], 400);
        }

        if (! $order->pickup_code_encrypted) {
            return response()->json([
                'success' => false,
                'message' => 'Pickup code not found',
            ], 404);
        }

        try {
            $code = $this->pickupCodeService->decrypt($order->pickup_code_encrypted);

            return response()->json([
                'success' => true,
                'data' => [
                    'code' => $code,
                    'masked_code' => $this->pickupCodeService->mask($code),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pickup code',
            ], 500);
        }
    }

    /**
     * Generate a claim code for customer to pick up their order
     */
    public function claimOrder(Request $request, Order $order)
    {
        $user = auth()->user();

        // Ensure user owns this order
        if ($order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to order',
            ], 403);
        }

        // Validate that order is ready for pickup
        if ($order->status !== Order::STATUS_READY_FOR_PICKUP) {
            return response()->json([
                'success' => false,
                'message' => 'Order must be ready for pickup to generate claim code',
            ], 400);
        }

        // Check if pickup window is still valid
        if ($order->pickup_window_end && now()->gt($order->pickup_window_end)) {
            return response()->json([
                'success' => false,
                'message' => 'Pickup window has expired',
            ], 400);
        }

        try {
            // Generate claim code (6-digit code valid for 5 minutes)
            $claimCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = now()->addMinutes(5);

            // Store claim code in order events for tracking
            \App\Models\OrderEvent::create([
                'order_id' => $order->id,
                'type' => 'claim_code_generated',
                'meta' => [
                    'code' => $claimCode,
                    'expires_at' => $expiresAt->toISOString(),
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Claim code generated successfully',
                'data' => [
                    'code' => $claimCode,
                    'expires_at' => $expiresAt->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate claim code: '.$e->getMessage(), [
                'order_id' => $order->id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate claim code',
            ], 500);
        }
    }
}
