<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
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

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
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

        return response()->json([
            'success' => true,
            'message' => 'User logged in successfully',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    } 

    
}
