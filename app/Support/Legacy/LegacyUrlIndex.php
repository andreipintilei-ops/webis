<?php

namespace App\Support\Legacy;

use App\Models\Redirect;

/**
 * Resolves links to the old site through the reviewed URL map: an old path
 * lands on its row's `target_path`, and `/?p=123` (or `?page_id=`) on the
 * post's path from `legacy-ids.json`. Rows answered with 410, or still
 * waiting for a decision, have no target.
 */
final class LegacyUrlIndex implements UrlResolver
{
    /** Hosts the old site answered on. */
    public const array HOSTS = ['webis.ro', 'www.webis.ro'];

    /**
     * @param  array<string, string>  $targets  normalised old path → target path
     * @param  array<int, string>  $legacyIds  WordPress post id → target path
     */
    public function __construct(
        private readonly array $targets,
        private readonly array $legacyIds = [],
    ) {}

    /**
     * Reads `url-map.csv` and, when present, `legacy-ids.json` from $directory.
     */
    public static function fromDirectory(string $directory): self
    {
        $targets = [];
        $handle = fopen("{$directory}/url-map.csv", 'r');

        if ($handle === false) {
            return new self([]);
        }

        $header = fgetcsv($handle, null, ',', '"', '') ?: [];

        while (($values = fgetcsv($handle, null, ',', '"', '')) !== false) {
            if (count($values) !== count($header)) {
                continue;
            }

            $row = array_combine($header, $values);

            if ($row['decision'] !== '' && $row['decision'] !== 'gone' && $row['target_path'] !== '') {
                $targets[Redirect::normalisePath($row['old_path'])] = $row['target_path'];
            }
        }

        fclose($handle);

        $idsFile = "{$directory}/legacy-ids.json";
        $legacyIds = is_file($idsFile) ? (array) json_decode((string) file_get_contents($idsFile), true) : [];

        return new self($targets, array_map(strval(...), $legacyIds));
    }

    /**
     * Whether $url points at the old site: one of its hosts, or a
     * site-relative path.
     */
    public static function isInternal(string $url): bool
    {
        $url = trim($url);

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST));

        return preg_match('~^https?://~i', $url) === 1 && in_array($host, self::HOSTS, true);
    }

    public function resolve(string $url): ?string
    {
        $parts = parse_url(trim($url));

        if ($parts === false) {
            return null;
        }

        $fragment = isset($parts['fragment']) && $parts['fragment'] !== '' ? '#'.$parts['fragment'] : '';
        parse_str($parts['query'] ?? '', $query);

        foreach (['p', 'page_id'] as $key) {
            if (is_string($query[$key] ?? null) && ctype_digit($query[$key])) {
                $target = $this->legacyIds[(int) $query[$key]] ?? null;

                return $target === null ? null : $target.$fragment;
            }
        }

        $target = $this->targets[Redirect::normalisePath($parts['path'] ?? '/')] ?? null;

        return $target === null ? null : $target.$fragment;
    }
}
