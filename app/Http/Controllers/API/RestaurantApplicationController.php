<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\RestaurantApplicationReceived;
use Illuminate\Http\Request; // Import Log facade
use Illuminate\Support\Facades\Log; // Import Mail facade
use Illuminate\Support\Facades\Mail; // We will create this Mail class later

class RestaurantApplicationController extends Controller
{
    /**
     * Store a newly submitted restaurant application.
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $validatedData = $request->validate([
            'restaurant_name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'required|string|max:25',
            'cuisine_type' => 'required|string|max:100',
            'description' => 'nullable|string|max:2000',
        ]);

        try {
            // ** Email Sending Logic will be added here in the next step **
            // For now, just log the data
            Log::info('Restaurant Application Received:', $validatedData);

            // Send email notification to admin
            // IMPORTANT: Replace with your actual admin email address!
            // Ensure your .env mail settings are configured.
            Mail::to('ghadisaab21@gmail.com')->send(new RestaurantApplicationReceived($validatedData));

            return response()->json([
                'success' => true,
                'message' => 'Application submitted successfully. We will review it shortly.',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process restaurant application:', [
                'error' => $e->getMessage(),
                'data' => $validatedData,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit application due to a server error.',
            ], 500); // Internal Server Error
        }
    }
}
