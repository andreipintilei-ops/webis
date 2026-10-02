<?php

namespace App\Concerns;

use App\Enums\ContentStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Draft / Scheduled / Published lifecycle for pages, projects and posts.
 *
 * The model's table needs `status` and `published_at` columns. An item is
 * public only when it is Published AND its `published_at` has passed — so a
 * Published row with a future date stays hidden rather than leaking early.
 *
 * @property ContentStatus $status
 * @property CarbonInterface|null $published_at
 */
trait HasPublication
{
    public static function bootHasPublication(): void
    {
        // Publishing without a date means "now". Keeps `published_at` usable
        // for sorting, feeds and article:published_time on every live item.
        static::saving(function (Model $model): void {
            if ($model->getAttribute('status') === ContentStatus::Published && $model->getAttribute('published_at') === null) {
                $model->setAttribute('published_at', now());
            }
        });
    }

    public function initializeHasPublication(): void
    {
        $this->mergeCasts([
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ]);
    }

    /**
     * Items visitors may see.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where($this->qualifyColumn('status'), ContentStatus::Published->value)
            ->where($this->qualifyColumn('published_at'), '<=', now());
    }

    /**
     * Scheduled items whose moment has come; `content:publish-due` flips them.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeDueForPublishing(Builder $query): Builder
    {
        return $query
            ->where($this->qualifyColumn('status'), ContentStatus::Scheduled->value)
            ->where($this->qualifyColumn('published_at'), '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === ContentStatus::Published
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }
}
