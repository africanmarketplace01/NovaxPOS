<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Validates a Bearer token against the tenant's api_tokens table.
 *
 * Usage:
 *   Route::middleware('api.token')->get('/api/products', ...);
 *   Route::middleware('api.token:write')->post('/api/products', ...);
 *
 * Clients send: Authorization: Bearer <plain_token>
 */
class ValidateApiToken
{
    public function handle(Request $request, Closure $next, string $ability = '*')
    {
        $plain = $request->bearerToken();

        if (!$plain) {
            return response()->json(['message' => 'API token required.'], 401);
        }

        // Token format: <id>|<plain>  (similar to Sanctum's format)
        [$id, $token] = array_pad(explode('|', $plain, 2), 2, '');

        $record = ApiToken::find((int) $id);

        if (!$record || !Hash::check($token, $record->token)) {
            return response()->json(['message' => 'Invalid API token.'], 401);
        }

        if ($record->isExpired()) {
            return response()->json(['message' => 'API token has expired.'], 401);
        }

        if ($ability !== '*' && !$record->can($ability)) {
            return response()->json(['message' => "Token does not have '{$ability}' ability."], 403);
        }

        // Update last_used_at (every 60s at most to avoid constant writes)
        if (!$record->last_used_at || $record->last_used_at->diffInSeconds(now()) > 60) {
            $record->update(['last_used_at' => now()]);
        }

        // Make the token available downstream
        $request->attributes->set('api_token', $record);
        $request->attributes->set('api_token_user', $record->user);

        return $next($request);
    }
}
