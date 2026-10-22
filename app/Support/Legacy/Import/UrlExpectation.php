<?php

namespace App\Support\Legacy\Import;

use App\Support\TrailingSlash;

/**
 * What one old URL must answer on the new site, by its reviewed decision —
 * not a blanket "200 or one 301", which would pass a redirect where a 410 was
 * meant and fail every deliberate 410:
 *
 *   gone                                    → 410
 *   redirect, or content whose target is
 *   another address (an old root category,
 *   `/12705-2`, `/proiect` → /portofoliu/)  → a single 301 to its target, which answers 200
 *   anything else                           → 200
 */
final readonly class UrlExpectation
{
    private function __construct(
        public int $status,
        public ?string $target = null,
    ) {}

    /** @param array<string, string> $row */
    public static function for(array $row): self
    {
        if ($row['decision'] === 'gone') {
            return new self(410);
        }

        if ($row['decision'] === 'redirect' || ReviewedMap::targetDiffers($row)) {
            return new self(301, TrailingSlash::append($row['target_path']));
        }

        return new self(200);
    }

    /**
     * The address requested: the path as Google has it, with its trailing
     * slash.
     *
     * @param  array<string, string>  $row
     */
    public static function requestPath(array $row): string
    {
        return $row['old_path'] === '/' ? '/' : TrailingSlash::append($row['old_path']);
    }

    /**
     * Whether a Location header names the expected target: same path (with
     * its slash) and query, on this site.
     */
    public function locationMatches(string $location, string $base): bool
    {
        if ($this->target === null) {
            return false;
        }

        return self::comparable($location, $base) === self::comparable($this->target, $base);
    }

    private static function comparable(string $url, string $base): string
    {
        $parts = parse_url($url);
        $baseHost = strtolower((string) parse_url($base, PHP_URL_HOST));
        $host = strtolower((string) ($parts['host'] ?? $baseHost));

        if ($host !== $baseHost) {
            return $url; // elsewhere: only an identical URL matches
        }

        $path = (string) ($parts['path'] ?? '/');

        return rawurldecode($path).(isset($parts['query']) ? '?'.$parts['query'] : '');
    }
}
