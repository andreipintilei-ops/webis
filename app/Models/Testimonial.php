<?php

namespace App\Models;

use App\Concerns\TracksAssetUsage;
use Carbon\CarbonImmutable;
use Database\Factories\TestimonialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $author_name
 * @property string|null $author_role
 * @property string|null $company
 * @property string $quote
 * @property int|null $rating
 * @property int|null $avatar_asset_id
 * @property int|null $client_id
 * @property int|null $project_id
 * @property bool $is_visible
 * @property int $sort_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Asset|null $avatar
 * @property-read Client|null $client
 * @property-read Project|null $project
 */
#[Fillable([
    'author_name', 'author_role', 'company', 'quote', 'rating', 'avatar_asset_id',
    'client_id', 'project_id', 'is_visible', 'sort_order',
])]
class Testimonial extends Model
{
    /** @use HasFactory<TestimonialFactory> */
    use HasFactory, SoftDeletes, TracksAssetUsage;

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function avatar(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'avatar_asset_id');
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('is_visible'), true);
    }

    public function assetReferences(): array
    {
        return ['avatar' => [$this->avatar_asset_id]];
    }
}
