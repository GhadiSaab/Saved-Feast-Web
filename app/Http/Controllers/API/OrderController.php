<?php

namespace App\Http\Controllers\API; // Corrected namespace

use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Models\Order;
use Illuminate\Http\Request; // Import Meal model
use Illuminate\Support\Facades\Log; // Import Log facade

class OrderController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->authorizeResource(Order::class, 'order');
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

            // Create the order
            $order = Order::create([
                'user_id' => $user->id,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'pickup_time' => $request->pickup_time ?? now()->addMinutes(30),
                'notes' => $request->notes,
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
            'status' => 'required|in:pending,completed,cancelled',
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
        if ($order->status === 'delivered' || $order->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel completed order',
            ], 422);
        }

        // Cancel the order
        $order->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully',
            'data' => $order,
        ], 200);
    }

    public function complete($id, Request $request)
    {
        $user = $request->user();

        // Complete the order
        $order = Order::where('user_id', $user->id)->findOrFail($id);
        $order->update(['status' => 'completed']);

        return response()->json([
            'status' => true,
            'message' => 'Order completed successfully',
            'data' => $order,
        ], 200);
    }
}
