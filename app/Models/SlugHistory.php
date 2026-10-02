<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $sluggable_type
 * @property int $sluggable_id
 * @property string $old_slug
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Model|null $sluggable
 */
#[Table('slug_history')]
#[Fillable(['sluggable_type', 'sluggable_id', 'old_slug'])]
class SlugHistory extends Model
{
    /**
     * @return MorphTo<Model, $this>
     */
    public function sluggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Record that $model used to live at $oldSlug. Idempotent, and it reclaims an
     * old slug that had been recorded against a different row (e.g. a slug reused
     * after a delete) by pointing it at the current owner.
     */
    public static function remember(Model $model, string $oldSlug): void
    {
        if ($oldSlug === '' || $oldSlug === $model->getAttribute('slug')) {
            return;
        }

        static::updateOrCreate(
            ['sluggable_type' => $model->getMorphClass(), 'old_slug' => $oldSlug],
            ['sluggable_id' => $model->getKey()],
        );
    }

    /**
     * The current record a retired slug now points to, or null if unknown.
     *
     * @param  class-string<Model>  $type
     */
    public static function resolve(string $type, string $oldSlug): ?Model
    {
        $record = static::query()
            ->with('sluggable')
            ->where('sluggable_type', (new $type)->getMorphClass())
            ->where('old_slug', $oldSlug)
            ->first();

        return $record?->sluggable;
    }
}
