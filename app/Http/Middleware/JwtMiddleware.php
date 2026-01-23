<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Auth;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = config('app.jwt_secret');
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Authorization token not found'], 401);
        }

        try {
            $detoken = JWT::decode($token, new Key($key, 'HS256'));
            $user = User::find($detoken->sub);

            if (!$user) {
                return response()->json(['message' => 'User not found'], 401);
            }
            Auth::setUser($user);

            return $next($request);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid or expired token'], 401);
        }
    }
}
