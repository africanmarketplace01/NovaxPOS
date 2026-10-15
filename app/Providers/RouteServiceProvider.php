<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/dashboard';

    public function boot()
    {
        $this->configureRateLimiting();
        parent::boot();
    }

    protected function configureRateLimiting()
    {
        // Standard API rate limit.
        // NOTE: key by the bearer token / IP — NOT $request->user(). Resolving the
        // user here (throttle runs before route middleware) would force Sanctum to
        // authenticate BEFORE tenancy initializes, so the token/user lookup would
        // run without the tenant table prefix and fail ("table ... doesn't exist").
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->bearerToken() ?: $request->ip());
        });

        // Strict rate limit for auth endpoints (login, register, password reset)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Tenant API — limit scales with package (default 300/min, overridden in Phase 6).
        // Key by bearer token / IP (not $request->user()) so throttling never forces
        // Sanctum to authenticate before tenancy initializes. See the 'api' note above.
        RateLimiter::for('tenant-api', function (Request $request) {
            return Limit::perMinute(300)->by(
                ($request->bearerToken() ?: $request->ip()) . '|' . ($request->getHost())
            );
        });

        // SaaS public registration — 5 attempts per IP per minute
        RateLimiter::for('saas-register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())
                ->response(function () {
                    return response()->view('errors.429', [], 429);
                });
        });

        // SaaS checkout — 10 attempts per IP per minute
        RateLimiter::for('saas-checkout', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip())
                ->response(function () {
                    return response()->view('errors.429', [], 429);
                });
        });

        // Superadmin login — 6 attempts per IP per 2 minutes
        RateLimiter::for('superadmin-login', function (Request $request) {
            return Limit::perMinutes(2, 6)->by($request->ip());
        });
    }

    public function map()
    {
        $this->mapApiRoutes();
        $this->mapMarketplaceApiRoutes(); // central-domain JSON API for the mobile app
        $this->mapLandlordRoutes(); // Must register first — has domain constraint for central domain
        $this->mapWebRoutes();      // Tenant routes registered after — no domain constraint
    }

    protected function mapMarketplaceApiRoutes()
    {
        // Stateless JSON API for the Flutter marketplace app. Central domain,
        // 'api' middleware (no sessions, no tenancy). Token auth via Bearer header.
        if (file_exists(base_path('routes/marketplace-api.php'))) {
            Route::prefix('api/marketplace')
                ->middleware('api')
                ->domain(env('CENTRAL_DOMAIN', 'novasuitespos.com'))
                ->group(base_path('routes/marketplace-api.php'));
        }
    }

    protected function mapWebRoutes()
    {
        // Tenant routes — use the 'tenant' middleware group which initialises
        // tenancy by domain and prevents access from the central domain.
        Route::middleware('tenant')
            ->group(base_path('routes/web.php'));
    }

    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(base_path('routes/api.php'));
    }

    protected function mapLandlordRoutes()
    {
        // Landlord routes — constrained to the central domain only so they
        // never match tenant subdomain requests.
        if (file_exists(base_path('routes/landlord.php'))) {
            Route::middleware(['web', \App\Http\Middleware\ClearTenantWebSession::class])
                ->domain(env('CENTRAL_DOMAIN', 'novasuitespos.com'))
                ->group(base_path('routes/landlord.php'));
        }
    }
}
