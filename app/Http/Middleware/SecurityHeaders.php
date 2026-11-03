<?php

namespace App\Http\Middleware;

use Closure;

class SecurityHeaders
{
    public function handle($r, Closure $next)
    {
        app()->setLocale(in_array($r->session()->get('locale'), ['en', 'sw'], true) ? $r->session()->get('locale') : 'en');
        $response = $next($r);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' https://challenges.cloudflare.com; frame-src https://challenges.cloudflare.com; connect-src 'self' https://challenges.cloudflare.com; img-src 'self' data:; style-src 'self' 'unsafe-inline'; font-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
        if ($r->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }
        if ($r->is('admin*', 'documents*', 'booking*', 'security*', 'login*')) {
            $response->headers->set('Cache-Control', 'private, no-store');
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
