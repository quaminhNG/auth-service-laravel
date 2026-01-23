<?php

namespace App\Providers;

use App\Enums\UserRole;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

use function PHPSTORM_META\map;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function(Request $request) {
           return Limit::perMinute(5)->by($request->email ?: $request->ip())->response(function (Request $request, array $headers){
                return response()->json([
                    'message' => 'Too many login attempts. Please try again later.'
                ], 429, $headers);
           });

        });
        Request::macro('isAdmin', function () {
            $user = $this->user();
            return $user && $user->role === UserRole::ADMIN;
        });
    }
}
