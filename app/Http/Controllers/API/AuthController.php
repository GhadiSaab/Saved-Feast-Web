<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password; // Import Password rule
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        
        $request->validate([
            'first_name' => 'required|string|max:255', // Added max length
            'last_name' => 'required|string|max:255', // Added max length
            'email' => 'required|string|email|max:255|unique:users,email', // Added max length and string type
            // Use the Password rule for better complexity enforcement
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'phone' => 'nullable|string|max:25', // Added max length
            'address' => 'nullable|string|max:255', // Added max length
        ]);

        
        $user = User::create([
            'first_name' => $request->first_name, 
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password), 
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        
        $token = $user->createToken('auth_token')->plainTextToken;

        // Eager load roles relationship
        $user->load('roles');

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user, // Return user data with roles
        ]);
    }


    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        
        $user = User::where('email', $request->email)->first();

        
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        
        $token = $user->createToken('auth_token')->plainTextToken;

        // Eager load roles relationship
        $user->load('roles');

        return response()->json([
            'success' => true,
            'message' => 'User logged in successfully',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user, // Return user data with roles
        ]);
    }

    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        // Get the authenticated user
        $user = $request->user();

        // Revoke the token that was used to authenticate the current request...
        $user->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'User logged out successfully',
        ]);
    }
}
