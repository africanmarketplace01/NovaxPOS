<?php

namespace App\Http\Middleware;

use App\Models\landlord\DeliveryAgency;
use App\Models\landlord\DeliveryAgencyToken;
use Closure;
use Illuminate\Http\Request;

/** Resolves the delivery agency from a Bearer token (marketplace app portal). */
class DeliveryAgencyApiAuth
{
    public function handle(Request $request, Closure $next)
    {
        $bearer = $request->bearerToken();
        if (!$bearer) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        $token = DeliveryAgencyToken::findByPlain($bearer);
        if (!$token) {
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }
        $agency = DeliveryAgency::where('id', $token->agency_id)->where('is_active', true)->first();
        if (!$agency) {
            return response()->json(['message' => 'Agency not found or inactive.'], 401);
        }
        $token->forceFill(['last_used_at' => now()])->saveQuietly();
        $request->setUserResolver(fn () => $agency);
        $request->attributes->set('agency', $agency);
        $request->attributes->set('token_id', $token->id);
        return $next($request);
    }
}
