<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketplaceCustomerAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('customer')->check()) {
            // Remember where the customer wanted to go, then send to login.
            session(['url.intended' => $request->fullUrl()]);
            return redirect()->route('customer.login')
                ->with('error', 'Please sign in to your account to continue.');
        }

        return $next($request);
    }
}
