<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $hasRole = collect($roles)->contains(fn($role) => $user->role?->value === $role);
    
        if (!$hasRole) {
            return response()->json([
                'message' => 'Forbidden: You do not have the required role'
            ], 403);
        }
        return $next($request);
    }
}
