<?php

namespace App\Support\Legacy;

use App\Models\Redirect;

/**
 * The reviewable map of every old URL, keyed by its normalised path (the
 * redirect table's lookup form). Written as `url-map.csv`; once corrected by
 * hand, that file is the single input to the import and to `legacy:verify`.
 */
final class UrlMap
{
    public const array COLUMNS = [
        'old_url', 'old_path', 'sources', 'object_type', 'object_sub_type', 'wp_id', 'wp_status',
        'noindex', 'title', 'meta_description', 'focus_keyword', 'canonical', 'published_at',
        'primary_category', 'article_count', 'content_source', 'has_elementor',
        'inbound_internal_links', 'images', 'spam_flags', 'decision', 'target_path', 'notes',
    ];

    /**
     * Where the WordPress rows come from: Yoast's index, the posts table
     * (attachment pages), the shapes WordPress answers on, and posts' old
     * addresses. A row with none of these was found only in an extra source.
     */
    public const array WORDPRESS_SOURCES = ['yoast', 'wordpress', 'wp-shape', 'wp-old-url'];

    public const string SITE_URL = 'https://www.webis.ro';

    /** @var array<string, array<string, string>> */
    private array $rows = [];

    /**
     * @param  array<string, string|int|null>  $values
     * @return array<string, string>
     */
    public static function row(array $values): array
    {
        $row = array_fill_keys(self::COLUMNS, '');

        foreach ($values as $column => $value) {
            if (array_key_exists($column, $row)) {
                $row[$column] = (string) $value;
            }
        }

        return $row;
    }

    /**
     * Adds a row unless its path is already mapped, in which case the first
     * row stays and records the second in its sources and notes.
     *
     * @param  array<string, string|int|null>  $values  must hold `old_url` or `old_path`
     */
    public function add(array $values): bool
    {
        $row = self::row($values);
        $row['old_path'] = Redirect::normalisePath($row['old_path'] !== '' ? $row['old_path'] : $row['old_url']);

        if ($row['old_url'] === '') {
            $row['old_url'] = self::SITE_URL.($row['old_path'] === '/' ? '/' : $row['old_path'].'/');
        }

        $existing = $this->rows[$row['old_path']] ?? null;

        if ($existing === null) {
            $this->rows[$row['old_path']] = $row;

            return true;
        }

        // A row backed by a WordPress object outranks one that isn't (the
        // front page's own row over Yoast's bare home-page entry).
        [$kept, $merged] = $existing['wp_id'] === '' && $row['wp_id'] !== '' ? [$row, $existing] : [$existing, $row];

        $kept['sources'] = self::joinSources($existing['sources'], $row['sources']);
        $kept['notes'] = trim($kept['notes'].' | Same path as '
            .trim("{$merged['object_type']}/{$merged['object_sub_type']}", '/').($merged['wp_id'] !== '' ? " #{$merged['wp_id']}" : ''), ' |');
        $this->rows[$row['old_path']] = $kept;

        return false;
    }

    /**
     * Merges the extra sources' URLs in. A known path only gains the source;
     * a new one becomes a row with no decision, since no WordPress row exists
     * to derive one from.
     *
     * @param  array<string, list<string>>  $sources  source label → URLs
     * @return int the number of URLs skipped as off-site, media or bot noise
     */
    public function mergeExtra(array $sources): int
    {
        $skipped = 0;

        foreach ($sources as $label => $urls) {
            foreach ($urls as $url) {
                if (! ExtraUrlSources::isSitePage($url)) {
                    $skipped++;

                    continue;
                }

                $path = Redirect::normalisePath($url);

                if (isset($this->rows[$path])) {
                    $this->rows[$path]['sources'] = self::joinSources($this->rows[$path]['sources'], $label);

                    continue;
                }

                $this->rows[$path] = self::row([
                    'old_url' => str_starts_with($url, '/') ? self::SITE_URL.$url : $url,
                    'old_path' => $path,
                    'sources' => $label,
                ]);
            }
        }

        foreach ($this->rows as $path => $row) {
            if (! array_intersect(explode('|', $row['sources']), self::WORDPRESS_SOURCES)) {
                $this->rows[$path]['decision'] = '';
                $this->rows[$path]['target_path'] = '';
                $this->rows[$path]['notes'] = $this->extraOnlyNote($path, $row['sources']);
            }
        }

        return $skipped;
    }

    /** @return array<string, array<string, string>> */
    public function rows(): array
    {
        return $this->rows;
    }

    /** @param array<string, string> $values */
    public function update(string $path, array $values): void
    {
        $path = Redirect::normalisePath($path);

        if (isset($this->rows[$path])) {
            $this->rows[$path] = [...$this->rows[$path], ...array_intersect_key($values, $this->rows[$path])];
        }
    }

    public function has(string $path): bool
    {
        return isset($this->rows[Redirect::normalisePath($path)]);
    }

    /** @param resource $handle */
    public function writeCsv($handle): void
    {
        fputcsv($handle, self::COLUMNS, ',', '"', '');

        foreach ($this->rows as $row) {
            fputcsv($handle, array_values($row), ',', '"', '');
        }
    }

    private function extraOnlyNote(string $path, string $sources): string
    {
        $note = 'Found only in '.str_replace('|', ', ', $sources).'. '.UrlClassifier::NEEDS_DECISION;

        foreach (UrlClassifier::wordpressShapes() as $shape) {
            if (str_starts_with($shape['notes'], 'Prefix row') && str_starts_with($path, $shape['old_path'].'/')) {
                $note .= " (covered by the prefix row {$shape['old_path']} → {$shape['target_path']})";
            }
        }

        return $note;
    }

    private static function joinSources(string $existing, string $added): string
    {
        $sources = array_filter([...explode('|', $existing), ...explode('|', $added)]);

        return implode('|', array_values(array_unique($sources)));
    }
}
