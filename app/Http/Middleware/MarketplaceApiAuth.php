<?php

namespace App\Http\Middleware;

use App\Models\landlord\MarketplaceCustomer;
use App\Models\landlord\MarketplaceCustomerToken;
use Closure;
use Illuminate\Http\Request;

/**
 * Resolves the marketplace customer from a Bearer token and binds it to the
 * request (available via $request->user() / $request->attributes 'customer').
 */
class MarketplaceApiAuth
{
    public function handle(Request $request, Closure $next)
    {
        $bearer = $request->bearerToken();
        if (!$bearer) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = MarketplaceCustomerToken::findByPlain($bearer);
        if (!$token) {
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        $customer = MarketplaceCustomer::where('id', $token->customer_id)->where('is_active', true)->first();
        if (!$customer) {
            return response()->json(['message' => 'Account not found or inactive.'], 401);
        }

        $token->forceFill(['last_used_at' => now()])->saveQuietly();

        $request->setUserResolver(fn () => $customer);
        $request->attributes->set('customer', $customer);
        $request->attributes->set('token_id', $token->id);

        return $next($request);
    }
}
