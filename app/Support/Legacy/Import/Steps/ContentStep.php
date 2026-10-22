<?php

namespace App\Support\Legacy\Import\Steps;

use App\Blocks\BlockRegistry;
use App\Enums\ContentStatus;
use App\Enums\PageType;
use App\Models\Page;
use App\Models\PostCategory;
use App\Support\Legacy\Import\BodyConverter;
use App\Support\Legacy\Import\ConversionContext;
use App\Support\Legacy\Import\ConverterUnavailable;
use App\Support\Legacy\Import\ImportContext;
use App\Support\Legacy\Import\LegacyPost;
use App\Support\Legacy\Import\ReviewedMap;
use App\Support\Legacy\Import\SpamReview;
use App\Support\ReservedSlugs;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * The CMS pages: articles (`post`), pages (`page`) and industry pages
 * (`domenii`, at /servicii/{slug}). This step owns everything but the body —
 * which rows, the slug (a row's target, so `/12705-2` lands on its original
 * slug), the category, the date, SEO, draft or published — and hands the body
 * to the BodyConverter, whose blocks are validated before anything is saved.
 *
 * Keyed by slug. A re-run refreshes title, excerpt, SEO, date and category,
 * and never replaces non-empty blocks without --force. A slug held by
 * something else (the live pages, the demo pages, the deleted `despre`, a page
 * of another type) is reported and left alone.
 */
final class ContentStep
{
    /** kind → [the decisions it imports, as which page type]. */
    public const array KINDS = [
        'articles' => ['article' => PageType::Article],
        'pages' => ['page' => PageType::Page, 'legal' => PageType::Legal, 'contact' => PageType::Contact],
        'industries' => ['industry' => PageType::Industry],
    ];

    /** The new site's own pages, which no import may touch. */
    public const array PROTECTED_SLUGS = ['acasa', 'contact', 'produse', 'portofoliu', 'despre', 'demo-blocuri-1', 'demo-blocuri-2'];

    /** Below this, a converted body probably lost its content. */
    public const int MIN_WORDS = 120;

    public function __construct(
        private readonly BodyConverter $converter,
        private readonly BlockRegistry $blocks,
    ) {}

    /**
     * @param  'articles'|'pages'|'industries'  $step
     * @return bool false when the step refused to run
     */
    public function run(ImportContext $context, string $step): bool
    {
        $report = $context->report;
        $kinds = self::KINDS[$step];
        $kind = Str::singular($step);

        if (! $this->converter->available()) {
            $report->refuse($step, ConverterUnavailable::forKind($kind)->getMessage());

            return false;
        }

        $rows = $context->map->decided(...array_keys($kinds));
        $ids = array_values(array_filter(array_map(fn (array $row): int => (int) $row['wp_id'], $rows)));
        $posts = $context->legacy->posts($ids);
        $yoastTitles = $context->legacy->yoastTitles('post', $ids);
        $conversion = new ConversionContext($context->media(), $context->links());

        foreach ($rows as $row) {
            try {
                $this->importOne($context, $step, $kind, $row, $kinds[$row['decision']], $posts[(int) $row['wp_id']] ?? null, $yoastTitles[(int) $row['wp_id']] ?? '', $conversion);
            } catch (ConverterUnavailable $e) {
                $report->refuse($step, $e->getMessage());

                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function importOne(ImportContext $context, string $step, string $kind, array $row, PageType $type, ?LegacyPost $post, string $yoastTitle, ConversionContext $conversion): void
    {
        $report = $context->report;
        $slug = ReviewedMap::targetSlug($row);
        $refuse = function (string $reason) use ($report, $step, $row, $slug): void {
            $report->count($step, 'refused');
            $report->add($step, 'refused', ['path' => $row['old_path'], 'slug' => $slug, 'reason' => $reason]);
        };

        if (ReservedSlugs::contains($slug)) {
            $refuse('reserved slug');

            return;
        }

        if (! SpamReview::allows($row)) {
            $refuse('spam flag not cleared: '.$row['spam_flags']);

            return;
        }

        $existing = Page::withTrashed()->where('slug', $slug)->first();

        if ($existing !== null && ($existing->trashed() || in_array($slug, self::PROTECTED_SLUGS, true) || $existing->type !== $type)) {
            $report->count($step, 'exists_kept');
            $report->add($step, 'exists_kept', ['slug' => $slug, 'type' => $existing->type->value, 'deleted' => $existing->trashed()]);

            return;
        }

        if ($post === null) {
            $refuse("post #{$row['wp_id']} not in the legacy database");

            return;
        }

        try {
            $body = $this->converter->convert($post, $kind, $conversion);
            $blocks = $this->blocks->validate($this->withIds($body->blocks));
        } catch (ValidationException $e) {
            $refuse('invalid blocks: '.implode('; ', array_map(
                fn (string $field, array $messages): string => $field.': '.implode(' ', $messages),
                array_keys($e->errors()),
                $e->errors(),
            )));

            return;
        } catch (ConverterUnavailable $e) {
            throw $e;
        } catch (Throwable $e) {
            $refuse('conversion failed: '.$e->getMessage());

            return;
        }

        foreach ($body->warnings as $warning) {
            $report->add($step, 'warnings', ['slug' => $slug, 'warning' => $warning]);
        }

        foreach ($body->dropped as $what => $n) {
            $report->count($step, "dropped:{$what}", $n);
        }

        if ($body->wordCount < self::MIN_WORDS) {
            $report->add($step, 'thin_bodies', ['slug' => $slug, 'words' => $body->wordCount]);
        }

        $title = $post->title !== '' ? $post->title : $row['title'];
        $page = $existing ?? new Page(['type' => $type, 'slug' => $slug, 'status' => ContentStatus::Draft]);

        $page->fill([
            'title' => $title,
            'excerpt' => $this->excerpt($post, $row),
            'published_at' => $this->publishedAt($row, $post),
            'seo' => $context->legacy->yoast()->seo([
                'title' => $yoastTitle,
                'description' => $row['meta_description'] ?? '',
                'canonical' => $row['canonical'] ?? '',
                'noindex' => $row['noindex'] ?? '',
                'focus_keyword' => $row['focus_keyword'] ?? '',
            ], $title, fn (string $canonical) => $report->add($step, 'canonicals_discarded', ['path' => $row['old_path'], 'canonical' => $canonical])),
        ]);

        if ($type === PageType::Article) {
            $page->post_category_id = $this->category($context, $step, $row, $slug);
        }

        if ($page->blocks === null || $page->blocks === [] || $context->force) {
            $page->blocks = $blocks;
        } else {
            $report->count($step, 'body_kept');
        }

        $hero = $context->assetForAttachment($post->thumbnailId());

        if ($hero !== null && ($page->hero_asset_id === null || $context->force)) {
            $page->hero_asset_id = $hero;
        }

        if ($context->publish) {
            $page->status = ContentStatus::Published;
        }

        $state = $page->exists ? ($page->isDirty() ? 'updated' : 'unchanged') : 'created';
        $page->save();
        $report->count($step, $state);
    }

    /**
     * Blocks as the editor would submit them: each with an id (validation
     * requires one; a converter need not invent them).
     *
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    private function withIds(array $blocks): array
    {
        return array_map(
            fn (mixed $block): mixed => is_array($block) && ! is_string($block['id'] ?? null)
                ? ['id' => Str::lower((string) Str::ulid()), ...$block]
                : $block,
            $blocks,
        );
    }

    /**
     * When it first went out. WordPress re-dated every article to 2026-04-14,
     * so an article's date is the reviewed `original_date` (at the post's time
     * of day); anything else keeps its own date.
     *
     * @param  array<string, string>  $row
     */
    private function publishedAt(array $row, LegacyPost $post): mixed
    {
        $date = $row['published_at'] !== '' ? $row['published_at'] : $post->date;
        $original = trim($row['original_date'] ?? '');

        if ($row['decision'] === 'article' && $original !== '') {
            return ImportContext::legacyDate($original, substr($date, 11, 8) ?: null) ?? ImportContext::legacyDate($date);
        }

        return ImportContext::legacyDate($date);
    }

    /**
     * The article's category, by the reviewed primary category's slug.
     *
     * @param  array<string, string>  $row
     */
    private function category(ImportContext $context, string $step, array $row, string $slug): ?int
    {
        $category = trim($row['primary_category'] ?? '');

        if ($category === '') {
            $context->report->add($step, 'no_category', $slug);

            return null;
        }

        $id = PostCategory::query()->where('slug', $category)->value('id');

        if ($id === null) {
            $context->report->add($step, 'category_missing', ['slug' => $slug, 'category' => $category]);
        }

        return $id;
    }

    /**
     * The hand-written excerpt, else the Yoast description, else the start of
     * the text.
     *
     * @param  array<string, string>  $row
     */
    private function excerpt(LegacyPost $post, array $row): ?string
    {
        foreach ([$post->excerpt, $row['meta_description'] ?? ''] as $candidate) {
            $text = $this->plain($candidate);

            if ($text !== '') {
                return Str::limit($text, 300, '…', preserveWords: true);
            }
        }

        $text = $this->plain($post->content);

        return $text === '' ? null : Str::limit($text, 200, '…', preserveWords: true);
    }

    private function plain(string $html): string
    {
        $html = (string) preg_replace(['/<!--.*?-->/s', '/\[[^\]]+\]/', '/<(script|style)\b.*?<\/\1>/is'], ' ', $html);
        $text = html_entity_decode(strip_tags((string) preg_replace('/<(br|\/p|\/li|\/h\d|\/div)\b[^>]*>/i', ' $0', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', str_replace("\u{a0}", ' ', $text)));
    }
}
