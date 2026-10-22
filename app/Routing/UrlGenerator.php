<?php

namespace App\Routing;

use App\Support\TrailingSlash;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollectionInterface;
use Illuminate\Routing\UrlGenerator as BaseUrlGenerator;

/**
 * Laravel's URL generator, emitting the public pages' trailing slash
 * (TrailingSlash): route('posts.index') is /blog/, url('/contact') is
 * /contact/, route('projects.index', ['tip' => 'magazin']) is
 * /portofoliu/?tip=magazin. Laravel's own helpers always trim the slash, so
 * without this every internal link would take a 301 on each click.
 *
 * Everything else comes out exactly as Laravel makes it: the admin, account
 * and settings routes, non-GET routes (form actions such as cerere-oferta),
 * files, the root, external URLs, and signed URLs, which are signed over the
 * slash-less form that the signature check rebuilds from the request.
 */
class UrlGenerator extends BaseUrlGenerator
{
    /** The route Laravel builds to generate a Route::redirect() destination. */
    private const string REDIRECT_DESTINATION = 'laravel_route_redirect_destination';

    /** Above zero while a signed URL is generated: no slash then. */
    private int $signing = 0;

    /** @var array<string, bool> path → whether a public page answers it */
    private array $pagePaths = [];

    /** @var list<Route>|null */
    private ?array $getRoutes = null;

    /**
     * This generator in place of the framework's, with the same state: the
     * routes, the request and the resolvers its service provider has set.
     */
    public static function from(BaseUrlGenerator $url): self
    {
        $generator = new self($url->routes, $url->request, $url->assetRoot);

        foreach (get_object_vars($url) as $property => $value) {
            $generator->{$property} = $value;
        }

        // Built lazily around its generator: let it be built around this one.
        $generator->routeGenerator = null;

        return $generator;
    }

    public function to($path, $extra = [], $secure = null)
    {
        $url = parent::to($path, $extra, $secure);

        return ! $this->isValidUrl($path) && $this->isPagePath($url) ? TrailingSlash::append($url) : $url;
    }

    public function toRoute($route, $parameters, $absolute)
    {
        $url = parent::toRoute($route, $parameters, $absolute);

        if ($this->signing > 0) {
            return $url;
        }

        // Route::redirect('proiect', '/portofoliu') generates its destination
        // through a throwaway route: a page if its path is one.
        $isPage = $route->getName() === self::REDIRECT_DESTINATION
            ? $this->isPagePath($url)
            : TrailingSlash::isPage($route);

        return $isPage ? TrailingSlash::append($url) : $url;
    }

    public function signedRoute($name, $parameters = [], $expiration = null, $absolute = true)
    {
        $this->signing++;

        try {
            return parent::signedRoute($name, $parameters, $expiration, $absolute);
        } finally {
            $this->signing--;
        }
    }

    public function setRoutes(RouteCollectionInterface $routes)
    {
        $this->pagePaths = [];
        $this->getRoutes = null;

        return parent::setRoutes($routes);
    }

    /**
     * Whether a URL on this site is answered by a public page route. Matched
     * route by route, as the router does, but without binding: the router's
     * own match() would bind the path's parameters onto the shared route
     * object — the route of the page being rendered.
     */
    private function isPagePath(string $url): bool
    {
        $base = substr($url, 0, strcspn($url, '?#'));
        $host = parse_url($base, PHP_URL_HOST);

        if (is_string($host) && $host !== parse_url($this->formatRoot($this->formatScheme()), PHP_URL_HOST)) {
            return false;
        }

        $path = '/'.ltrim((string) parse_url($base, PHP_URL_PATH), '/');

        return $this->pagePaths[$path] ??= (function () use ($path): bool {
            $request = Request::create($path, 'GET');

            foreach ($this->getRoutes() as $route) {
                if ($route->matches($request)) {
                    return TrailingSlash::isPage($route);
                }
            }

            return false;
        })();
    }

    /**
     * The GET routes in the order the router tries them: fallbacks last.
     *
     * @return list<Route>
     */
    private function getRoutes(): array
    {
        if ($this->getRoutes === null) {
            [$routes, $fallbacks] = collect($this->routes->get('GET'))
                ->partition(fn (Route $route) => ! $route->isFallback);

            $this->getRoutes = $routes->merge($fallbacks)->values()->all();
        }

        return $this->getRoutes;
    }
}
