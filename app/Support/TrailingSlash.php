<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Routing\RedirectController;
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
        'services.shop',
        'services.presentation',
        'projects.index',
        'projects.show',
        'posts.index',
        'posts.category',
        'trials.story',
        'trials.right',
        'pages.show',
        // The industry pages at /servicii/{slug}, with the services rename.
        'pages.industry',
        // The cookie policy, in code (public/cookies).
        'legal.cookies',
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
     * links kept as plain paths, such as the menus' (SiteNavigation) and the
     * ones editors type into blocks (a hero's "/contact"). A path that is one
     * of the app's own permanent redirects (/clienti) links straight to where
     * it leads, so the click takes no hop. Anything that isn't a root-relative
     * path comes back as given.
     */
    public static function href(string $href): string
    {
        if (! str_starts_with($href, '/') || str_starts_with($href, '//')) {
            return $href;
        }

        $root = rtrim(url('/'), '/');
        $url = url(self::throughRedirect($href));

        return str_starts_with($url, $root) ? (substr($url, strlen($root)) ?: '/') : $url;
    }

    /**
     * Where a root-relative path leads when it is one of the app's permanent
     * Route::redirect()s (/clienti → /portofoliu), query string kept; the path
     * itself otherwise. Only those routes are tried, one by one, so matching
     * never binds the parameters of the route being rendered.
     */
    public static function throughRedirect(string $href): string
    {
        $end = strcspn($href, '?#');
        $path = substr($href, 0, $end);
        $request = Request::create($path, 'GET');

        foreach (app('router')->getRoutes()->get('GET') as $route) {
            $destination = $route->defaults['destination'] ?? null;

            if (is_string($destination)
                && in_array((int) ($route->defaults['status'] ?? 0), [301, 308], true)
                && str_starts_with(ltrim($route->getActionName(), '\\'), RedirectController::class)
                && $route->matches($request)) {
                return $destination.substr($href, $end);
            }
        }

        return $href;
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
