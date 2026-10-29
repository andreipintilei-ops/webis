<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Strict-Transport-Security on every HTTPS response: browsers that have
 * seen it go straight to https for a year, never asking over http first.
 * The host alone — no includeSubDomains until every subdomain is known to
 * answer on HTTPS. Not locally, where a year-long rule would outlive any
 * change to the .test setup.
 */
class StrictTransportSecurity
{
    public const string VALUE = 'max-age=31536000';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isSecure() && ! app()->isLocal()) {
            $response->headers->set('Strict-Transport-Security', self::VALUE);
        }

        return $response;
    }
}
