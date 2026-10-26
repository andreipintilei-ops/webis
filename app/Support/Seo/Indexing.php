<?php

namespace App\Support\Seo;

use Illuminate\Http\Request;

/**
 * Whether search engines may index what a request returns. Only production
 * on the canonical host (config site.canonical_url, www.webis.ro) may be
 * indexed: a local or staging copy never is, even when linked to, and nor is
 * production reached at another host. The admin, the login and the account
 * pages never are, anywhere.
 *
 * The public layout writes it as <meta name="robots">; RobotsHeader sends it
 * as X-Robots-Tag on every response.
 */
final class Indexing
{
    /** Paths never indexed: the admin, Fortify's auth and account routes, the user's settings. */
    private const array PRIVATE_PATHS = [
        'admin', 'admin/*', 'login', 'logout', 'register', 'forgot-password', 'reset-password', 'reset-password/*',
        'two-factor-challenge', 'email/*', 'user/*', 'passkeys/*', 'settings', 'settings/*',
    ];

    public static function siteAllowed(Request $request): bool
    {
        return app()->isProduction()
            && strcasecmp($request->getHost(), (string) parse_url((string) config('site.canonical_url'), PHP_URL_HOST)) === 0;
    }

    public static function isPrivate(Request $request): bool
    {
        return $request->is(...self::PRIVATE_PATHS);
    }

    /** The X-Robots-Tag a response gets, or null for none. */
    public static function header(Request $request): ?string
    {
        return ! self::siteAllowed($request) || self::isPrivate($request) ? 'noindex, nofollow' : null;
    }
}
