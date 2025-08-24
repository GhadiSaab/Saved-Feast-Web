<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash; // Import Auth facade
use Illuminate\Validation\Rule; // Import Password rule
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException; // Import Rule for unique email check

class AuthController extends Controller
{
    public function register(Request $request): \Illuminate\Http\JsonResponse
    {

        $request->validate([
            'first_name' => 'required|string|max:255', // Added max length
            'last_name' => 'required|string|max:255', // Added max length
            'email' => 'required|string|email|max:255|unique:users,email', // Added max length and string type
            // Use a more user-friendly password rule
            'password' => ['required', 'confirmed', Password::min(8)],
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

        // Assign default 'customer' role to new users
        $customerRole = \App\Models\Role::where('name', 'customer')->first();
        if ($customerRole) {
            $user->roles()->attach($customerRole->id);
        }

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

    /**
     * Update the authenticated user's profile information.
     */
    public function updateProfile(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        // Check if user can update their own profile
        $this->authorize('update', $user);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id), // Ignore current user's email
            ],
            // Add validation for other updatable fields like phone, address if needed
            // 'phone' => 'nullable|string|max:25',
            // 'address' => 'nullable|string|max:255',
        ]);

        // Split the name into first_name and last_name
        $nameParts = explode(' ', trim($validatedData['name']), 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

        // Update the user with first_name, last_name, and email
        $user->update([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $validatedData['email'],
        ]);

        // Eager load roles to return updated user data consistently
        $user->load('roles');

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }

    /**
     * Change the authenticated user's password.
     */
    public function changePassword(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $validatedData = $request->validate([
            'current_password' => 'required|string',
            'new_password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        // Check if the current password matches
        if (! Hash::check($validatedData['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided current password does not match our records.'],
            ]);
        }

        // Update the password
        $user->password = Hash::make($validatedData['new_password']);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }
}
