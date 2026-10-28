<?php

namespace App\Support\Blog;

use App\Models\Page;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * An article's cover as the views take it (components/site/post-card,
 * public/page): the WebP sizes for srcset, their width and height, the alt
 * text, whether it is a cut-out, and the share image. Null for an article
 * without one, or whose sizes are not made yet — the views then keep their
 * layout without it.
 *
 * A cut-out (a transparent ground) is whole, at its own proportions, and is
 * fitted into its 16:9 frame with room around it, on the frame's own
 * ground (`cutout`); one with its own ground is its middle 16:9 and fills
 * the frame.
 *
 * Load the pages `with('media')` for a list, so each card asks nothing more.
 */
final readonly class Cover
{
    public const int WIDTH = 1536;

    private function __construct(
        public string $src,
        public string $srcset,
        public int $width,
        public int $height,
        public string $alt,
        public bool $cutout,
        public string $og,
    ) {}

    public static function of(Page $page): ?self
    {
        $media = $page->getFirstMedia(Page::COVER);

        if (! $media instanceof Media || ! $media->hasGeneratedConversion('cover-'.self::WIDTH)) {
            return null;
        }

        $cutout = (bool) $media->getCustomProperty('transparent');
        $sourceWidth = (int) $media->getCustomProperty('width');
        $sourceHeight = (int) $media->getCustomProperty('height');

        $srcset = collect(Page::COVER_WIDTHS)
            ->filter(fn (int $width): bool => $media->hasGeneratedConversion('cover-'.$width))
            ->map(fn (int $width): string => $media->getUrl('cover-'.$width).' '.$width.'w')
            ->reverse()
            ->implode(', ');

        return new self(
            src: $media->getUrl('cover-'.self::WIDTH),
            srcset: $srcset,
            width: self::WIDTH,
            height: $cutout && $sourceWidth > 0 ? (int) round(self::WIDTH * $sourceHeight / $sourceWidth) : (int) round(self::WIDTH * 9 / 16),
            alt: (string) $page->cover_alt,
            cutout: $cutout,
            // Absolute, whatever the disk's URL: og:image and the JSON-LD need it.
            og: url($media->getUrl('og')),
        );
    }

    /**
     * The share image, as StructuredData::image() gives an asset's.
     *
     * @return array{url: string, width: int, height: int}
     */
    public function shareImage(): array
    {
        return ['url' => $this->og, 'width' => 1200, 'height' => 630];
    }
}
