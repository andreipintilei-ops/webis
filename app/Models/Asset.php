<?php

namespace App\Models;

use App\Exceptions\AssetInUseException;
use App\Settings\BrandSettings;
use App\Settings\SeoSettings;
use Carbon\CarbonImmutable;
use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A media-library item: exactly one uploaded file plus its alt text, caption
 * and intrinsic size. Content references assets by id; the file and its WebP
 * conversions live in spatie's `media` table.
 *
 * @property int $id
 * @property string|null $title
 * @property string|null $alt
 * @property string|null $caption
 * @property int|null $width
 * @property int|null $height
 * @property int|null $uploaded_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, AssetUsage> $usages
 * @property-read User|null $uploader
 */
#[Fillable(['title', 'alt', 'caption', 'width', 'height', 'uploaded_by'])]
class Asset extends Model implements HasMedia
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory, InteractsWithMedia;

    public const string COLLECTION = 'file';

    protected static function booted(): void
    {
        static::deleting(function (Asset $asset): void {
            $usages = $asset->usages()->count() + count($asset->settingsReferences());

            if ($usages > 0) {
                throw AssetInUseException::for($asset, $usages);
            }
        });
    }

    /**
     * Site settings that point at this asset. Settings are not models, so they
     * are not in `asset_usages`; they are checked here instead.
     *
     * @return list<string> human labels, e.g. "Setări SEO: logo"
     */
    public function settingsReferences(): array
    {
        $seo = app(SeoSettings::class);
        $brand = app(BrandSettings::class);

        return array_keys(array_filter([
            'Identitate: logo' => $brand->logo_asset_id === $this->id,
            'Identitate: logo negativ' => $brand->logo_negative_asset_id === $this->id,
            'Identitate: favicon' => $brand->favicon_asset_id === $this->id,
            'Identitate: iconiță' => $brand->icon_asset_id === $this->id,
            'Setări SEO: imagine implicită de distribuire' => $seo->default_og_image_asset_id === $this->id,
            'Setări SEO: logo' => $seo->logo_asset_id === $this->id,
        ]));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COLLECTION)->singleFile();
    }

    /**
     * WebP versions, generated on the queue. `web` is the public rendition with
     * responsive widths for srcset; `thumb` feeds the admin grid. Formats the
     * image driver cannot read (SVG under GD) are simply served as uploaded.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->format('webp')
            ->fit(Fit::Max, 480, 480);

        // Conversion options first: image manipulations are proxied to the image
        // driver, so nothing conversion-specific can be chained after them.
        $this->addMediaConversion('web')
            ->withResponsiveImages()
            ->format('webp')
            ->fit(Fit::Max, 2400, 2400);
    }

    /**
     * @return HasMany<AssetUsage, $this>
     */
    public function usages(): HasMany
    {
        return $this->hasMany(AssetUsage::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function file(): ?Media
    {
        return $this->getFirstMedia(self::COLLECTION);
    }
}
