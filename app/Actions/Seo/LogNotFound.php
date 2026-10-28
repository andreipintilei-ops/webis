<?php

namespace App\Actions\Seo;

use App\Models\NotFoundLog;
use Illuminate\Http\Request;

/**
 * Counts a 404 in `not_found_logs`: one row per path, with its running hit
 * count and the last referrer and user agent (admin/erori-404 lists them, the
 * frequent real ones become redirects). Scanner noise is never written, or the
 * table becomes a log of every exploit probe on the internet.
 */
class LogNotFound
{
    /** Substrings that mark a probe for files a Laravel site never serves. */
    private const array NOISE_FRAGMENTS = ['.php', '.env', '.git', '.asp', '.aspx', '.cgi', '.bak', '.sql'];

    /** WordPress internals: probed constantly, and never real pages. */
    private const array NOISE_PREFIXES = ['/wp-content', '/wp-includes', '/wp-login', '/wp-admin', '/wp-json', '/xmlrpc'];

    /** Length of the referrer and user-agent columns. */
    private const int MAX_LENGTH = 512;

    /**
     * @param  string  $path  a path normalised by Redirect::normalisePath(), so
     *                        it compares directly with a redirect's source_path
     */
    public function __invoke(Request $request, string $path): void
    {
        if ($this->isNoise($path)) {
            return;
        }

        $seen = [
            'last_seen_at' => now(),
            'last_referrer' => $this->truncate($request->headers->get('referer')),
            'last_user_agent' => $this->truncate($request->userAgent()),
        ];

        if ($this->increment($path, $seen) > 0) {
            return;
        }

        // Two first hits at once: the loser of the unique index counts as a repeat.
        rescue(
            fn () => NotFoundLog::create(['path' => $path, 'hits' => 1, ...$seen]),
            fn () => $this->increment($path, $seen),
            report: false,
        );
    }

    /**
     * Bumps an existing row; `is_ignored` is left as it was.
     *
     * @param  array<string, mixed>  $seen
     */
    private function increment(string $path, array $seen): int
    {
        return NotFoundLog::query()->toBase()
            ->where('path', $path)
            ->increment('hits', 1, [...$seen, 'updated_at' => now()]);
    }

    private function isNoise(string $path): bool
    {
        if (preg_match('#^/[a-z0-9\-/_.]{0,255}$#i', $path) !== 1) {
            return true;
        }

        foreach (self::NOISE_FRAGMENTS as $fragment) {
            if (str_contains($path, $fragment)) {
                return true;
            }
        }

        foreach (self::NOISE_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function truncate(?string $value): ?string
    {
        return $value === null || $value === '' ? null : mb_substr($value, 0, self::MAX_LENGTH);
    }
}
