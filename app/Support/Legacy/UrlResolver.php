<?php

namespace App\Support\Legacy;

/**
 * Where a link to the old site lands on the new one: the final target, in
 * the canonical trailing-slash form, so links never go through a redirect.
 */
interface UrlResolver
{
    /**
     * @param  string  $url  an internal link: `https://www.webis.ro/x/`, `/x`, `/?p=123` …
     * @return string|null the target path (with the link's #fragment, if any), or null when the old URL has none
     */
    public function resolve(string $url): ?string;
}
