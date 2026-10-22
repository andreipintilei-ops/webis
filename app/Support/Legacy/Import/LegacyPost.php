<?php

namespace App\Support\Legacy\Import;

use App\Support\Legacy\ContentSource;

/**
 * One old WordPress post with everything a body converter reads: its own
 * columns and its postmeta (`_elementor_data`, the Pods fields of a
 * `domenii`…). Multi-valued meta (a Pods gallery stored as one row per item)
 * keeps every value in `metaValues()`.
 */
final readonly class LegacyPost
{
    /**
     * @param  array<string, list<string>>  $meta  meta key → its values, in meta_id order
     */
    public function __construct(
        public int $id,
        public string $type,
        public string $slug,
        public string $title,
        public string $content,
        public string $excerpt,
        public string $date,
        public array $meta = [],
    ) {}

    /** The first value of a meta key, or null. */
    public function meta(string $key): ?string
    {
        return $this->meta[$key][0] ?? null;
    }

    /** @return list<string> */
    public function metaValues(string $key): array
    {
        return $this->meta[$key] ?? [];
    }

    public function contentSource(): ContentSource
    {
        return ContentSource::detect($this->content, $this->meta('_elementor_data'));
    }

    /** The featured image's attachment id, if any. */
    public function thumbnailId(): ?int
    {
        $id = $this->meta('_thumbnail_id');

        return $id !== null && ctype_digit($id) && (int) $id > 0 ? (int) $id : null;
    }
}
