<?php

namespace App\Listeners;

use App\Events\ContentPublished;
use App\Support\Seo\Sitemap;

/**
 * Something just went live (from the admin or the scheduler): the cached
 * sitemap is rebuilt on its next request, with it.
 */
class ForgetSitemap
{
    public function handle(ContentPublished $event): void
    {
        Sitemap::forget();
    }
}
