<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

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
        RateLimiter::for('admin-actions', function (Request $request) {
            $key = $request->user()?->id ?? $request->ip();
            return Limit::perMinute(30)->by($key);
        });

        Inertia::share([
            'auth' => function () {
                return [
                    'user' => Auth::user()?->only('id', 'name', 'email'),
                    'permissions' => Auth::user()?->getAllPermissions()->pluck('name')->toArray() ?? [],
                ];
            },
        ]);
    }
}
