<?php

namespace App\Support;

use Illuminate\Routing\Route;
use Illuminate\Support\Str;

/**
 * The public pages' canonical form: a trailing slash (/blog/, /proiect/x/),
 * as on the old WordPress site, so no indexed address changes. The one place
 * that says which routes are pages: the URL generator (App\Routing\UrlGenerator)
 * emits the slash for them, and CanonicalTrailingSlash 301s a slash-less
 * request for one to the slash form.
 *
 * Pages are named one by one rather than inferred from a route's middleware
 * or its URI. A route missing here only keeps Laravel's slash-less form (its
 * links take a 301, which the internal-link crawl test catches); a route
 * wrongly caught by a broader rule would change URLs that must stay exactly
 * as they are: signed links, the admin and account routes, Wayfinder's,
 * Fortify's and the passkey endpoints, form actions.
 */
final class TrailingSlash
{
    /**
     * Named public GET routes whose address ends in a slash. "home" is "/"
     * and stays so; it is listed because it is a page all the same.
     */
    public const array PAGE_ROUTES = [
        'home',
        'services.index',
        'services.web',
        'services.software',
        'services.products',
        'projects.index',
        'projects.show',
        'posts.index',
        'posts.category',
        'trials.story',
        'pages.show',
        // The industry pages at /servicii/{slug}, with the services rename.
        'pages.industry',
    ];

    /**
     * Whether the route is a public page, canonical with a trailing slash.
     */
    public static function isPage(?Route $route): bool
    {
        return $route !== null
            && in_array($route->getName(), self::PAGE_ROUTES, true)
            && in_array('GET', $route->methods(), true);
    }

    /**
     * A link written as a path (/portofoliu?tip=magazin), in the form the URL
     * generator gives it (/portofoliu/?tip=magazin), still root-relative. For
     * links kept as plain paths, such as the menus' (SiteNavigation). Anything
     * that isn't a root-relative path comes back as given.
     */
    public static function href(string $href): string
    {
        if (! str_starts_with($href, '/') || str_starts_with($href, '//')) {
            return $href;
        }

        $root = rtrim(url('/'), '/');
        $url = url($href);

        return str_starts_with($url, $root) ? (substr($url, strlen($root)) ?: '/') : $url;
    }

    /**
     * The URL (absolute or root-relative) with a slash at the end of its path,
     * ahead of any query string or fragment: /blog → /blog/, /blog?pagina=2 →
     * /blog/?pagina=2. Unchanged: the root, a path that already has one, and
     * a file (a dot in the last segment: /sitemap.xml).
     */
    public static function append(string $url): string
    {
        $end = strcspn($url, '?#');
        $base = substr($url, 0, $end);
        $path = (string) parse_url($base, PHP_URL_PATH);

        if ($path === '' || str_ends_with($path, '/') || str_contains(Str::afterLast($path, '/'), '.')) {
            return $url;
        }

        return $base.'/'.substr($url, $end);
    }
}
