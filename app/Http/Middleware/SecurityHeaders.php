<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only attach to web responses, not binary downloads
        if (!method_exists($response, 'header')) {
            return $response;
        }

        // Prevent clickjacking
        $response->header('X-Frame-Options', 'SAMEORIGIN');

        // Stop MIME sniffing
        $response->header('X-Content-Type-Options', 'nosniff');

        // XSS protection (legacy browsers)
        $response->header('X-XSS-Protection', '1; mode=block');

        // Referrer policy
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions policy
        $response->header('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // HSTS — only on HTTPS
        if ($request->isSecure()) {
            $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Content Security Policy — permissive defaults; tighten per route if needed
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://code.jquery.com https://cdnjs.cloudflare.com https://cdn.datatables.net",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com https://cdn.datatables.net",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data: https://cdnjs.cloudflare.com https://fonts.gstatic.com https://cdn.datatables.net",
            "connect-src 'self' https://cdn.jsdelivr.net https://cdn.datatables.net",
            "frame-src https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com",
            "media-src 'self' blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);

        $response->header('Content-Security-Policy', $csp);

        return $response;
    }
}
