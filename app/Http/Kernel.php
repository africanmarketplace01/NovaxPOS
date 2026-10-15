<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        // SecurityHeaders removed from global stack — apply via route group or web middleware group if needed
        \App\Http\Middleware\Cors::class,
        \App\Http\Middleware\TrustProxies::class,
        \App\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ],

        'api' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        /*
        |----------------------------------------------------------------------
        | Tenant middleware group
        |----------------------------------------------------------------------
        | Applied to all tenant routes. Initialises tenancy by subdomain and
        | prevents access from the central domain.
        */
        'tenant' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\InitializeTenancyByDomain::class,
            \App\Http\Middleware\CheckTenantExpiry::class,
        ],
    ];

    /**
     * The application's route middleware aliases.
     * Renamed from $routeMiddleware → $middlewareAliases for Laravel 10+/12 compatibility.
     *
     * @var array
     */
    protected $middlewareAliases = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'active' => \App\Http\Middleware\Active::class,
        'common' => \App\Http\Middleware\Common::class,
        'tenant.sanctum' => \App\Http\Middleware\TenantSanctumAuth::class,
        'superadminauth' => \App\Http\Middleware\SuperAdminAuth::class,
        'marketplace.customer' => \App\Http\Middleware\MarketplaceCustomerAuth::class,
        'marketplace.api' => \App\Http\Middleware\MarketplaceApiAuth::class,
        'agency.api'      => \App\Http\Middleware\DeliveryAgencyApiAuth::class,
        'tenancy.domain' => \App\Http\Middleware\InitializeTenancyByDomain::class,
        // 'customerauth' => \Modules\Ecommerce\Http\Middleware\CustomerAuth::class,
        // 'customerauth0' => \Modules\Restaurant\Http\Middleware\CustomerAuth::class,
        'cors' => \App\Http\Middleware\Cors::class,
        'checkSmsBalance' => \App\Http\Middleware\CheckSmsBalance::class,
        'validate_mobile_token' => \App\Http\Middleware\ValidateMobileToken::class,
        'hasPermanentDeletePermission' => \App\Http\Middleware\HasPermanentDeletePermission::class,
        'permission' => \App\Http\Middleware\PermissionMiddleware::class,
        'tenant.expiry' => \App\Http\Middleware\CheckTenantExpiry::class,
        'usage.limits'  => \App\Http\Middleware\CheckUsageLimits::class,
        'api.token'     => \App\Http\Middleware\ValidateApiToken::class,
    ];

    /**
     * The priority-sorted list of middleware.
     *
     * This forces non-global middleware to always be in the given order.
     *
     * @var array
     */
    protected $middlewarePriority = [
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        // Tenancy MUST initialize before auth so the User model uses the tenant DB connection
        \App\Http\Middleware\InitializeTenancyByDomain::class,
        \App\Http\Middleware\Authenticate::class,
        \Illuminate\Routing\Middleware\ThrottleRequests::class,
        \Illuminate\Session\Middleware\AuthenticateSession::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Illuminate\Auth\Middleware\Authorize::class,
    ];
}
