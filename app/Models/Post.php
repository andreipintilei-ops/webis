<?php

namespace App\Models;

use App\Concerns\HasPublication;
use App\Concerns\HasRevisions;
use App\Concerns\RecordsSlugHistory;
use App\Concerns\TracksAssetUsage;
use App\Support\RichText\RichText;
use App\Support\Seo\SeoData;
use Carbon\CarbonImmutable;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A blog post, served at /blog/{slug}.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property array<string, mixed>|null $body
 * @property string|null $body_html
 * @property list<array{id: string, text: string, level: int}>|null $toc
 * @property int $reading_minutes
 * @property int|null $cover_asset_id
 * @property int|null $post_category_id
 * @property int|null $author_id
 * @property int|null $cta_page_id
 * @property bool $is_featured
 * @property SeoData $seo
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Asset|null $cover
 * @property-read PostCategory|null $category
 * @property-read User|null $author
 * @property-read Page|null $ctaPage
 */
#[Fillable([
    'title', 'slug', 'excerpt', 'body', 'body_html', 'toc', 'reading_minutes',
    'cover_asset_id', 'post_category_id', 'author_id', 'cta_page_id',
    'is_featured', 'status', 'published_at', 'seo',
])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, HasPublication, HasRevisions, RecordsSlugHistory, SoftDeletes, TracksAssetUsage;

    protected function casts(): array
    {
        return [
            'body' => 'array',
            'toc' => 'array',
            'reading_minutes' => 'integer',
            'is_featured' => 'boolean',
            'seo' => SeoData::class,
        ];
    }

    protected static function booted(): void
    {
        // The body JSON is the source of truth. Whenever it changes, sanitize it
        // and derive the rendered HTML, table of contents and reading time, so
        // public pages never parse Tiptap JSON.
        static::saving(function (Post $post): void {
            if (! $post->isDirty('body')) {
                return;
            }

            $richText = app(RichText::class);
            $body = $richText->sanitize($post->body ?? []);

            $post->body = $body;
            $post->body_html = $richText->render($body);
            $post->toc = $richText->toc($body);
            $post->reading_minutes = max(1, $richText->readingMinutes($body));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function cover(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'cover_asset_id');
    }

    /**
     * @return BelongsTo<PostCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function ctaPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'cta_page_id');
    }

    public function revisionAttributes(): array
    {
        // The derived columns ride along so a restored body never shows a
        // stale rendering or table of contents.
        return [
            'title', 'excerpt', 'body', 'body_html', 'toc', 'reading_minutes',
            'cover_asset_id', 'post_category_id', 'author_id', 'cta_page_id', 'seo',
        ];
    }

    public function assetReferences(): array
    {
        return [
            'cover' => [$this->cover_asset_id],
            'body' => app(RichText::class)->assetIds($this->body ?? []),
            'seo.og_image' => [$this->seo->ogImageAssetId],
        ];
    }
}
