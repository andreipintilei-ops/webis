<?php

namespace App\Actions\Seo;

use App\Enums\RedirectMatchType;
use App\Models\Redirect;

/**
 * The redirects-table row that answers a 404ing path: an exact match first,
 * then the longest matching prefix. A prefix row `/tag` covers `/tag` and
 * everything under it (`/tag/seo`), never a sibling like `/tagged`.
 *
 * One indexed query: the path and its ancestors (`/a/b/c`, `/a/b`, `/a`) go
 * into a whereIn on the unique `source_path`, and the few rows that come back
 * are ranked here.
 */
class ResolveRedirect
{
    /** How many leading segments are tried as prefixes; deeper paths are junk. */
    private const int MAX_SEGMENTS = 10;

    /**
     * @param  string  $path  a path normalised by Redirect::normalisePath()
     * @param  string|null  $host  the request's host, so an absolute target on
     *                             another domain never reads as a loop
     */
    public function __invoke(string $path, ?string $host = null): ?Redirect
    {
        $rows = Redirect::query()->whereIn('source_path', $this->candidates($path))->get();

        $hit = $rows->first(fn (Redirect $row) => $row->match_type === RedirectMatchType::Exact && $row->source_path === $path)
            ?? $rows->where('match_type', RedirectMatchType::Prefix)
                ->sortByDesc(fn (Redirect $row) => strlen($row->source_path))
                ->first();

        if ($hit === null || $this->loops($hit, $path, $host)) {
            return null;
        }

        return $hit;
    }

    /**
     * Counts a redirect that fired, after the response is sent. Through the
     * query builder, so `updated_at` keeps meaning "a person edited this row".
     */
    public function recordHit(int $id): void
    {
        defer(fn () => Redirect::query()->toBase()
            ->where('id', $id)
            ->increment('hits', 1, ['last_hit_at' => now()]));
    }

    /**
     * The path itself, then its ancestors up to MAX_SEGMENTS deep.
     *
     * @return list<string>
     */
    private function candidates(string $path): array
    {
        $segments = array_slice(explode('/', trim($path, '/')), 0, self::MAX_SEGMENTS);
        $candidates = [$path];

        for ($depth = count($segments); $depth > 0; $depth--) {
            $candidates[] = '/'.implode('/', array_slice($segments, 0, $depth));
        }

        return array_values(array_unique($candidates));
    }

    /**
     * A row whose target is the very path asked for would redirect to itself
     * forever. The admin CSV import doesn't check for that, so it's caught here.
     */
    private function loops(Redirect $hit, string $path, ?string $host): bool
    {
        if ($hit->target === null) {
            return false;
        }

        $targetHost = parse_url($hit->target, PHP_URL_HOST);

        if (is_string($targetHost) && $host !== null && strcasecmp($targetHost, $host) !== 0) {
            return false;
        }

        return Redirect::normalisePath($hit->target) === $path;
    }
}
