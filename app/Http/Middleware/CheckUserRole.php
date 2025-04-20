<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     *
     * Check if the authenticated user has the required role.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role The required role name (e.g., 'provider', 'admin')
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Check if user is authenticated first
        if (!Auth::check()) {
            // Or return a JSON response for API routes
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = Auth::user();

        // Check if the user has the required role
        // Assumes the roles relationship is defined on the User model
        if (!$user->roles()->where('name', $role)->exists()) {
            // User does not have the required role, return forbidden response
            return response()->json(['message' => 'Forbidden. You do not have the required permissions.'], 403);
        }

        // User has the role, proceed with the request
        return $next($request);
    }
}
