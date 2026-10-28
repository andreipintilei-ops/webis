<?php

namespace App\Support;

use App\Models\Asset;
use App\Settings\BrandSettings;
use Illuminate\Support\Facades\Storage;

/**
 * The identity images as the public layout needs them: URL plus intrinsic
 * size (so the logo has width/height and never shifts the header while it
 * loads). Resolved once per request.
 */
final class Brand
{
    /** @var array<int, array{url: string, width: int|null, height: int|null}>|null */
    private ?array $images = null;

    public function __construct(private readonly BrandSettings $settings) {}

    /**
     * @return array{url: string, width: int|null, height: int|null}|null
     */
    public function logo(): ?array
    {
        return $this->image($this->settings->logo_asset_id);
    }

    /**
     * The logo for dark backgrounds, falling back to the normal one.
     *
     * @return array{url: string, width: int|null, height: int|null}|null
     */
    public function logoNegative(): ?array
    {
        return $this->image($this->settings->logo_negative_asset_id) ?? $this->logo();
    }

    /**
     * The square app icon (admin sidebar, login screen).
     *
     * @return array{url: string, width: int|null, height: int|null}|null
     */
    public function icon(): ?array
    {
        return $this->image($this->settings->icon_asset_id);
    }

    /**
     * Favicon URLs keyed by kind (icon32, icon96, apple, or svg).
     *
     * @return array<string, string>
     */
    public function favicons(): array
    {
        return array_map(
            fn (string $path): string => Storage::disk('public')->url($path),
            $this->settings->favicons,
        );
    }

    /**
     * @return array{url: string, width: int|null, height: int|null}|null
     */
    private function image(?int $assetId): ?array
    {
        if ($assetId === null) {
            return null;
        }

        $this->images ??= Asset::query()
            ->with('media')
            ->whereKey(array_filter([
                $this->settings->logo_asset_id,
                $this->settings->logo_negative_asset_id,
                $this->settings->icon_asset_id,
            ]))
            ->get()
            ->mapWithKeys(fn (Asset $asset): array => [$asset->id => [
                'url' => (string) $asset->file()?->getUrl(),
                'width' => $asset->width,
                'height' => $asset->height,
            ]])
            ->filter(fn (array $image): bool => $image['url'] !== '')
            ->all();

        return $this->images[$assetId] ?? null;
    }
}
