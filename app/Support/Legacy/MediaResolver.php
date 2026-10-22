<?php

namespace App\Support\Legacy;

/**
 * Finds the media-library asset an old WordPress image became. The preview
 * (`legacy:convert`) answers from `media-source.json`; the import answers
 * from the `old_url → asset_id` map it writes while importing the files.
 */
interface MediaResolver
{
    /**
     * By the image's URL — the original or any resized copy of it.
     */
    public function assetIdForUrl(string $url): ?int;

    /**
     * By the WordPress attachment id (Elementor and Pods store these).
     */
    public function assetIdForAttachment(int $attachmentId): ?int;
}
