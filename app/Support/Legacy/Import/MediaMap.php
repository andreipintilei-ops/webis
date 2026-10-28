<?php

namespace App\Support\Legacy\Import;

use Illuminate\Support\Facades\File;

/**
 * Every old upload URL — each original and each of its WordPress-resized
 * copies — mapped to the one asset that now holds the original. Persisted as
 * `media-map.json` (`{"https://www.webis.ro/wp-content/uploads/…": 12}`), so
 * re-runs reuse it instead of re-uploading, and the content converters resolve
 * `<img src>` through it.
 *
 * Lookups ignore scheme, host, `www.`, a query string and case, so a link
 * written as `http://webis.ro/wp-content/…` or `/wp-content/…` still resolves.
 */
final class MediaMap
{
    /** @var array<string, int> url → asset id, as persisted */
    private array $urls = [];

    /** @var array<string, int> lookup key → asset id */
    private array $keys = [];

    /** @param array<string, int> $urls */
    public function __construct(array $urls = [])
    {
        foreach ($urls as $url => $assetId) {
            $this->put((string) $url, (int) $assetId);
        }
    }

    public static function load(string $path): self
    {
        if (! is_file($path)) {
            return new self;
        }

        $data = json_decode((string) File::get($path), true);

        return new self(is_array($data) ? array_filter($data, is_numeric(...)) : []);
    }

    public function save(string $path): void
    {
        File::ensureDirectoryExists(dirname($path));
        ksort($this->urls);
        File::put($path, (string) json_encode($this->urls, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public function put(string $url, int $assetId): void
    {
        $this->urls[$url] = $assetId;
        $this->keys[self::key($url)] = $assetId;
    }

    /** Drops every URL pointing at the asset (it no longer exists). */
    public function forget(int $assetId): void
    {
        $this->urls = array_filter($this->urls, fn (int $id): bool => $id !== $assetId);
        $this->keys = array_filter($this->keys, fn (int $id): bool => $id !== $assetId);
    }

    public function assetId(string $url): ?int
    {
        return $this->keys[self::key($url)] ?? null;
    }

    public function has(string $url): bool
    {
        return $this->assetId($url) !== null;
    }

    /** @return array<string, int> */
    public function all(): array
    {
        return $this->urls;
    }

    public function count(): int
    {
        return count($this->urls);
    }

    /** `/wp-content/uploads/2024/03/x.jpg`, lowercased and decoded. */
    public static function key(string $url): string
    {
        $path = (string) (parse_url(trim($url), PHP_URL_PATH) ?? '');

        return mb_strtolower('/'.ltrim(rawurldecode($path), '/'));
    }
}
