<?php

namespace App\Support\Legacy;

/**
 * The URL lists that know what Yoast cannot: pages deleted from WordPress
 * that still get traffic or links. Each is an optional export dropped into the
 * legacy directory by hand: the old server's access log, the Wayback Machine's
 * CDX list, and a backlink export (Ahrefs or Bing Webmaster Tools).
 */
final class ExtraUrlSources
{
    /** Source label → file name in the legacy directory. */
    public const array FILES = [
        'access-log' => 'access-log.csv',
        'wayback' => 'wayback.txt',
        'backlinks' => 'backlinks.csv',
    ];

    /** Hosts the old site answered on. */
    public const array SITE_HOSTS = ['webis.ro', 'www.webis.ro'];

    /** Column headings that name the site's own URL, best first. */
    private const array URL_HEADINGS = ['target url', 'target', 'url', 'page url', 'page', 'landing page', 'path', 'request', 'request uri', 'uri'];

    /**
     * The URLs of every source file present; absent files are skipped.
     *
     * @return array<string, list<string>> source label → URLs
     */
    public static function read(string $directory): array
    {
        $sources = [];

        foreach (self::FILES as $label => $file) {
            $path = rtrim($directory, '/\\').DIRECTORY_SEPARATOR.$file;

            if (! is_file($path)) {
                continue;
            }

            $contents = (string) file_get_contents($path);
            $sources[$label] = str_ends_with($file, '.csv') ? self::fromCsv($contents) : self::fromText($contents);
        }

        return $sources;
    }

    /**
     * One URL per line, as the Wayback CDX API returns with `fl=original`.
     *
     * @return list<string>
     */
    public static function fromText(string $contents): array
    {
        $urls = [];

        foreach (preg_split('/\R/', $contents) ?: [] as $line) {
            $line = trim($line);

            if ($line !== '' && ! str_starts_with($line, '#') && ($url = self::urlIn($line)) !== null) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    /**
     * A CSV export of unknown shape: the column with a recognised heading if
     * there is one, otherwise the first cell of each row that looks like a URL.
     *
     * @return list<string>
     */
    public static function fromCsv(string $contents): array
    {
        $contents = (string) preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        $lines = array_values(array_filter(preg_split('/\R/', $contents) ?: [], fn (string $line): bool => trim($line) !== ''));

        if ($lines === []) {
            return [];
        }

        $delimiter = self::delimiter($lines[0]);
        $rows = array_map(fn (string $line): array => str_getcsv($line, $delimiter, '"', ''), $lines);
        $column = self::urlColumn($rows[0]);

        if ($column !== null) {
            array_shift($rows);
        }

        $urls = [];

        foreach ($rows as $row) {
            $cells = $column !== null ? [$row[$column] ?? ''] : $row;

            foreach ($cells as $cell) {
                if (($url = self::urlIn((string) $cell)) !== null) {
                    $urls[] = $url;

                    break;
                }
            }
        }

        return $urls;
    }

    /**
     * The page paths in MonsterInsights' cached Google Analytics reports (the
     * `monsterinsights_report_data_*` options): the only traffic data inside
     * the dump. A `/404.html?page=…` entry is a request that 404'd, so it
     * stands for the page it asked for.
     *
     * @param  array<int|string, mixed>  $report  one unserialised option value
     * @return list<string>
     */
    public static function fromAnalytics(array $report): array
    {
        $urls = [];

        array_walk_recursive($report, function (mixed $value, int|string $key) use (&$urls): void {
            if ($key !== 'url' || ! is_string($value) || ! str_starts_with($value, '/')) {
                return;
            }

            $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5);

            if (str_starts_with($value, '/404.html')) {
                parse_str((string) parse_url($value, PHP_URL_QUERY), $query);
                $value = is_string($query['page'] ?? null) ? $query['page'] : '';
            }

            if (($url = self::urlIn($value)) !== null) {
                $urls[] = $url;
            }
        });

        return $urls;
    }

    /**
     * Whether a URL is one of the old site's pages, rather than another site,
     * a media file (the media inventory covers those) or bot noise.
     */
    public static function isSitePage(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (is_string($host) && ! in_array(strtolower($host), self::SITE_HOSTS, true)) {
            return false;
        }

        $path = strtolower((string) parse_url($url, PHP_URL_PATH));

        return ! preg_match('#^/(wp-admin|wp-includes|wp-content|wp-json|cgi-bin)(/|$)|/\.|\.[a-z0-9]{2,5}$#', $path);
    }

    /** The URL or path a cell holds, unwrapping a logged request line (`GET /x HTTP/1.1`). */
    private static function urlIn(string $cell): ?string
    {
        $cell = trim($cell);

        if (preg_match('#^(?:GET|HEAD|POST)\s+(\S+)#i', $cell, $m)) {
            $cell = $m[1];
        }

        return preg_match('#^(https?://\S+|/\S*)$#i', $cell) ? $cell : null;
    }

    /** @param list<string|null> $heading */
    private static function urlColumn(array $heading): ?int
    {
        $names = array_map(fn (?string $name): string => strtolower(trim((string) $name)), $heading);

        foreach ($names as $i => $name) {
            if (str_contains($name, 'target')) {
                return $i;
            }
        }

        foreach (self::URL_HEADINGS as $wanted) {
            $index = array_search($wanted, $names, true);

            if ($index !== false) {
                return $index;
            }
        }

        return null;
    }

    private static function delimiter(string $line): string
    {
        $counts = ['comma' => substr_count($line, ','), 'semicolon' => substr_count($line, ';'), 'tab' => substr_count($line, "\t")];
        arsort($counts);

        return ['comma' => ',', 'semicolon' => ';', 'tab' => "\t"][array_key_first($counts)];
    }
}
