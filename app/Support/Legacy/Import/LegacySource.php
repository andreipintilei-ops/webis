<?php

namespace App\Support\Legacy\Import;

use App\Support\Legacy\AttachmentMetadata;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only access to the old WordPress database (the `legacy` connection,
 * prefix `wpts_`) in the shapes the import steps want.
 */
final class LegacySource
{
    private ?YoastSeo $yoast = null;

    public function __construct(private readonly ConnectionInterface $db) {}

    public static function connect(): self
    {
        return new self(DB::connection('legacy'));
    }

    /**
     * Posts with all their postmeta, keyed by id.
     *
     * @param  list<int>  $ids
     * @return array<int, LegacyPost>
     */
    public function posts(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $meta = [];

        foreach (array_chunk($ids, 500) as $chunk) {
            $this->db->table('postmeta')->whereIn('post_id', $chunk)->orderBy('meta_id')
                ->get(['post_id', 'meta_key', 'meta_value'])
                ->each(function (object $row) use (&$meta): void {
                    $meta[(int) $row->post_id][(string) $row->meta_key][] = (string) $row->meta_value;
                });
        }

        $hasExcerpt = Schema::connection($this->db->getName())->hasColumn('posts', 'post_excerpt');
        $posts = [];

        foreach (array_chunk($ids, 500) as $chunk) {
            $this->db->table('posts')->whereIn('ID', $chunk)
                ->get(array_filter(['ID', 'post_type', 'post_name', 'post_title', 'post_content', 'post_date', $hasExcerpt ? 'post_excerpt' : null]))
                ->each(function (object $row) use (&$posts, $meta): void {
                    $id = (int) $row->ID;
                    $posts[$id] = new LegacyPost(
                        id: $id,
                        type: (string) $row->post_type,
                        slug: (string) $row->post_name,
                        title: html_entity_decode((string) $row->post_title, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                        content: (string) $row->post_content,
                        excerpt: (string) ($row->post_excerpt ?? ''),
                        date: (string) $row->post_date,
                        meta: $meta[$id] ?? [],
                    );
                });
        }

        return $posts;
    }

    /**
     * Yoast's custom SEO title per object (null where it has none), keyed by
     * object id. The URL map's `title` column falls back to the post title,
     * so it cannot tell "no SEO title" from one: this can.
     *
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    public function yoastTitles(string $objectType, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->db->table('yoast_indexable')
            ->where('object_type', $objectType)
            ->whereIn('object_id', $ids)
            ->whereNotNull('title')
            ->where('title', '!=', '')
            ->pluck('title', 'object_id')
            ->mapWithKeys(fn (mixed $title, mixed $id): array => [(int) $id => (string) $title])
            ->all();
    }

    /**
     * Terms by id: name, slug and (when the table has it) description.
     *
     * @param  list<int>  $ids
     * @return array<int, object{name: string, slug: string, description: string}>
     */
    public function terms(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $hasDescription = Schema::connection($this->db->getName())->hasColumn('term_taxonomy', 'description');

        return $this->db->table('terms')
            ->join('term_taxonomy', 'term_taxonomy.term_id', '=', 'terms.term_id')
            ->whereIn('terms.term_id', $ids)
            ->get(array_filter(['terms.term_id', 'terms.name', 'terms.slug', $hasDescription ? 'term_taxonomy.description' : null]))
            ->mapWithKeys(fn (object $term): array => [(int) $term->term_id => (object) [
                'name' => html_entity_decode((string) $term->name, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'slug' => (string) $term->slug,
                'description' => trim((string) ($term->description ?? '')),
            ]])
            ->all();
    }

    /**
     * Each post's term slugs in one taxonomy, the primary one (Yoast's) first.
     *
     * @param  list<int>  $postIds
     * @return array<int, list<string>>
     */
    public function postTerms(array $postIds, string $taxonomy): array
    {
        if ($postIds === []) {
            return [];
        }

        $primary = $this->db->table('yoast_primary_term')
            ->whereIn('post_id', $postIds)->where('taxonomy', $taxonomy)
            ->pluck('term_id', 'post_id');

        $terms = [];

        $this->db->table('term_relationships')
            ->join('term_taxonomy', 'term_taxonomy.term_taxonomy_id', '=', 'term_relationships.term_taxonomy_id')
            ->join('terms', 'terms.term_id', '=', 'term_taxonomy.term_id')
            ->whereIn('term_relationships.object_id', $postIds)
            ->where('term_taxonomy.taxonomy', $taxonomy)
            ->orderBy('term_relationships.term_order')
            ->orderBy('term_relationships.term_taxonomy_id')
            ->get(['term_relationships.object_id', 'terms.term_id', 'terms.slug'])
            ->each(function (object $row) use (&$terms, $primary): void {
                $postId = (int) $row->object_id;
                $terms[$postId] ??= [];

                if ((int) ($primary[$postId] ?? 0) === (int) $row->term_id) {
                    array_unshift($terms[$postId], (string) $row->slug);
                } else {
                    $terms[$postId][] = (string) $row->slug;
                }
            });

        return $terms;
    }

    /**
     * Yoast's title variables as this site rendered them: the blog name and
     * the chosen separator.
     */
    public function yoast(): YoastSeo
    {
        if ($this->yoast !== null) {
            return $this->yoast;
        }

        $options = $this->db->table('options')
            ->whereIn('option_name', ['blogname', 'wpseo_titles'])
            ->pluck('option_value', 'option_name');
        $titles = AttachmentMetadata::unserialise($options['wpseo_titles'] ?? null);

        return $this->yoast = YoastSeo::fromOptions(
            is_string($titles['separator'] ?? null) ? $titles['separator'] : null,
            $options['blogname'] ?? null,
        );
    }
}
