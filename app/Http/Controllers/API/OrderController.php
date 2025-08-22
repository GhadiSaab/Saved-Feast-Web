<?php

namespace App\Http\Controllers\API; // Corrected namespace

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Meal; // Import Meal model
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
            $orders = Order::whereHas('orderItems.meal', function($query) use ($restaurantIds) {
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
            'data' => $orders
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
            'data' => $order
        ], 200);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // Check if user can create orders
        $this->authorize('create', Order::class);

        // Validate the request data
        $request->validate([
            'total_amount' => 'required|numeric',
            'order_items' => 'required|array',
            'order_items.*.meal_id' => 'required|exists:meals,id',
            'order_items.*.meal_id' => 'required|exists:meals,id',
            'order_items.*.quantity' => 'required|integer|min:1',
            // 'order_items.*.price' validation removed, as we'll fetch price from the Meal model
        ]);

        // Create the order
        $order = Order::create([
            'user_id' => $user->id,
            'total_amount' => $request->total_amount, // Consider recalculating total amount based on fetched prices
            'status' => 'pending', // Use string status directly or define constants in Order model
        ]);

        // Attach order items to the order, fetching prices from Meal model
        foreach ($request->order_items as $itemData) {
             $meal = Meal::find($itemData['meal_id']);

             if ($meal) {
                 // Ensure meal has sufficient quantity (optional check)
                 // if ($meal->quantity < $itemData['quantity']) { ... handle error ... }

                 $order->orderItems()->create([
                     'meal_id' => $meal->id,
                     'quantity' => $itemData['quantity'],
                     'price' => $meal->current_price, // Use current_price from Meal model
                     'original_price' => $meal->original_price ?? $meal->current_price, // Use original_price, fallback to current_price if null
                 ]);

                 // Optionally decrease meal quantity
                 // $meal->decrement('quantity', $itemData['quantity']);
             } else {
                 // Log error if meal not found, potentially rollback transaction
                 Log::error("Meal with ID {$itemData['meal_id']} not found during order creation for order ID {$order->id}.");
                 // Consider deleting the order and returning an error response
                 // $order->delete();
                 // return response()->json(['status' => false, 'message' => 'Invalid meal ID provided.'], 400);
             }
        }

        // Reload the order with items to return the complete data
        $order->load('orderItems.meal');

        return response()->json([
            'status' => true,
            'message' => 'Order created successfully',
            'data' => $order
        ], 201);
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
            'data' => $order
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
    public function cancel($id, Request $request)
    {
        $user = $request->user();

        // Cancel the order
        $order = Order::where('user_id', $user->id)->findOrFail($id);
        $order->update(['status' => 'cancelled']);

        return response()->json([
            'status' => true,
            'message' => 'Order cancelled successfully',
            'data' => $order
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
            'data' => $order
        ], 200);
    }
}
