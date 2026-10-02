<?php

namespace App\Concerns;

use App\Models\Asset;
use App\Models\AssetUsage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Keeps `asset_usages` in sync with the assets a model references, so the media
 * library can show "used in" and refuse to delete an image that is still live.
 *
 * The model lists its references in assetReferences(), keyed by field. Usages
 * are rebuilt after every save and removed only on a force delete — a trashed
 * item still protects its images, so restoring it never finds them gone.
 *
 * @phpstan-require-extends Model
 */
trait TracksAssetUsage
{
    /**
     * Asset ids this model references, keyed by the field that holds them
     * (e.g. `cover`, `seo.og_image`, `blocks`). Nulls are ignored.
     *
     * @return array<string, list<int|null>>
     */
    abstract public function assetReferences(): array;

    public static function bootTracksAssetUsage(): void
    {
        static::saved(function (Model $model): void {
            /** @var Model&self $model */
            $model->syncAssetUsages();
        });

        static::deleted(function (Model $model): void {
            if (! method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                /** @var Model&self $model */
                $model->assetUsages()->delete();
            }
        });
    }

    /**
     * @return MorphMany<AssetUsage, $this>
     */
    public function assetUsages(): MorphMany
    {
        return $this->morphMany(AssetUsage::class, 'usable');
    }

    public function syncAssetUsages(): void
    {
        $references = array_map(
            fn (array $ids): array => array_values(array_unique(array_filter($ids))),
            $this->assetReferences(),
        );

        // References inside JSON (seo, blocks) have no foreign key, so one can
        // outlive its asset. Track only assets that still exist.
        $existingAssets = Asset::query()
            ->whereKey(array_merge(...array_values($references)))
            ->pluck('id')
            ->all();

        $wanted = [];

        foreach ($references as $field => $ids) {
            foreach (array_intersect($ids, $existingAssets) as $assetId) {
                $wanted["{$assetId}|{$field}"] = ['asset_id' => $assetId, 'field' => $field];
            }
        }

        $existing = $this->assetUsages()->get(['id', 'asset_id', 'field'])
            ->keyBy(fn (AssetUsage $usage): string => "{$usage->asset_id}|{$usage->field}");

        $stale = $existing->diffKeys($wanted)->pluck('id');

        if ($stale->isNotEmpty()) {
            AssetUsage::query()->whereKey($stale)->delete();
        }

        foreach (array_diff_key($wanted, $existing->all()) as $usage) {
            $this->assetUsages()->create($usage);
        }
    }
}
