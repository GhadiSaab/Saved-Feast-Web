<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\RestaurantInvoice;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SettlementsController extends Controller
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->middleware('auth:sanctum');
        $this->invoiceService = $invoiceService;
    }

    /**
     * Generate weekly invoices for all restaurants
     */
    public function generate(Request $request)
    {
        Gate::authorize('admin-access');

        $period = $request->get('period', 'weekly');

        if ($period !== 'weekly') {
            return response()->json([
                'status' => false,
                'message' => 'Only weekly period is supported',
            ], 400);
        }

        // Calculate the previous week (Monday to Sunday)
        $timezone = config('savedfeast.invoicing.timezone', 'Asia/Beirut');
        $now = Carbon::now($timezone);
        $lastMonday = $now->copy()->subWeek()->startOfWeek();
        $lastSunday = $now->copy()->subWeek()->endOfWeek();

        try {
            $results = $this->invoiceService->generateWeeklyInvoices($lastMonday, $lastSunday);

            return response()->json([
                'status' => true,
                'message' => 'Invoice generation completed',
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to generate invoices: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all invoices with filters
     */
    public function invoices(Request $request)
    {
        Gate::authorize('admin-access');

        $query = RestaurantInvoice::with('restaurant');

        // Filter by restaurant
        if ($request->has('restaurant_id')) {
            $query->where('restaurant_id', $request->restaurant_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $invoices,
        ]);
    }

    /**
     * Mark invoice as sent
     */
    public function markSent(Request $request, $id)
    {
        Gate::authorize('admin-access');

        try {
            $this->invoiceService->markInvoiceSent($id);

            return response()->json([
                'status' => true,
                'message' => 'Invoice marked as sent',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mark invoice as paid
     */
    public function markPaid(Request $request, $id)
    {
        Gate::authorize('admin-access');

        try {
            $this->invoiceService->markInvoicePaid($id);

            return response()->json([
                'status' => true,
                'message' => 'Invoice marked as paid',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mark invoice as overdue
     */
    public function markOverdue(Request $request, $id)
    {
        Gate::authorize('admin-access');

        try {
            $this->invoiceService->markInvoiceOverdue($id);

            return response()->json([
                'status' => true,
                'message' => 'Invoice marked as overdue',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get invoice details
     */
    public function show(Request $request, $id)
    {
        Gate::authorize('admin-access');

        $invoice = RestaurantInvoice::with(['restaurant', 'items.order.orderItems.meal'])
            ->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $invoice,
        ]);
    }

    /**
     * Download invoice PDF
     */
    public function download(Request $request, $id)
    {
        Gate::authorize('admin-access');

        $invoice = RestaurantInvoice::findOrFail($id);

        if (! $invoice->pdf_path || ! file_exists(storage_path('app/'.$invoice->pdf_path))) {
            return response()->json([
                'status' => false,
                'message' => 'PDF not available for this invoice',
            ], 404);
        }

        return response()->download(storage_path('app/'.$invoice->pdf_path));
    }
}
