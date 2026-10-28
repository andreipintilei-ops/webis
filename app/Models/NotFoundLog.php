<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\NotFoundLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One row per 404ing path with a running hit count (logged in Phase 4).
 *
 * @property int $id
 * @property string $path
 * @property int $hits
 * @property CarbonImmutable|null $last_seen_at
 * @property string|null $last_referrer
 * @property string|null $last_user_agent
 * @property bool $is_ignored
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['path', 'hits', 'last_seen_at', 'last_referrer', 'last_user_agent', 'is_ignored'])]
class NotFoundLog extends Model
{
    /** @use HasFactory<NotFoundLogFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'hits' => 'integer',
            'last_seen_at' => 'datetime',
            'is_ignored' => 'boolean',
        ];
    }
}
