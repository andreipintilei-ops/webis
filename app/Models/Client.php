<?php

namespace App\Models;

use App\Concerns\TracksAssetUsage;
use Carbon\CarbonImmutable;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property int|null $logo_asset_id
 * @property string|null $sector
 * @property string|null $url
 * @property bool $is_institution
 * @property bool $show_in_logos
 * @property int $sort_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Asset|null $logo
 * @property-read Collection<int, Project> $projects
 * @property-read Collection<int, Testimonial> $testimonials
 */
#[Fillable(['name', 'logo_asset_id', 'sector', 'url', 'is_institution', 'show_in_logos', 'sort_order'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, SoftDeletes, TracksAssetUsage;

    protected function casts(): array
    {
        return [
            'is_institution' => 'boolean',
            'show_in_logos' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function logo(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'logo_asset_id');
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return HasMany<Testimonial, $this>
     */
    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    public function assetReferences(): array
    {
        return ['logo' => [$this->logo_asset_id]];
    }
}
