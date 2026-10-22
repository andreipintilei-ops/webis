<?php

namespace App\Http\Middleware;

use App\Actions\Seo\HandleNotFound;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * WordPress's short links (/?p=123, /?page_id=45) point at the home page,
 * which is a live route, so they never reach the 404 handler. Here they get
 * the same answer HandleNotFound would give: a 301 to the post's new address,
 * when the legacy id map knows it. Anything else renders the home page.
 */
class RedirectLegacyIds
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getPathInfo() === '/'
            && ($request->isMethod('GET') || $request->isMethod('HEAD'))
            && $request->hasAny(HandleNotFound::LEGACY_ID_KEYS)
            && ($response = app(HandleNotFound::class)->legacyIdRedirect($request)) !== null) {
            return $response;
        }

        return $next($request);
    }
}
