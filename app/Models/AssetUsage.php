<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One place an asset is referenced (App\Concerns\TracksAssetUsage).
 *
 * @property int $id
 * @property int $asset_id
 * @property string $usable_type
 * @property int $usable_id
 * @property string $field
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Asset|null $asset
 * @property-read Model|null $usable
 */
#[Fillable(['asset_id', 'field'])]
class AssetUsage extends Model
{
    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function usable(): MorphTo
    {
        return $this->morphTo();
    }
}
