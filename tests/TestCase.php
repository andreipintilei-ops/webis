<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Uri;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /** Whether requests go out exactly as written (asWritten()). */
    private bool $urlsAsWritten = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Page tests assert on the response, not on built assets. Without this
        // every new Inertia page fails until `npm run build` has been re-run.
        $this->withoutVite();
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }

    /**
     * Requests from here on go out exactly as written: `get('/blog')` asks for
     * /blog and `get('/blog/')` for /blog/. For tests of the trailing slash
     * itself (CanonicalTrailingSlash).
     */
    protected function asWritten(): static
    {
        $this->urlsAsWritten = true;

        return $this;
    }

    /**
     * Laravel's test client trims every trailing slash, so `get('/blog/')`
     * would ask for /blog, which now 301s. Instead a path is asked for in the
     * form the site's own links give it, through the URL generator: a public
     * page with its slash (`get('/blog')` asks for /blog/), anything else as
     * before. A full URL goes out as it is.
     *
     * @param  Uri|string  $uri
     * @return string
     */
    protected function prepareUrlForRequest($uri)
    {
        $uri = $uri instanceof Uri ? $uri->value() : (string) $uri;

        if ($this->app['url']->isValidUrl($uri)) {
            return $uri;
        }

        return $this->urlsAsWritten
            ? rtrim(url('/'), '/').'/'.ltrim($uri, '/')
            : url(ltrim($uri, '/'));
    }
}
