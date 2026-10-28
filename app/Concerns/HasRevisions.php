<?php

namespace App\Concerns;

use App\Models\Revision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Snapshots a model's editable content after each save that changes it, keeping
 * the newest Revision::KEEP per item. Editing a published page changes the live
 * page on save (no separate draft copy in v1), so revisions are the undo.
 *
 * The newest revision always mirrors the current state; restoring means
 * copying an older snapshot's `data` back onto the model.
 *
 * @phpstan-require-extends Model
 */
trait HasRevisions
{
    /**
     * The attributes worth snapshotting — the content an editor changes, not
     * counters or timestamps.
     *
     * @return list<string>
     */
    abstract public function revisionAttributes(): array;

    public static function bootHasRevisions(): void
    {
        // Separate hooks, not `saved` + wasRecentlyCreated: that flag stays true
        // on the instance after its first save, so every later save would snapshot.
        static::created(function (Model $model): void {
            /** @var Model&self $model */
            $model->recordRevision();
        });

        static::updated(function (Model $model): void {
            /** @var Model&self $model */
            if ($model->wasChanged($model->revisionAttributes())) {
                $model->recordRevision();
            }
        });

        static::deleted(function (Model $model): void {
            if (! method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                /** @var Model&self $model */
                $model->revisions()->delete();
            }
        });
    }

    /**
     * @return MorphMany<Revision, $this>
     */
    public function revisions(): MorphMany
    {
        return $this->morphMany(Revision::class, 'revisionable')->latest('id');
    }

    public function recordRevision(): Revision
    {
        $data = [];

        foreach ($this->revisionAttributes() as $attribute) {
            // Raw attributes: JSON columns stay JSON strings and enums stay their
            // backing values, so a restore writes back exactly what was stored.
            $data[$attribute] = $this->getAttributes()[$attribute] ?? null;
        }

        $revision = $this->revisions()->create([
            'user_id' => auth()->id(),
            'data' => $data,
        ]);

        $keep = $this->revisions()->limit(Revision::KEEP)->pluck('id');

        $this->revisions()->whereNotIn('id', $keep)->delete();

        return $revision;
    }

    /**
     * Put an earlier snapshot's content back and save — which itself records a
     * new revision, so a restore can be undone too.
     */
    public function restoreRevision(Revision $revision): void
    {
        $this->setRawAttributes(array_merge($this->getAttributes(), $revision->data));
        $this->save();
    }
}
