<?php

namespace App\Http\Middleware;

use App\Support\TrailingSlash;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * A public page asked for without its trailing slash (/blog, /proiect/x) gets
 * one 301 to the canonical form (/blog/), query string kept. The rule lives
 * here rather than in the web server's config, so it holds on any stack.
 *
 * It runs after routing, on the response. The catch-all {slug} route matches
 * any slug-shaped path, so a redirect decided before routing would send a
 * legacy /old to /old/ first, and only then would the 404 handler send it on
 * to /new/: a chain on every old address. Here only a page that actually
 * answered 2xx is redirected. A 404 never is: HandleNotFound has already
 * turned it into a 301 to the final target, or the 404 page. That costs a
 * page render on a slash-less request, which internal links never make.
 *
 * Only GET and HEAD (a 301 drops a POST's body: the contact form posts to
 * cerere-oferta), only routes that are public pages (TrailingSlash, so never
 * the admin, settings, account or API routes, /up, /storage or /build), never
 * a file (a dot in the last segment: robots.txt, sitemap.xml), and never a
 * JSON or Inertia request.
 */
class CanonicalTrailingSlash
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->applies($request, $response)) {
            return $response;
        }

        $query = (string) $request->server->get('QUERY_STRING');

        return new RedirectResponse(
            $request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo().'/'.($query !== '' ? '?'.$query : ''),
            301,
        );
    }

    private function applies(Request $request, Response $response): bool
    {
        $path = $request->getPathInfo();

        return ($request->isMethod('GET') || $request->isMethod('HEAD'))
            && $response->isSuccessful()
            && ! $request->expectsJson()
            && ! $request->hasHeader('X-Inertia')
            && $request->route() instanceof Route
            && TrailingSlash::isPage($request->route())
            && TrailingSlash::append($path) !== $path;
    }
}
