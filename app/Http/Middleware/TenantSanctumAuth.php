<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Sanctum token auth that runs AFTER tenancy is initialized.
 *
 * The default `auth:sanctum` guard can resolve the bearer token before
 * InitializeTenancyByDomain switches the DB connection, so the lookup hits the
 * central DB instead of the tenant DB (→ "Unauthenticated"). This middleware is
 * placed after tenancy in the stack and resolves the token explicitly on the
 * now-active tenant connection, sidestepping the guard-timing issue entirely.
 */
class TenantSanctumAuth
{
    public function handle(Request $request, Closure $next)
    {
        $bearer = $request->bearerToken();
        if (!$bearer) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Tenancy is already initialized here, so the default connection is the
        // tenant DB — findToken() queries the tenant's personal_access_tokens.
        $token = PersonalAccessToken::findToken($bearer);
        if (!$token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Expiry check (Sanctum config `expiration` is null = never, so usually skipped).
        if ($token->expires_at && $token->expires_at->isPast()) {
            return response()->json(['message' => 'Token expired.'], 401);
        }

        $user = $token->tokenable;
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Make the token available (for currentAccessToken()/logout) and bind the
        // user for the rest of the request.
        if (method_exists($user, 'withAccessToken')) {
            $user->withAccessToken($token);
        }
        $request->setUserResolver(fn () => $user);

        try {
            $token->forceFill(['last_used_at' => now()])->save();
        } catch (\Throwable $e) {
            // last_used_at is best-effort; never block the request.
        }

        return $next($request);
    }
}
