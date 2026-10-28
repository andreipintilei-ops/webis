<?php

namespace App\Support\Legacy\Import;

use App\Support\Legacy\MediaResolver;

/**
 * The import's media resolver: old upload URLs and WordPress attachment ids
 * to the real assets the media step created, through `media-map.json`.
 * (The preview's LegacyMediaIndex answers with placeholder ids instead.)
 *
 * A resized copy WordPress never recorded — an Elementor crop such as
 * `x-600x400.jpg` — still names its original, so it resolves to the
 * original's asset.
 */
final class ImportMediaResolver implements MediaResolver
{
    /** @var array<int, string> attachment id → original URL */
    private array $attachments = [];

    /**
     * @param  list<array<string, mixed>>  $mediaSource  the decoded `media-source.json`
     */
    public function __construct(private readonly MediaMap $map, array $mediaSource)
    {
        foreach ($mediaSource as $attachment) {
            $id = (int) ($attachment['wp_id'] ?? 0);

            if ($id > 0 && ($attachment['url'] ?? '') !== '') {
                $this->attachments[$id] = (string) $attachment['url'];
            }
        }
    }

    public function assetIdForUrl(string $url): ?int
    {
        $url = str_replace('\\/', '/', trim($url));

        if (stripos((string) parse_url($url, PHP_URL_PATH), '/wp-content/uploads/') === false) {
            return null;
        }

        return $this->map->assetId($url)
            ?? $this->map->assetId((string) preg_replace('/-\d+x\d+(?=\.[a-z0-9]+(?:$|[?#]))/i', '', $url));
    }

    public function assetIdForAttachment(int $attachmentId): ?int
    {
        $url = $this->attachments[$attachmentId] ?? null;

        return $url === null ? null : $this->map->assetId($url);
    }
}
