<?php

namespace App\Support\Legacy\Import\Steps;

use App\Models\PostCategory;
use App\Support\Legacy\Import\ImportContext;
use App\Support\Legacy\Import\ReviewedMap;
use App\Support\Legacy\Import\SpamReview;

/**
 * One blog category per `category` row, keeping the old slug, so the old
 * `/{slug}/` 301s one-to-one to `/blog/categorie/{slug}/`. Merged categories
 * are `redirect` rows in the map and never become categories.
 *
 * Runs first: articles link to these by slug.
 */
final class CategoriesStep
{
    public const string NAME = 'categories';

    public function run(ImportContext $context): void
    {
        $rows = $context->map->decided('category');
        $report = $context->report;

        // The fullest categories first in the admin's order.
        usort($rows, fn (array $a, array $b): int => (int) ($b['article_count'] ?? 0) <=> (int) ($a['article_count'] ?? 0));

        $ids = array_values(array_filter(array_map(fn (array $row): int => (int) $row['wp_id'], $rows)));
        $terms = $context->legacy->terms($ids);
        $yoastTitles = $context->legacy->yoastTitles('term', $ids);
        $yoast = $context->legacy->yoast();

        foreach ($rows as $position => $row) {
            if (! SpamReview::allows($row)) {
                $report->count(self::NAME, 'refused_spam');
                $report->add(self::NAME, 'refused', ['path' => $row['old_path'], 'reason' => 'spam flag not cleared: '.$row['spam_flags']]);

                continue;
            }

            $slug = ReviewedMap::targetSlug($row);
            $term = $terms[(int) $row['wp_id']] ?? null;
            $name = $term->name ?? $row['title'];

            $seo = $yoast->seo([
                'title' => $yoastTitles[(int) $row['wp_id']] ?? '',
                'description' => $row['meta_description'] ?? '',
                'canonical' => $row['canonical'] ?? '',
                'noindex' => $row['noindex'] ?? '',
                'focus_keyword' => $row['focus_keyword'] ?? '',
            ], $name, fn (string $canonical) => $report->add(self::NAME, 'canonicals_discarded', ['path' => $row['old_path'], 'canonical' => $canonical]));

            $category = PostCategory::query()->where('slug', $slug)->first();

            if ($category === null) {
                PostCategory::query()->create([
                    'name' => $name,
                    'slug' => $slug,
                    'description' => ($term->description ?? '') ?: null,
                    'sort_order' => ($position + 1) * 10,
                    'seo' => $seo,
                ]);
                $report->count(self::NAME, 'created');

                continue;
            }

            $category->fill(['name' => $name, 'seo' => $seo]);

            if (($category->description ?? '') === '' || $context->force) {
                $category->description = ($term->description ?? '') ?: $category->description;
            }

            $report->count(self::NAME, $category->isDirty() ? 'updated' : 'unchanged');
            $category->save();
        }
    }
}
