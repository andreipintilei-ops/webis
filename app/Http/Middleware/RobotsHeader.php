<?php

namespace App\Http\Middleware;

use App\Support\Seo\Indexing;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * X-Robots-Tag: noindex, nofollow on every response that must stay out of
 * search engines (Support\Seo\Indexing): everything outside production on
 * the canonical host, and the admin, login and account pages everywhere.
 * Global, so it covers files and errors too, not only the pages' <meta>.
 */
class RobotsHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($header = Indexing::header($request)) {
            $response->headers->set('X-Robots-Tag', $header);
        }

        return $response;
    }
}
