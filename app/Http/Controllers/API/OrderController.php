<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Fetch orders for the authenticated user
        $orders = Order::where('user_id', $user->id)->with('orderItems.meal')->get();

        return response()->json([
            'status' => true,
            'message' => 'Orders retrieved successfully',
            'data' => $orders
        ], 200);
    }
    public function show($id, Request $request)
    {
        $user = $request->user();

        // Fetch a specific order for the authenticated user
        $order = Order::where('user_id', $user->id)->with('orderItems.meal')->findOrFail($id);

        return response()->json([
            'status' => true,
            'message' => 'Order retrieved successfully',
            'data' => $order
        ], 200);
    }
    public function store(Request $request)
    {
        $user = $request->user();

        // Validate the request data
        $request->validate([
            'total_amount' => 'required|numeric',
            'order_items' => 'required|array',
            'order_items.*.meal_id' => 'required|exists:meals,id',
            'order_items.*.quantity' => 'required|integer|min:1',
        ]);

        // Create the order
        $order = Order::create([
            'user_id' => $user->id,
            'total_amount' => $request->total_amount,
            'status' => Order::STATUS_PENDING,
        ]);

        // Attach order items to the order
        foreach ($request->order_items as $item) {
            $order->orderItems()->create([
                'meal_id' => $item['meal_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Order created successfully',
            'data' => $order
        ], 201);
    }
    public function update(Request $request, $id)
    {
        $user = $request->user();

        // Validate the request data
        $request->validate([
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        // Update the order status
        $order = Order::where('user_id', $user->id)->findOrFail($id);
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
        $order->update(['status' => Order::STATUS_CANCELLED]);

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
        $order->update(['status' => Order::STATUS_COMPLETED]);

        return response()->json([
            'status' => true,
            'message' => 'Order completed successfully',
            'data' => $order
        ], 200);
    }
}
