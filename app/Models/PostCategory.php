<?php

namespace App\Models;

use App\Concerns\RecordsSlugHistory;
use App\Support\Seo\SeoData;
use Carbon\CarbonImmutable;
use Database\Factories\PostCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A blog category, served at /blog/categorie/{slug}.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $sort_order
 * @property SeoData $seo
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, Post> $posts
 */
#[Fillable(['name', 'slug', 'description', 'sort_order', 'seo'])]
class PostCategory extends Model
{
    /** @use HasFactory<PostCategoryFactory> */
    use HasFactory, RecordsSlugHistory;

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'seo' => SeoData::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
