<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A snapshot of a content item's editable attributes (App\Concerns\HasRevisions).
 *
 * @property int $id
 * @property string $revisionable_type
 * @property int $revisionable_id
 * @property int|null $user_id
 * @property array<string, mixed> $data
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Model|null $revisionable
 * @property-read User|null $user
 */
#[Fillable(['user_id', 'data'])]
class Revision extends Model
{
    /**
     * Snapshots kept per item; older ones are pruned on each new revision.
     */
    public const int KEEP = 25;

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function revisionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
