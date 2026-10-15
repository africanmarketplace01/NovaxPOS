<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Enforces per-package usage limits (products, users, warehouses).
 *
 * Attach to resource creation routes:
 *   Route::post('/products', ...)->middleware('usage.limits:product');
 *   Route::post('/users', ...)->middleware('usage.limits:user');
 *   Route::post('/warehouses', ...)->middleware('usage.limits:warehouse');
 *
 * Skips check when:
 *  - Not a SaaS install
 *  - The package has null for the limit (= unlimited)
 *  - The limit is already stored as 0 (unlimited in old schema)
 */
class CheckUsageLimits
{
    /**
     * Map resource types to [table, package_column].
     */
    private const RESOURCE_MAP = [
        'product'   => ['products',   'product_limit'],
        'user'      => ['users',      'user_limit'],
        'warehouse' => ['warehouses', 'warehouse_limit'],
    ];

    public function handle(Request $request, Closure $next, string $resource = 'product')
    {
        // Only enforce in SaaS mode
        if (!config('database.connections.saleprosaas_landlord')) {
            return $next($request);
        }

        // Superadmin impersonation — skip limits
        if (session('superadmin_impersonating')) {
            return $next($request);
        }

        try {
            $limit = $this->getLimit($resource);

            // null or 0 = unlimited
            if ($limit === null || $limit === 0) {
                return $next($request);
            }

            $current = $this->getCurrent($resource);

            if ($current >= $limit) {
                return $this->limitExceededResponse($request, $resource, $current, $limit);
            }
        } catch (\Throwable $e) {
            Log::warning('CheckUsageLimits error: ' . $e->getMessage());
        }

        return $next($request);
    }

    /**
     * Get the package limit for this resource type.
     * Cached per-tenant for 10 minutes.
     */
    private function getLimit(string $resource): ?int
    {
        if (!isset(self::RESOURCE_MAP[$resource])) {
            return null;
        }

        $column   = self::RESOURCE_MAP[$resource][1];
        $tenantId = tenancy()->tenant?->getTenantKey() ?? 'unknown';
        $cacheKey = "tenant_limit:{$tenantId}:{$resource}";

        return Cache::remember($cacheKey, 600, function () use ($tenantId, $column) {
            $packageId = DB::connection('saleprosaas_landlord')
                ->table('tenants')
                ->where('id', $tenantId)
                ->value('package_id');

            if (!$packageId) return null;

            $limit = DB::connection('saleprosaas_landlord')
                ->table('packages')
                ->where('id', $packageId)
                ->value($column);

            return $limit !== null ? (int) $limit : null;
        });
    }

    /**
     * Get current count of the resource in the tenant's DB.
     */
    private function getCurrent(string $resource): int
    {
        if (!isset(self::RESOURCE_MAP[$resource])) {
            return 0;
        }

        $table = self::RESOURCE_MAP[$resource][0];

        return (int) DB::table($table)->count();
    }

    /**
     * Return an appropriate limit-exceeded response.
     * JSON for AJAX, redirect with flash for regular requests.
     */
    private function limitExceededResponse(Request $request, string $resource, int $current, int $limit)
    {
        $message = ucfirst($resource) . " limit reached ({$current}/{$limit}). Please upgrade your plan to add more.";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'limit_exceeded' => true,
                'resource' => $resource,
                'current'  => $current,
                'limit'    => $limit,
            ], 403);
        }

        return redirect()->back()
            ->with('not_permitted', $message)
            ->with('upgrade_required', true);
    }
}
