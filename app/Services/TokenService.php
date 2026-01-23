<?php
namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Str;

class TokenService {
    public static function generateAccessToken($user){
        $key = config('app.jwt_secret');
        $payload = ([
            'iss' => 'auth-service', 
            'sub' => $user->id, 
            'role' => $user->role,
            'iat' => time(), 
            'exp' => time() + 3600
        ]);
        $token = JWT::encode($payload, $key, 'HS256');
        return $token;
    }
    public static function generateRefreshToken($user, $userAgent){
        $str = Str::random(64);
        $expiry = now()->addDays(30);
        RefreshToken::create([
            'user_id'    => $user->id, 
            'token'      => $str,
            'user_agent' => $userAgent, 
            'expires_at' => $expiry,
        ]);
        return $str;
    }
}