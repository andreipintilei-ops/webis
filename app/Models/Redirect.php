<?php

namespace App\Models;

use App\Enums\RedirectCode;
use App\Enums\RedirectMatchType;
use Carbon\CarbonImmutable;
use Database\Factories\RedirectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A manual redirect, consulted only when a request would otherwise 404.
 *
 * @property int $id
 * @property string $source_path
 * @property RedirectMatchType $match_type
 * @property string|null $target
 * @property RedirectCode $status_code
 * @property int $hits
 * @property CarbonImmutable|null $last_hit_at
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['source_path', 'match_type', 'target', 'status_code', 'notes'])]
class Redirect extends Model
{
    /** @use HasFactory<RedirectFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'match_type' => RedirectMatchType::class,
            'status_code' => RedirectCode::class,
            'hits' => 'integer',
            'last_hit_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Redirect $redirect): void {
            $redirect->source_path = self::normalisePath($redirect->source_path);
        });
    }

    /**
     * The one canonical form of a source path, so lookups are a plain equality:
     * path only (no scheme, host or query), leading slash, no trailing slash,
     * lowercase. `https://www.webis.ro/Proiect/Foo/?x=1` becomes `/proiect/foo`.
     */
    public static function normalisePath(string $path): string
    {
        $path = (string) (parse_url(trim($path), PHP_URL_PATH) ?? '');
        $path = '/'.trim(rawurldecode($path), '/');

        return mb_strtolower($path);
    }
}
