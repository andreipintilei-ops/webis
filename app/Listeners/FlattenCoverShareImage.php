<?php

namespace App\Listeners;

use App\Models\Page;
use GdImage;
use Spatie\MediaLibrary\Conversions\Events\ConversionHasBeenCompletedEvent;

/**
 * An article cover's share image (Page's `og` conversion) when the original
 * is transparent: the conversion's JPEG has no transparency to keep, so its
 * empty parts come out black. Drawn again here, the whole original laid in
 * the middle of the hero's gradient — its indigo, lit by the violet and the
 * purple, as css/site/page-hero's backdrop — with room around it, so a
 * shared link looks like the site and nothing of the drawing is cut off.
 * An opaque original's JPEG (its middle, cropped) is left as it is.
 *
 * Runs whenever the conversion does: on upload, on import, and on
 * `media-library:regenerate`.
 */
class FlattenCoverShareImage
{
    public const int WIDTH = 1200;

    public const int HEIGHT = 630;

    public function handle(ConversionHasBeenCompletedEvent $event): void
    {
        $media = $event->media;

        if ($event->conversion->getName() !== 'og' || $media->collection_name !== Page::COVER) {
            return;
        }

        $source = self::open($media->getPath());

        if ($source === null || ! self::isTransparent($source)) {
            return;
        }

        $canvas = self::gradient(self::WIDTH, self::HEIGHT);
        imagealphablending($canvas, true);
        self::copyWhole($source, $canvas);
        imagejpeg($canvas, $media->getPath('og'), 85);
    }

    /** A cut-out whole, in the middle, a margin of the ground around it. */
    public static function copyWhole(GdImage $source, GdImage $canvas, float $margin = 0.06): void
    {
        $sw = imagesx($source);
        $sh = imagesy($source);
        $cw = imagesx($canvas);
        $ch = imagesy($canvas);
        $scale = min($cw * (1 - 2 * $margin) / $sw, $ch * (1 - 2 * $margin) / $sh);
        $w = (int) round($sw * $scale);
        $h = (int) round($sh * $scale);

        imagecopyresampled($canvas, $source, (int) (($cw - $w) / 2), (int) (($ch - $h) / 2), 0, 0, $w, $h, $sw, $sh);
    }

    public static function open(string $path): ?GdImage
    {
        $data = @file_get_contents($path);
        $image = $data === false ? false : @imagecreatefromstring($data);

        if (! $image instanceof GdImage) {
            return null;
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        return $image;
    }

    /** Whether any of it lets what is behind it show (sampled small). */
    public static function isTransparent(GdImage $image): bool
    {
        if (imageistruecolor($image) === false && imagecolortransparent($image) === -1) {
            return false;
        }

        $sample = imagescale($image, 96, 64, IMG_BILINEAR_FIXED);

        if (! $sample instanceof GdImage) {
            return false;
        }

        imagealphablending($sample, false);

        for ($y = 0; $y < 64; $y++) {
            for ($x = 0; $x < 96; $x++) {
                if (((imagecolorat($sample, $x, $y) >> 24) & 0x7F) > 8) {
                    return true;
                }
            }
        }

        return false;
    }

    /** The hero's ground: indigo, a violet glow to the left, purple low right. */
    public static function gradient(int $width, int $height): GdImage
    {
        $canvas = imagecreatetruecolor($width, $height);

        // Top to bottom, #2d2b78 → #1f2868 → #2d2b78.
        for ($y = 0; $y < $height; $y++) {
            $k = abs($y / max(1, $height - 1) - 0.5) * 2;
            imageline($canvas, 0, $y, $width, $y, imagecolorallocate(
                $canvas,
                (int) round(0x1F + (0x2D - 0x1F) * $k),
                (int) round(0x28 + (0x2B - 0x28) * $k),
                (int) round(0x68 + (0x78 - 0x68) * $k),
            ));
        }

        imagealphablending($canvas, true);
        self::glow($canvas, (int) ($width * 0.15), (int) ($height * 0.35), (int) ($width * 0.7), (int) ($height * 1.1), [0x77, 0x5A, 0xFC], 0.45);
        self::glow($canvas, (int) ($width * 0.9), (int) ($height * 0.95), (int) ($width * 0.7), (int) ($height * 1.0), [0xA4, 0x4C, 0xEE], 0.5);

        return $canvas;
    }

    /** A soft ellipse of light, strongest at its middle. */
    private static function glow(GdImage $canvas, int $cx, int $cy, int $w, int $h, array $rgb, float $strength): void
    {
        $steps = 40;

        for ($i = $steps; $i >= 1; $i--) {
            $k = $i / $steps;
            // Each ring adds a little, so the middle, under all of them, is the brightest.
            $alpha = (int) round(127 - (127 * $strength / $steps) * 2.2 * (1 - $k * 0.6));
            imagefilledellipse($canvas, $cx, $cy, (int) ($w * $k), (int) ($h * $k), imagecolorallocatealpha($canvas, $rgb[0], $rgb[1], $rgb[2], max(0, min(127, $alpha))));
        }
    }
}
