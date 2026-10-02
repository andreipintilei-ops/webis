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

/**
 * A CMS page served at a root slug. See App\Enums\PageType for the kinds.
 *
 * @property int $id
 * @property PageType $type
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string|null $icon
 * @property int|null $hero_asset_id
 * @property list<array<string, mixed>>|null $blocks
 * @property array<string, mixed>|null $details
 * @property int $sort_order
 * @property SeoData $seo
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Asset|null $hero
 * @property-read Collection<int, Project> $projects
 */
#[Fillable([
    'type', 'title', 'slug', 'excerpt', 'icon', 'hero_asset_id', 'blocks',
    'details', 'sort_order', 'status', 'published_at', 'seo',
])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory, HasPublication, HasRevisions, RecordsSlugHistory, SoftDeletes, TracksAssetUsage;

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
        return ['title', 'excerpt', 'icon', 'hero_asset_id', 'blocks', 'details', 'seo'];
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
