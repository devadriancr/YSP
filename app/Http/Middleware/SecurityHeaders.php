<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser protections the app sends itself, so they apply on any server (IIS adds
 * nosniff, X-Frame-Options and Referrer-Policy in public/web.config).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->add([
            // No framing by other sites, no plugins, forms and <base> only to this site.
            // shortcut: no script-src yet (Alpine needs eval and Vite dev loads another origin); tighten when both allow it
            'Content-Security-Policy' => "frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self'",
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        // Only over HTTPS: tells the browser to never use plain HTTP for this site again
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
