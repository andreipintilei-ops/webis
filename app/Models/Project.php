<?php

namespace App\Models;

use App\Blocks\BlockRegistry;
use App\Concerns\HasPublication;
use App\Concerns\HasRevisions;
use App\Concerns\RecordsSlugHistory;
use App\Concerns\TracksAssetUsage;
use App\Enums\ProjectType;
use App\Support\Seo\SeoData;
use Carbon\CarbonImmutable;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A client project, served at /proiect/{slug} and listed on /portofoliu
 * (filtered by `type`). Client work only — Webis's products are not projects.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property int|null $client_id
 * @property ProjectType|null $type
 * @property int|null $year
 * @property string|null $url the live site
 * @property string|null $summary one line
 * @property string|null $need what the client needed
 * @property string|null $built what was built
 * @property list<string>|null $stack the technologies
 * @property int|null $cover_asset_id
 * @property list<int>|null $images asset ids: the gallery
 * @property list<array<string, mixed>>|null $blocks
 * @property list<array{label: string, value: string}>|null $metrics
 * @property bool $is_featured
 * @property int $sort_order
 * @property SeoData $seo
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Client|null $client
 * @property-read Asset|null $cover
 * @property-read Collection<int, ProjectCategory> $categories
 * @property-read Collection<int, Page> $pages
 * @property-read Collection<int, Testimonial> $testimonials
 */
#[Fillable([
    'title', 'slug', 'client_id', 'type', 'year', 'url', 'summary', 'need', 'built', 'stack',
    'cover_asset_id', 'images', 'blocks', 'metrics', 'is_featured', 'sort_order', 'status', 'published_at', 'seo',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasPublication, HasRevisions, RecordsSlugHistory, SoftDeletes, TracksAssetUsage;

    protected function casts(): array
    {
        return [
            'type' => ProjectType::class,
            'year' => 'integer',
            'stack' => 'array',
            'images' => 'array',
            'blocks' => 'array',
            'metrics' => 'array',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'seo' => SeoData::class,
        ];
    }

    protected static function booted(): void
    {
        // Every save stores blocks in their canonical shape (ids, versions,
        // rendered rich text), whichever path wrote them.
        static::saving(function (Project $project): void {
            if ($project->isDirty('blocks') && is_array($project->blocks)) {
                $project->blocks = app(BlockRegistry::class)->prepare($project->blocks);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function cover(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'cover_asset_id');
    }

    /**
     * @return BelongsToMany<ProjectCategory, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProjectCategory::class);
    }

    /**
     * Service and industry pages that feature this project.
     *
     * @return BelongsToMany<Page, $this>
     */
    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class)->withPivot('sort_order');
    }

    /**
     * @return HasMany<Testimonial, $this>
     */
    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    public function revisionAttributes(): array
    {
        return ['title', 'client_id', 'type', 'year', 'url', 'summary', 'need', 'built', 'stack', 'cover_asset_id', 'images', 'blocks', 'metrics', 'seo'];
    }

    public function assetReferences(): array
    {
        return [
            'cover' => [$this->cover_asset_id],
            'images' => array_map(intval(...), $this->images ?? []),
            'blocks' => app(BlockRegistry::class)->assetIds($this->blocks ?? []),
            'seo.og_image' => [$this->seo->ogImageAssetId],
        ];
    }
}
