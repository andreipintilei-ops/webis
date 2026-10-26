<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\Seo\Sitemap;
use Illuminate\Http\Response;

/**
 * GET /sitemap.xml — every indexable public page (Support\Seo\Sitemap), from
 * the cache. robots.txt names it.
 */
class SitemapController extends Controller
{
    public function __invoke(Sitemap $sitemap): Response
    {
        return response($sitemap->cached(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
