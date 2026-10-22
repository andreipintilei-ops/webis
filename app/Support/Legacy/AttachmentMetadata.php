<?php

namespace App\Support\Legacy;

/**
 * Every file WordPress generated from one upload. `_wp_attachment_metadata`
 * lists the resized copies (`thumbnail`, `medium`, `-300x175` …) by file name,
 * each in the same directory as the original; it is the only complete source
 * of those URLs, most of which appear nowhere else.
 */
final class AttachmentMetadata
{
    /**
     * The derivatives of one attachment: its `sizes`, the pre-scaling
     * `original_image` (WordPress 5.3+ stores big uploads as `-scaled`), and
     * the copies kept in `_wp_attachment_backup_sizes` by the image editor.
     * Deduplicated by file, never including the attachment's own file.
     *
     * @return list<array{size: string, file: string, url: string, width: int|null, height: int|null}>
     */
    public static function derivatives(?string $metadata, string $attachedFile, string $uploadsUrl, ?string $backupSizes = null): array
    {
        $meta = self::unserialise($metadata);
        $mainFile = $attachedFile !== '' ? $attachedFile : (string) ($meta['file'] ?? '');

        if ($mainFile === '') {
            return [];
        }

        $directory = dirname($mainFile);
        $directory = $directory === '.' ? '' : $directory.'/';

        $candidates = [];

        foreach ((array) ($meta['sizes'] ?? []) as $size => $info) {
            if (is_array($info) && isset($info['file'])) {
                $candidates[] = [(string) $size, (string) $info['file'], $info['width'] ?? null, $info['height'] ?? null];
            }
        }

        if (isset($meta['original_image']) && is_string($meta['original_image'])) {
            $candidates[] = ['original_image', $meta['original_image'], null, null];
        }

        foreach (self::unserialise($backupSizes) as $size => $info) {
            if (is_array($info) && isset($info['file'])) {
                $candidates[] = ['backup:'.$size, (string) $info['file'], $info['width'] ?? null, $info['height'] ?? null];
            }
        }

        $derivatives = [];
        $seen = [$mainFile => true];

        foreach ($candidates as [$size, $file, $width, $height]) {
            $path = $directory.basename($file);

            if ($file === '' || isset($seen[$path])) {
                continue;
            }

            $seen[$path] = true;
            $derivatives[] = [
                'size' => $size,
                'file' => $path,
                'url' => self::url($uploadsUrl, $path),
                'width' => is_numeric($width) ? (int) $width : null,
                'height' => is_numeric($height) ? (int) $height : null,
            ];
        }

        return $derivatives;
    }

    public static function url(string $uploadsUrl, string $file): string
    {
        return rtrim($uploadsUrl, '/').'/'.ltrim($file, '/');
    }

    /**
     * A serialised WordPress value as an array, refusing objects; anything
     * malformed comes back empty.
     *
     * @return array<int|string, mixed>
     */
    public static function unserialise(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $data = @unserialize($value, ['allowed_classes' => false]);

        return is_array($data) ? $data : [];
    }
}
