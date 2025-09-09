<?php

namespace App\Http\Controllers\API\Provider;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RestaurantInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SettlementsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get settlements summary for the authenticated provider
     */
    public function summary(Request $request)
    {
        Gate::authorize('provider-access');

        $user = $request->user();
        $restaurant = $user->restaurants()->first();

        if (! $restaurant) {
            return response()->json([
                'status' => false,
                'message' => 'No restaurant found for this provider',
            ], 404);
        }

        // Calculate amount owed (unpaid invoices)
        $amountOwed = RestaurantInvoice::where('restaurant_id', $restaurant->id)
            ->whereIn('status', ['sent', 'overdue'])
            ->sum('commission_total');

        // Get last invoice status
        $lastInvoice = RestaurantInvoice::where('restaurant_id', $restaurant->id)
            ->latest()
            ->first();

        // Calculate next invoice date (next Monday)
        $nextInvoiceDate = now()->next('monday');

        return response()->json([
            'status' => true,
            'data' => [
                'amount_owed' => number_format($amountOwed, 2),
                'last_invoice_status' => $lastInvoice ? $lastInvoice->status : null,
                'last_invoice_date' => $lastInvoice ? $lastInvoice->created_at->format('Y-m-d') : null,
                'next_invoice_date' => $nextInvoiceDate->format('Y-m-d'),
                'restaurant_id' => $restaurant->id,
                'restaurant_name' => $restaurant->name,
            ],
        ]);
    }

    /**
     * Get paginated list of invoices for the authenticated provider
     */
    public function invoices(Request $request)
    {
        Gate::authorize('provider-access');

        $user = $request->user();
        $restaurant = $user->restaurants()->first();

        if (! $restaurant) {
            return response()->json([
                'status' => false,
                'message' => 'No restaurant found for this provider',
            ], 404);
        }

        $invoices = RestaurantInvoice::where('restaurant_id', $restaurant->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'status' => true,
            'data' => $invoices,
        ]);
    }

    /**
     * Get invoice details with line items
     */
    public function showInvoice(Request $request, $id)
    {
        Gate::authorize('provider-access');

        $user = $request->user();
        $restaurant = $user->restaurants()->first();

        if (! $restaurant) {
            return response()->json([
                'status' => false,
                'message' => 'No restaurant found for this provider',
            ], 404);
        }

        $invoice = RestaurantInvoice::where('restaurant_id', $restaurant->id)
            ->with(['items.order.orderItems.meal'])
            ->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $invoice,
        ]);
    }

    /**
     * Download invoice PDF
     */
    public function downloadInvoice(Request $request, $id)
    {
        Gate::authorize('provider-access');

        $user = $request->user();
        $restaurant = $user->restaurants()->first();

        if (! $restaurant) {
            return response()->json([
                'status' => false,
                'message' => 'No restaurant found for this provider',
            ], 404);
        }

        $invoice = RestaurantInvoice::where('restaurant_id', $restaurant->id)
            ->findOrFail($id);

        if (! $invoice->pdf_path || ! file_exists(storage_path('app/'.$invoice->pdf_path))) {
            return response()->json([
                'status' => false,
                'message' => 'PDF not available for this invoice',
            ], 404);
        }

        return response()->download(storage_path('app/'.$invoice->pdf_path));
    }

    /**
     * Get recent completed orders not yet invoiced
     */
    public function recentOrders(Request $request)
    {
        Gate::authorize('provider-access');

        $user = $request->user();
        $restaurant = $user->restaurants()->first();

        if (! $restaurant) {
            return response()->json([
                'status' => false,
                'message' => 'No restaurant found for this provider',
            ], 404);
        }

        $period = $request->get('period', 'current');

        $query = Order::whereHas('orderItems.meal', function ($q) use ($restaurant) {
            $q->where('restaurant_id', $restaurant->id);
        })
            ->where('payment_method', 'CASH_ON_PICKUP')
            ->where('status', Order::STATUS_COMPLETED)
            ->whereNull('invoiced_at');

        if ($period === 'current') {
            // Current week
            $query->where('completed_at', '>=', now()->startOfWeek());
        }

        $orders = $query->with(['orderItems.meal'])
            ->orderBy('completed_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $orders,
        ]);
    }
}
