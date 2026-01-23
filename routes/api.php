<?php
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/refresh', [AuthController::class, 'refresh']);

Route::middleware('jwt.auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('role:admin')->group(function(){
        Route::get('/admin/dashboard', [AuthController::class, 'dashboard']);
    });

    Route::get('/me', function (Request $request) {
        return response()->json([
            'user' => $request->user(), 
            'message' => 'great!'
        ]);
    });
});