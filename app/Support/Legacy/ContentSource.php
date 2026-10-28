<?php

namespace App\Support\Legacy;

/**
 * Where an old WordPress post keeps its body, which decides how it gets
 * converted: Elementor data rebuilds as blocks, classic and Gutenberg HTML
 * go through the rich-text path, and an empty body has nothing to convert.
 */
enum ContentSource: string
{
    case Classic = 'classic';
    case Gutenberg = 'gutenberg';
    case Elementor = 'elementor';
    case Empty = 'empty';

    /**
     * Elementor wins whenever the post carries real `_elementor_data`: that is
     * what WordPress rendered, and `post_content` is only Elementor's copy.
     */
    public static function detect(?string $postContent, ?string $elementorData): self
    {
        if (self::hasElementorData($elementorData)) {
            return self::Elementor;
        }

        $content = trim((string) $postContent);

        if ($content === '') {
            return self::Empty;
        }

        return str_contains($content, '<!-- wp:') ? self::Gutenberg : self::Classic;
    }

    /** `[]` is what Elementor leaves behind on a post it was opened on but never used for. */
    public static function hasElementorData(?string $elementorData): bool
    {
        $data = trim((string) $elementorData);

        return $data !== '' && $data !== '[]';
    }
}
