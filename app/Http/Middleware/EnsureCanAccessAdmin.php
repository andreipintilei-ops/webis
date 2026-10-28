<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates /admin to staff roles. Registered as the `can.access-admin`
 * route-middleware alias; admin-only areas add `can:admin-only` on top.
 */
class EnsureCanAccessAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->role->canAccessAdmin(), 403);

        return $next($request);
    }
}
