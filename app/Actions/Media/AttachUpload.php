<?php

namespace App\Actions\Media;

use App\Models\Asset;
use enshrined\svgSanitize\Sanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Puts an uploaded file on an asset — a new one, or an existing one whose file
 * is being replaced (everything that references the asset then shows the new
 * file). Records the intrinsic size so every <img> can carry width/height.
 *
 * SVGs are sanitised before storage: an SVG is a document that can carry
 * scripts and event handlers, and it is served from our own origin.
 */
class AttachUpload
{
    public const array ACCEPTED_MIMES = [
        'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif', 'image/svg+xml',
    ];

    public function __invoke(Asset $asset, UploadedFile $file): Asset
    {
        $name = $this->fileName($file);

        if ($this->isSvg($file)) {
            $svg = $this->sanitizeSvg((string) file_get_contents($file->getRealPath()));
            [$width, $height] = $this->svgSize($svg);

            $asset->addMediaFromString($svg)
                ->usingFileName($name)
                ->usingName(pathinfo($name, PATHINFO_FILENAME))
                ->toMediaCollection(Asset::COLLECTION);
        } else {
            $size = @getimagesize($file->getRealPath());
            [$width, $height] = $size === false ? [null, null] : [$size[0], $size[1]];

            $asset->addMedia($file)
                ->usingFileName($name)
                ->usingName(pathinfo($name, PATHINFO_FILENAME))
                ->toMediaCollection(Asset::COLLECTION);
        }

        $asset->forceFill(['width' => $width, 'height' => $height])->save();

        return $asset->load('media');
    }

    /**
     * A readable default title from the file name: "logo-client_final.png"
     * becomes "Logo client final".
     */
    public static function titleFrom(UploadedFile $file): string
    {
        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        return Str::ucfirst(trim((string) preg_replace('/[\s_-]+/', ' ', $base))) ?: 'Imagine';
    }

    private function isSvg(UploadedFile $file): bool
    {
        return $file->getMimeType() === 'image/svg+xml' || Str::lower($file->getClientOriginalExtension()) === 'svg';
    }

    /**
     * Slugged, so URLs stay clean and diacritics never reach the file system.
     */
    private function fileName(UploadedFile $file): string
    {
        $extension = Str::lower($file->getClientOriginalExtension() ?: ($file->guessExtension() ?? 'bin'));
        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'fisier';

        return Str::limit($base, 80, '').'.'.$extension;
    }

    private function sanitizeSvg(string $svg): string
    {
        $sanitizer = new Sanitizer;
        $sanitizer->removeRemoteReferences(true);
        $clean = $sanitizer->sanitize($svg);

        if ($clean === false || trim($clean) === '') {
            throw ValidationException::withMessages(['file' => 'Fișierul SVG nu este valid.']);
        }

        return $clean;
    }

    /**
     * Width/height attributes when they are plain numbers, else the viewBox.
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function svgSize(string $svg): array
    {
        $xml = @simplexml_load_string($svg);

        if ($xml === false) {
            return [null, null];
        }

        $attributes = $xml->attributes();
        $width = (string) ($attributes['width'] ?? '');
        $height = (string) ($attributes['height'] ?? '');

        if (is_numeric(rtrim($width, 'px')) && is_numeric(rtrim($height, 'px'))) {
            return [(int) round((float) rtrim($width, 'px')), (int) round((float) rtrim($height, 'px'))];
        }

        $viewBox = preg_split('/[\s,]+/', trim((string) ($attributes['viewBox'] ?? ''))) ?: [];

        return count($viewBox) === 4
            ? [(int) round((float) $viewBox[2]), (int) round((float) $viewBox[3])]
            : [null, null];
    }
}
