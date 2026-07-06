<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // [SECURITY] Dedicated limiters so auth/payment abuse can't exhaust (or hide behind) the
        // shared browsing pool, and vice-versa (M11). Tuned so legitimate login/OTP + payment
        // flows and single-IP dev testing aren't throttled.
        RateLimiter::for('auth', function (Request $request) {
            // [SECURITY][R2-M2] Key by phone+IP (was IP only) so a single account can't be
            // brute-forced from rotating IPs without also hitting a per-account ceiling. The strong
            // guarantee is the per-code TTL + attempt lockout (R2-C5); this is defence in depth.
            return Limit::perMinute(30)->by(($request->input('phone') ?? '') . '|' . $request->ip());
        });
        RateLimiter::for('payment', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
