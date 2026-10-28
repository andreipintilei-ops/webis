<?php

namespace App\Models;

use App\Blocks\BlockRegistry;
use App\Concerns\HasPublication;
use App\Concerns\HasRevisions;
use App\Concerns\RecordsSlugHistory;
use App\Concerns\TracksAssetUsage;
use App\Enums\PageType;
use App\Support\Seo\SeoData;
use Carbon\CarbonImmutable;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A CMS page served at a root slug. See App\Enums\PageType for the kinds.
 *
 * An article may have a cover (the `cover` media collection, COVER): the
 * uploaded original kept privately, so its sizes can be made again; from
 * it, WebP at each of COVER_WIDTHS — the middle 16:9 of one with its own
 * ground, a cut-out (a transparent ground) whole, its transparency kept —
 * and the 1200×630 share image as a JPEG (a cut-out laid on the hero's
 * gradient: Listeners\FlattenCoverShareImage). Its alt text is `cover_alt`. Made
 * at once, not on the queue: the article shows it as soon as it is saved.
 * Support\Blog\Cover reads it for the views.
 *
 * @property int $id
 * @property PageType $type
 * @property int|null $post_category_id
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string|null $icon
 * @property int|null $hero_asset_id
 * @property string|null $cover_alt
 * @property list<array<string, mixed>>|null $blocks
 * @property array<string, mixed>|null $details
 * @property int $sort_order
 * @property SeoData $seo
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Asset|null $hero
 * @property-read PostCategory|null $category
 * @property-read Collection<int, Project> $projects
 */
#[Fillable([
    'type', 'post_category_id', 'title', 'slug', 'excerpt', 'icon', 'hero_asset_id', 'cover_alt',
    'blocks', 'details', 'sort_order', 'status', 'published_at', 'seo',
])]
class Page extends Model implements HasMedia
{
    /** @use HasFactory<PageFactory> */
    use HasFactory, HasPublication, HasRevisions, InteractsWithMedia, RecordsSlugHistory, SoftDeletes, TracksAssetUsage;

    public const string COVER = 'cover';

    /** The cover's widths, for srcset; the first is the largest. */
    public const array COVER_WIDTHS = [1536, 1200, 768, 480];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COVER)
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp'])
            // The original, private; what is shown, public.
            ->useDisk('local')
            ->storeConversionsOnDisk('public');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // A cut-out (Actions\Blog\StoreCover) whole, at its own proportions,
        // fitted into its frame with room around it; one with its own ground
        // cropped to 16:9, to fill the frame.
        $cutout = (bool) $media?->getCustomProperty('transparent');

        foreach (self::COVER_WIDTHS as $width) {
            $this->addMediaConversion('cover-'.$width)
                ->performOnCollections(self::COVER)
                ->nonQueued()
                ->format('webp')
                ->quality(80)
                ->fit($cutout ? Fit::Max : Fit::Crop, $width, $cutout ? $width : (int) round($width * 9 / 16));
        }

        $this->addMediaConversion('og')
            ->performOnCollections(self::COVER)
            ->nonQueued()
            ->format('jpg')
            ->quality(85)
            ->fit(Fit::Crop, 1200, 630);
    }

    protected function casts(): array
    {
        return [
            'type' => PageType::class,
            'blocks' => 'array',
            'details' => 'array',
            'sort_order' => 'integer',
            'seo' => SeoData::class,
        ];
    }

    protected static function booted(): void
    {
        // Every save stores blocks in their canonical shape (ids, versions,
        // rendered rich text), whichever path wrote them.
        static::saving(function (Page $page): void {
            if ($page->isDirty('blocks') && is_array($page->blocks)) {
                $page->blocks = app(BlockRegistry::class)->prepare($page->blocks);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function hero(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'hero_asset_id');
    }

    /**
     * An article's blog category (PageType::Article only).
     *
     * @return BelongsTo<PostCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    /**
     * Related portfolio work shown on service and industry pages.
     *
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOfType(Builder $query, PageType $type): Builder
    {
        return $query->where($this->qualifyColumn('type'), $type->value);
    }

    public function revisionAttributes(): array
    {
        return ['title', 'excerpt', 'icon', 'hero_asset_id', 'cover_alt', 'blocks', 'details', 'seo'];
    }

    public function assetReferences(): array
    {
        return [
            'hero' => [$this->hero_asset_id],
            'blocks' => app(BlockRegistry::class)->assetIds($this->blocks ?? []),
            'seo.og_image' => [$this->seo->ogImageAssetId],
        ];
    }
}
