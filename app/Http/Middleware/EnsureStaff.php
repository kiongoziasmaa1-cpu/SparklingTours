<?php

namespace App\Http\Middleware;

use Closure;

class EnsureStaff
{
    public function handle($r, Closure $next)
    {
        abort_unless($r->user()?->active && in_array($r->user()->role, ['admin', 'guide'], true), 403);

        return $next($r);
    }
}
