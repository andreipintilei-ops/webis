<?php

namespace App\Concerns;

use App\Models\SlugHistory;
use Illuminate\Database\Eloquent\Model;

/**
 * When a model whose `slug` appears in a public URL is renamed, remember the old
 * slug so LegacySlugRedirect can 301 the retired URL to the new one. Applied to
 * pages, projects, posts and their categories. Eloquent calls
 * bootRecordsSlugHistory() automatically.
 */
trait RecordsSlugHistory
{
    public static function bootRecordsSlugHistory(): void
    {
        static::updating(function (Model $model): void {
            if ($model->isDirty('slug')) {
                SlugHistory::remember($model, (string) $model->getOriginal('slug'));
            }
        });
    }
}
