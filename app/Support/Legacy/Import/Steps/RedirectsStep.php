<?php

namespace App\Support\Legacy\Import\Steps;

use App\Actions\Seo\ImportRedirects;
use App\Models\Asset;
use App\Models\Page;
use App\Models\PostCategory;
use App\Models\Project;
use App\Models\Redirect;
use App\Models\SlugHistory;
use App\Support\Legacy\Import\ImportContext;
use App\Support\Legacy\Import\ImportUrlResolver;
use App\Support\Legacy\Import\ReviewedMap;
use App\Support\TrailingSlash;

/**
 * The redirects CSV, in exactly the format the admin's import reads
 * (`sursa;destinatie;cod;tip`), written rather than inserted so it can be
 * counted and read before anything goes live; `--load-redirects` then loads
 * it through that same import (ImportRedirects).
 *
 * Rows: every `redirect` row to its target, every `gone` row as a 410, every
 * imported item whose target is a different address from its old one (an old
 * root category → /blog/categorie/{slug}/, `/12705-2` → its original slug),
 * and each old upload URL — original and resized copies — to its asset's file,
 * or 410 when it has none.
 *
 * Left out, and reported: a source SlugHistory already answers (it wins over
 * the table, so the row would be dead), a source that is a live address, a
 * duplicate, and — since the admin import checks neither — any loop or chain.
 *
 * Also writes `legacy-ids.json` (WordPress id → new address, for `/?p=123`).
 */
final class RedirectsStep
{
    public const string NAME = 'redirects';

    public function __construct(private readonly ImportRedirects $importRedirects) {}

    public function run(ImportContext $context): void
    {
        $report = $context->report;

        $rows = $this->dedupe($context, [...$this->mapRows($context), ...$this->imageRows($context)]);
        $rows = $this->withoutSlugHistory($context, $rows);
        $rows = $this->withoutLoopsAndChains($context, $rows);

        foreach ($rows as $row) {
            $report->count(self::NAME, "{$row['kind']}_{$row['code']}");
        }

        $report->count(self::NAME, 'rows_written', count($rows));

        $csv = $this->csv($rows);
        $context->write($context->outputDirectory.'/redirects.csv', $csv);
        $context->write($context->outputDirectory.'/legacy-ids.json', (string) json_encode((object) $this->legacyIds($context), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if ($context->loadRedirects) {
            ['saved' => $saved, 'skipped' => $skipped] = ($this->importRedirects)($csv);
            $report->count(self::NAME, 'loaded', $saved);
            $report->count(self::NAME, 'load_skipped', count($skipped));

            foreach ($skipped as $line) {
                $report->add(self::NAME, 'load_skipped_lines', $line);
            }
        }
    }

    /**
     * @return list<array{source: string, target: string|null, code: int, type: string, kind: string}>
     */
    private function mapRows(ImportContext $context): array
    {
        $rows = [];

        foreach ($context->map->rows() as $row) {
            $source = $row['old_path'];

            if ($row['decision'] === 'gone') {
                $rows[] = ['source' => $source, 'target' => null, 'code' => 410, 'type' => 'exact', 'kind' => 'gone'];
            } elseif ($row['decision'] === 'redirect') {
                if ($row['target_path'] === '') {
                    $context->report->add(self::NAME, 'no_target', $source);

                    continue;
                }

                $rows[] = [
                    'source' => $source,
                    'target' => TrailingSlash::append($row['target_path']),
                    'code' => 301,
                    // The shapes with a tail (/page/2/, /author/x/) cover it whole.
                    'type' => str_starts_with($row['notes'] ?? '', 'Prefix row') ? 'prefix' : 'exact',
                    'kind' => 'redirect',
                ];
            } elseif (in_array($row['decision'], ReviewedMap::CONTENT_DECISIONS, true) && ReviewedMap::targetDiffers($row)) {
                $rows[] = ['source' => $source, 'target' => TrailingSlash::append($row['target_path']), 'code' => 301, 'type' => 'exact', 'kind' => 'moved'];
            }
        }

        return $rows;
    }

    /**
     * Each old upload URL to the file of the asset holding its original; 410
     * for those with none.
     *
     * @return list<array{source: string, target: string|null, code: int, type: string, kind: string}>
     */
    private function imageRows(ImportContext $context): array
    {
        $map = $context->media();
        $paths = $this->assetPaths(array_values(array_unique($map->all())));
        $rows = [];

        foreach ($context->mediaSource() as $attachment) {
            $urls = [(string) ($attachment['url'] ?? ''), ...array_map(fn (mixed $d): string => (string) ($d['url'] ?? ''), (array) ($attachment['derivatives'] ?? []))];

            foreach (array_filter($urls) as $url) {
                $assetId = $map->assetId($url);
                $target = $assetId !== null ? ($paths[$assetId] ?? null) : null;

                if ($assetId !== null && $target === null) {
                    $context->report->add(self::NAME, 'asset_without_file', ['url' => $url, 'asset' => $assetId]);
                }

                $rows[] = $target !== null
                    ? ['source' => Redirect::normalisePath($url), 'target' => $target, 'code' => 301, 'type' => 'exact', 'kind' => 'image']
                    : ['source' => Redirect::normalisePath($url), 'target' => null, 'code' => 410, 'type' => 'exact', 'kind' => 'image'];
            }
        }

        return $rows;
    }

    /**
     * Each asset's file as a site path (`/storage/12/x.jpg`).
     *
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    private function assetPaths(array $ids): array
    {
        $paths = [];

        foreach (array_chunk($ids, 500) as $chunk) {
            Asset::query()->with('media')->whereIn('id', $chunk)->get()->each(function (Asset $asset) use (&$paths): void {
                $url = $asset->file()?->getUrl();
                $path = $url !== null ? (string) parse_url($url, PHP_URL_PATH) : '';

                if ($path !== '') {
                    $paths[$asset->id] = $path;
                }
            });
        }

        return $paths;
    }

    /**
     * One row per source (the first); a source that is a live address of the
     * new site is dropped, since a live page never reaches the table.
     *
     * @param  list<array{source: string, target: string|null, code: int, type: string, kind: string}>  $rows
     * @return list<array{source: string, target: string|null, code: int, type: string, kind: string}>
     */
    private function dedupe(ImportContext $context, array $rows): array
    {
        $live = [];

        foreach ($context->map->rows() as $row) {
            if (in_array($row['decision'], [...ReviewedMap::CONTENT_DECISIONS, 'skip'], true) && $row['target_path'] !== '') {
                $live[Redirect::normalisePath($row['target_path'])] = true;
            }
        }

        $kept = [];

        foreach ($rows as $row) {
            if ($row['source'] === '/') {
                $context->report->add(self::NAME, 'dropped_root', $row);
            } elseif (isset($kept[$row['source']])) {
                $context->report->count(self::NAME, 'dropped_duplicate');
                $context->report->add(self::NAME, 'dropped_duplicate', $row);
            } elseif (isset($live[$row['source']])) {
                $context->report->count(self::NAME, 'dropped_source_live');
                $context->report->add(self::NAME, 'dropped_source_live', $row);
            } else {
                $kept[$row['source']] = $row;
            }
        }

        return array_values($kept);
    }

    /**
     * Rows whose source a renamed item's SlugHistory already answers.
     *
     * @param  list<array{source: string, target: string|null, code: int, type: string, kind: string}>  $rows
     * @return list<array{source: string, target: string|null, code: int, type: string, kind: string}>
     */
    private function withoutSlugHistory(ImportContext $context, array $rows): array
    {
        $history = SlugHistory::query()->get(['sluggable_type', 'old_slug'])
            ->mapWithKeys(fn (SlugHistory $h): array => ["{$h->sluggable_type}|{$h->old_slug}" => true])
            ->all();

        if ($history === []) {
            return $rows;
        }

        $types = [
            'page' => (new Page)->getMorphClass(),
            'project' => (new Project)->getMorphClass(),
            'category' => (new PostCategory)->getMorphClass(),
        ];

        return array_values(array_filter($rows, function (array $row) use ($context, $history, $types): bool {
            $key = match (true) {
                preg_match('#^/proiect/([a-z0-9-]+)$#', $row['source'], $m) === 1 => "{$types['project']}|{$m[1]}",
                preg_match('#^/blog/categorie/([a-z0-9-]+)$#', $row['source'], $m) === 1 => "{$types['category']}|{$m[1]}",
                preg_match('#^/(?:servicii/)?([a-z0-9-]+)$#', $row['source'], $m) === 1 => "{$types['page']}|{$m[1]}",
                default => null,
            };

            if ($key !== null && isset($history[$key])) {
                $context->report->count(self::NAME, 'excluded_slug_history');
                $context->report->add(self::NAME, 'excluded_slug_history', $row);

                return false;
            }

            return true;
        }));
    }

    /**
     * Drops every 301 whose target is itself a source here — a chain (two
     * hops) or, if following it comes back round, a loop. The admin import
     * checks neither, so this is the only guard.
     *
     * @param  list<array{source: string, target: string|null, code: int, type: string, kind: string}>  $rows
     * @return list<array{source: string, target: string|null, code: int, type: string, kind: string}>
     */
    private function withoutLoopsAndChains(ImportContext $context, array $rows): array
    {
        $bySource = [];
        $prefixes = [];

        foreach ($rows as $row) {
            $bySource[$row['source']] = $row;

            if ($row['type'] === 'prefix') {
                $prefixes[] = $row['source'];
            }
        }

        $next = function (string $path) use ($bySource, $prefixes): ?string {
            if (isset($bySource[$path])) {
                return $path;
            }

            foreach ($prefixes as $prefix) {
                if (str_starts_with($path, $prefix.'/')) {
                    return $prefix;
                }
            }

            return null;
        };

        $kept = [];

        foreach ($rows as $row) {
            if ($row['target'] === null || ! self::isSitePath($row['target'])) {
                $kept[] = $row;

                continue;
            }

            $hop = $next(Redirect::normalisePath($row['target']));

            if ($hop === null) {
                $kept[] = $row;

                continue;
            }

            // Follow it, to tell a loop from a chain.
            $walk = [$row['source']];
            $loop = false;

            while ($hop !== null) {
                if (in_array($hop, $walk, true)) {
                    $loop = true;

                    break;
                }

                $walk[] = $hop;
                $target = $bySource[$hop]['target'] ?? null;
                $hop = $target !== null && self::isSitePath($target) ? $next(Redirect::normalisePath($target)) : null;
            }

            $kind = $loop ? 'loop' : 'chain';
            $context->report->count(self::NAME, "dropped_{$kind}");
            $context->report->add(self::NAME, "dropped_{$kind}", [...$row, 'walk' => $walk]);
        }

        return $kept;
    }

    /** A target on this site: a root-relative path or an absolute webis.ro URL. */
    private static function isSitePath(string $target): bool
    {
        if (str_starts_with($target, '/') && ! str_starts_with($target, '//')) {
            return true;
        }

        $host = strtolower((string) parse_url($target, PHP_URL_HOST));

        return in_array($host, ['webis.ro', 'www.webis.ro'], true);
    }

    /**
     * @param  list<array{source: string, target: string|null, code: int, type: string, kind: string}>  $rows
     */
    private function csv(array $rows): string
    {
        $out = fopen('php://temp', 'w+');

        if ($out === false) {
            return '';
        }

        fputcsv($out, ['sursa', 'destinatie', 'cod', 'tip'], ';', '"', '');

        foreach ($rows as $row) {
            fputcsv($out, [$row['source'], $row['target'] ?? '', $row['code'], $row['type']], ';', '"', '');
        }

        rewind($out);
        $csv = (string) stream_get_contents($out);
        fclose($out);

        return $csv;
    }

    /**
     * WordPress post id → its new address, for `/?p=123` links, from the
     * reviewed map: content rows by their own target, a moved post's old
     * addresses never overriding it. Gone rows have none.
     *
     * @return array<int, string>
     */
    private function legacyIds(ImportContext $context): array
    {
        $ids = ImportUrlResolver::fromMap($context->map)->ids();
        ksort($ids);

        return $ids;
    }
}
