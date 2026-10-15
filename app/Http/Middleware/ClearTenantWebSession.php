<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * On the central (landlord) domain, clear any stale tenant web auth session.
 * The landlord uses the 'superadmin' guard — a leftover 'login_web_*' session
 * key from a tenant subdomain would cause the web guard to query the 'users'
 * table on the landlord DB, which does not exist.
 */
class ClearTenantWebSession
{
    public function handle(Request $request, Closure $next)
    {
        try {
            // Forget any web-guard session to avoid cross-context user loading
            $key = 'login_web_' . sha1('web');
            if ($request->session()->has($key)) {
                $request->session()->forget($key);
            }
        } catch (\Throwable $e) {
            // Session may not be started yet — safe to ignore
        }

        return $next($request);
    }
}
