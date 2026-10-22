<?php

namespace App\Support\Legacy\Import;

use App\Models\Redirect;
use App\Support\Legacy\UrlResolver;
use App\Support\TrailingSlash;

/**
 * The import's link resolver: an old address to its final target from the
 * **reviewed** map (`url-map-reviewed.csv`, never the generated one), in the
 * trailing-slash form, so imported bodies never link through a redirect.
 * `/?p=123` and `/?page_id=45` go by the post's id. Gone and undecided rows
 * have no target: the converter keeps the link's text and drops the link.
 */
final class ImportUrlResolver implements UrlResolver
{
    /**
     * @param  array<string, string>  $targets  normalised old path → final target
     * @param  array<int, string>  $ids  WordPress post id → final target
     */
    public function __construct(
        private readonly array $targets,
        private readonly array $ids = [],
    ) {}

    public static function fromMap(ReviewedMap $map): self
    {
        $targets = [];
        $ids = [];

        foreach ($map->rows() as $row) {
            if ($row['target_path'] === '' || in_array($row['decision'], ['', 'gone'], true)) {
                continue;
            }

            $target = TrailingSlash::append($row['target_path']);
            $targets[$row['old_path']] = $target;
            $id = (int) ($row['wp_id'] ?? 0);

            // A post's own row wins over the rows of its old addresses.
            if ($id > 0 && ($row['object_type'] ?? '') === 'post' && (! isset($ids[$id]) || ! str_contains($row['sources'] ?? '', 'wp-old-url'))) {
                $ids[$id] = $target;
            }
        }

        return new self($targets, $ids);
    }

    /** @return array<int, string> */
    public function ids(): array
    {
        return $this->ids;
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
                $target = $this->ids[(int) $query[$key]] ?? null;

                return $target === null ? null : $target.$fragment;
            }
        }

        $target = $this->targets[Redirect::normalisePath($parts['path'] ?? '/')] ?? null;

        return $target === null ? null : $target.$fragment;
    }
}
