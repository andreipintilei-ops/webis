<?php

namespace App\Actions\Brand;

use App\Models\Asset;
use App\Settings\BrandSettings;
use GdImage;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Makes the favicon files from the favicon asset and records their paths on
 * the public disk in BrandSettings::$favicons:
 *
 * - `icon32`, `icon96`: transparent PNGs, the mark centred on a square. 96 is
 *   a multiple of 48, the size Google uses for favicons in search results.
 * - `apple`: 180×180 on white with some breathing room — iOS fills any
 *   transparency with black.
 * - `svg`: an SVG mark is linked as-is (it scales, and GD cannot read it).
 *
 * File names carry a hash of the source, so browsers fetch a replaced favicon
 * instead of serving the old one from cache.
 */
class GenerateFavicons
{
    private const string DIRECTORY = 'brand';

    public function __invoke(BrandSettings $settings): void
    {
        $disk = Storage::disk('public');
        $disk->deleteDirectory(self::DIRECTORY);

        $asset = $settings->favicon_asset_id ? Asset::with('media')->find($settings->favicon_asset_id) : null;
        $file = $asset?->file();

        $settings->favicons = match (true) {
            $file === null => [],
            $file->mime_type === 'image/svg+xml' => ['svg' => $file->getPathRelativeToRoot()],
            default => $this->pngs((string) file_get_contents($file->getPath()), $asset->id.'-'.md5_file($file->getPath())),
        };

        $settings->save();
    }

    /**
     * @return array<string, string>
     */
    private function pngs(string $source, string $version): array
    {
        $image = @imagecreatefromstring($source);

        if (! $image instanceof GdImage) {
            throw new RuntimeException('The favicon image could not be read.');
        }

        $hash = substr(md5($version), 0, 10);
        $urls = [];

        foreach ([
            'icon32' => [32, 0.0, null],
            'icon96' => [96, 0.0, null],
            'apple' => [180, 0.12, [255, 255, 255]],
        ] as $name => [$size, $padding, $background]) {
            $path = self::DIRECTORY."/{$name}-{$hash}.png";
            Storage::disk('public')->put($path, $this->square($image, $size, $padding, $background));
            // A disk path, not a URL: the database must survive a host change.
            $urls[$name] = $path;
        }

        return $urls;
    }

    /**
     * The image fitted inside a square, centred, keeping its proportions.
     *
     * @param  positive-int  $size
     * @param  array{int<0, 255>, int<0, 255>, int<0, 255>}|null  $background  RGB, or null for transparent
     */
    private function square(GdImage $source, int $size, float $padding, ?array $background): string
    {
        $canvas = imagecreatetruecolor($size, $size);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);

        $fill = $background === null
            ? (int) imagecolorallocatealpha($canvas, 0, 0, 0, 127)
            : (int) imagecolorallocate($canvas, ...$background);
        imagefilledrectangle($canvas, 0, 0, $size, $size, $fill);
        imagealphablending($canvas, true);

        $box = (int) round($size * (1 - 2 * $padding));
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min($box / $sourceWidth, $box / $sourceHeight);
        $width = max(1, (int) round($sourceWidth * $scale));
        $height = max(1, (int) round($sourceHeight * $scale));

        imagecopyresampled(
            $canvas, $source,
            intdiv($size - $width, 2), intdiv($size - $height, 2), 0, 0,
            $width, $height, $sourceWidth, $sourceHeight,
        );

        ob_start();
        imagepng($canvas);

        return (string) ob_get_clean();
    }
}
