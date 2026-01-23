<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\RefreshToken;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
class AuthController extends \App\Http\Controllers\Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        return response()->json(
            [
                'message' => 'User registered successfully',
                'user' => $user
            ],
            201
        );
    }
    public function login(LoginRequest $request)
    {

        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            Log::warning('Phát hiện đăng nhập thất bại!', [
                'email' => $request->email,
                'ip' => $request->ip()
            ]);
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }
        $accessToken = TokenService::generateAccessToken($user);
        $refreshToken = TokenService::generateRefreshToken($user, $request->header('User-Agent'));
        return response()->json([
            'message' => 'User logged in successfully',
            'user' => $user,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ], 200);
    }
    public function refresh(Request $request)
    {
        $refresh = $request->input('refresh_token');
        $rtRecord = RefreshToken::where('token', $refresh)->first();
        if (!$rtRecord) {
            return response()->json(['message' => 'Token not found'], 401);
        }
        if($rtRecord->is_revoked){
            RefreshToken::where('user_id', $rtRecord->user_id)->delete();
            return response()->json(['message' => 'Security alert: Token reused!'], 401);
        }
        if ($rtRecord->expires_at < now()) {
            $rtRecord->delete();
            return response()->json(['message' => 'Token expired'], 401);
        }
        $rtRecord->update(['is_revoked' => true]);
        $user = $rtRecord->user;

        $newAccessToken = TokenService::generateAccessToken($rtRecord->user);
        $newRefreshToken = TokenService::generateRefreshToken($rtRecord->user, $request->header('User-Agent'));

        return response()->json([
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
        ], 200);
    }
    public function logout(Request $request)
    {
        $user = $request->user();
        $refreshToken = $request->input('refresh_token');
        RefreshToken::where(['token' => $refreshToken])->update(['is_revoked' => true]);
        return response()->json(
            [
                'message' => 'Logged out successfully'
            ], 200);
    }
    public function dashboard(Request $request) {
        return response()->json('Admin!');
    }
}
