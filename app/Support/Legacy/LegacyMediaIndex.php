<?php

namespace App\Support\Legacy;

/**
 * The preview's media resolver, read from `media-source.json`: an image
 * resolves when its attachment's original is in the uploads archive, and
 * its "asset id" is a placeholder — the WordPress attachment id. Real asset
 * ids exist only once the import has copied the files into the library.
 *
 * URLs match on their path under `wp-content/uploads/`, ignoring the host,
 * so a resized copy (`-300x175`) or an `-scaled` original resolves to its
 * attachment.
 */
final class LegacyMediaIndex implements MediaResolver
{
    /**
     * @param  array<string, int>  $files  lower-cased uploads-relative file → attachment id
     * @param  array<int, bool>  $available  attachment id → whether its original is in the archive
     */
    public function __construct(
        private readonly array $files,
        private readonly array $available,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $media  the decoded `media-source.json`
     */
    public static function fromMediaSource(array $media): self
    {
        $files = [];
        $available = [];

        foreach ($media as $item) {
            $id = (int) ($item['wp_id'] ?? 0);

            if ($id < 1) {
                continue;
            }

            $available[$id] = ($item['in_archive'] ?? false) === true;

            foreach ([(string) ($item['file'] ?? ''), ...array_map(fn (array $d): string => (string) ($d['file'] ?? ''), (array) ($item['derivatives'] ?? []))] as $file) {
                if ($file !== '') {
                    $files[mb_strtolower($file)] ??= $id;
                }
            }
        }

        return new self($files, $available);
    }

    public static function fromFile(string $path): self
    {
        return self::fromMediaSource(is_file($path) ? (array) json_decode((string) file_get_contents($path), true) : []);
    }

    /**
     * The path under `wp-content/uploads/`, lower-cased, or null for any
     * other URL.
     */
    public static function uploadsFile(string $url): ?string
    {
        $path = rawurldecode((string) parse_url(str_replace('\\/', '/', trim($url)), PHP_URL_PATH));
        $position = stripos($path, '/wp-content/uploads/');

        return $position === false ? null : mb_strtolower(substr($path, $position + strlen('/wp-content/uploads/')));
    }

    public function assetIdForUrl(string $url): ?int
    {
        $file = self::uploadsFile($url);

        if ($file === null) {
            return null;
        }

        // A size WordPress never recorded (an Elementor crop) still names its original.
        $id = $this->files[$file]
            ?? $this->files[(string) preg_replace('/-\d+x\d+(?=\.[a-z0-9]+$)/', '', $file)]
            ?? null;

        return $id === null ? null : $this->assetIdForAttachment($id);
    }

    public function assetIdForAttachment(int $attachmentId): ?int
    {
        return ($this->available[$attachmentId] ?? false) ? $attachmentId : null;
    }
}
