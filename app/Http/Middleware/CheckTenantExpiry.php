<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Blocks access to a tenant's POS if their subscription has expired or
 * their account has been suspended by the superadmin.
 *
 * Skips the check on:
 *  - Non-SaaS installs (no landlord connection configured)
 *  - Superadmin impersonation (session flag set by ClientController::impersonate)
 *  - The suspension/expired page itself (to avoid redirect loops)
 */
class CheckTenantExpiry
{
    public function handle(Request $request, Closure $next)
    {
        // Not a SaaS install — skip
        if (!config('database.connections.saleprosaas_landlord')) {
            return $next($request);
        }

        // Superadmin is impersonating — skip all checks
        if (session('superadmin_impersonating')) {
            return $next($request);
        }

        // Already on the suspension page — avoid redirect loop
        if ($request->routeIs('tenant.suspended') || $request->routeIs('tenant.expired')) {
            return $next($request);
        }

        try {
            $tenantId = tenancy()->tenant?->getTenantKey();
            if (!$tenantId) {
                return $next($request);
            }

            $tenant = DB::connection('saleprosaas_landlord')
                ->table('tenants')
                ->where('id', $tenantId)
                ->first();

            if (!$tenant) {
                return $next($request);
            }

            // Account suspended by admin
            if (!$tenant->status) {
                return response()->view('tenant.suspended', [
                    'reason'   => 'suspended',
                    'tenantId' => $tenantId,
                ], 403);
            }

            // Subscription expired (has an expiry date and it's in the past)
            if ($tenant->expiry_date && now()->gt($tenant->expiry_date)) {
                // Allow a grace window (saas_settings: grace_days, default 3)
                $graceDays = (int)(DB::connection('saleprosaas_landlord')
                    ->table('saas_settings')
                    ->where('key', 'grace_days')
                    ->value('value') ?? 3);

                $graceEnd = \Carbon\Carbon::parse($tenant->expiry_date)->addDays($graceDays);

                if (now()->gt($graceEnd)) {
                    return response()->view('tenant.suspended', [
                        'reason'     => 'expired',
                        'tenantId'   => $tenantId,
                        'expiredAt'  => $tenant->expiry_date,
                    ], 402);
                }

                // Within grace period — allow access but flash a warning
                session()->flash('grace_warning', true);
                session()->flash('grace_days_left', now()->diffInDays($graceEnd));
            }

        } catch (\Throwable $e) {
            // Don't block the tenant if the check itself fails
            \Illuminate\Support\Facades\Log::warning('CheckTenantExpiry error: ' . $e->getMessage());
        }

        return $next($request);
    }
}
