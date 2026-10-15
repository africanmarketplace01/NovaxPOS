<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\landlord\Tenant;

/**
 * Tenancy initializer supporting:
 * 1. Standard *.novasuitespos.com subdomains (stored as full domain in domains table)
 * 2. Custom white-label domains stored in tenant JSON data column
 */
class InitializeTenancyByDomain
{
    public function handle(Request $request, Closure $next)
    {
        $host          = $request->getHost();
        $centralDomain = env('CENTRAL_DOMAIN', 'novasuitespos.com');
        $connection    = config('database.connections.saleprosaas_landlord')
            ? 'saleprosaas_landlord'
            : config('database.default');

        // 1. Subdomain of the central domain
        if (str_ends_with($host, '.' . $centralDomain)) {
            $tenant = $this->findTenantByFullDomain($host, $connection)
                   ?? $this->findTenantBySubdomain($host, $centralDomain, $connection);

            if ($tenant) {
                tenancy()->initialize($tenant);
                // Discard any auth guard resolved BEFORE tenancy switched the DB
                // (e.g. by a rate limiter touching $request->user()). Otherwise a
                // token looked up against the central DB caches a null user and
                // auth:sanctum returns "Unauthenticated" even though the token is
                // valid in the tenant DB.
                auth()->forgetGuards();
                return $next($request);
            }

            abort(404, 'Tenant not found.');
        }

        // 2. Custom white-label domain
        $tenant = $this->findTenantByCustomDomain($host, $connection);
        if ($tenant) {
            tenancy()->initialize($tenant);
            auth()->forgetGuards();
            return $next($request);
        }

        abort(404, 'Domain not recognized.');
    }

    private function findTenantByFullDomain(string $host, string $connection): ?Tenant
    {
        try {
            $row = DB::connection($connection)
                ->table('domains')
                ->where('domain', $host)
                ->first();

            if ($row) {
                return Tenant::on($connection)->find($row->tenant_id);
            }
        } catch (\Throwable $e) {
            Log::error('InitializeTenancyByDomain (full domain): ' . $e->getMessage());
        }
        return null;
    }

    private function findTenantBySubdomain(string $host, string $centralDomain, string $connection): ?Tenant
    {
        try {
            // Extract subdomain: rmsb.novasuitespos.com → rmsb
            $subdomain = substr($host, 0, strlen($host) - strlen($centralDomain) - 1);
            if ($subdomain) {
                return Tenant::on($connection)->find($subdomain);
            }
        } catch (\Throwable $e) {
            Log::error('InitializeTenancyByDomain (subdomain): ' . $e->getMessage());
        }
        return null;
    }

    private function findTenantByCustomDomain(string $host, string $connection): ?Tenant
    {
        if (!config('database.connections.saleprosaas_landlord')) return null;
        try {
            return Tenant::on($connection)
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.custom_domain')) = ?", [$host])
                ->first();
        } catch (\Throwable $e) {
            Log::error('InitializeTenancyByDomain (custom domain): ' . $e->getMessage());
        }
        return null;
    }
}
