<?php

namespace App\Console\Commands;

use App\Enums\PageType;
use App\Models\Page;
use App\Models\PostCategory;
use App\Models\Project;
use App\Settings\SeoSettings;
use App\Support\PublicUrls;
use App\Support\ReservedSlugs;
use App\Support\Seo\SeoData;
use App\Support\Seo\SeoText;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

/**
 * Fills the SEO title and description of every page and project that has
 * none, in its SEO fields (editable in the admin), held to the site's rules
 * (Support\Seo\SeoText):
 *
 * - the old site's Yoast title and description where it had them (the URL
 *   map's `title` and `meta_description`, matched on the page's path);
 * - else the title from the page's H1 (its hero's heading, an article's or
 *   project's title) and the description from the start of its text.
 *
 * Two projects of one client (its site, its logo) get their type after the
 * name, so no two titles are the same. The home page and the contact page
 * are left alone: theirs are written by hand. Values already set stay,
 * unless --force.
 *
 * Every value not taken whole from the old site is listed (and written to
 * storage/app/private/seo-generated.csv), for its copy to be reviewed.
 */
class FillSeo extends Command
{
    protected $signature = 'seo:fill-missing
        {--force : Replace titles and descriptions already set}
        {--dry-run : Show what would be written, write nothing}
        {--map= : The URL map (default: storage/app/private/legacy/url-map.csv)}
        {--review= : Where the values to review are listed (default: storage/app/private/seo-generated.csv)}';

    protected $description = 'Fill missing SEO titles and descriptions from the old site, else from the page';

    /** @var array<string, array{title: string, description: string}> by path */
    private array $yoast = [];

    /** @var list<array{0: string, 1: string, 2: string, 3: string}> path, field, value, source */
    private array $generated = [];

    public function handle(SeoSettings $seo): int
    {
        $this->yoast = $this->readMap((string) ($this->option('map') ?: storage_path('app/private/legacy/url-map.csv')));
        $suffix = $seo->title_suffix;
        $force = (bool) $this->option('force');

        // Not the pages at a reserved slug either: the app's own route
        // answers there (/produse, /portofoliu), with its own title.
        $items = [
            ...Page::query()->whereNotIn('type', [PageType::Home, PageType::Contact])->get()
                ->reject(fn (Page $page): bool => $page->type !== PageType::Industry && ReservedSlugs::contains($page->slug))->all(),
            ...Project::query()->with('client')->get()->all(),
        ];

        // Titles first, all of them, so duplicates can be told apart.
        $titles = [];

        foreach ($items as $item) {
            $path = (string) parse_url(PublicUrls::canonical($item), PHP_URL_PATH);
            $kept = $item->seo->title;

            // One already set stays, held to the limit if it is over it.
            $titles[$path] = match (true) {
                ! $kept || $force => $this->title($item, $path, $suffix),
                mb_strlen($kept.$suffix) > SeoText::TITLE_MAX => [SeoText::title($kept, $suffix), 'set before, shortened'],
                default => [$kept, null],
            };
        }

        $counts = array_count_values(array_map(fn (array $title): string => mb_strtolower($title[0]), $titles));
        $written = 0;

        foreach ($items as $item) {
            $path = (string) parse_url(PublicUrls::canonical($item), PHP_URL_PATH);
            [$title, $source] = $titles[$path];

            if ($item instanceof Project && $source !== null && $counts[mb_strtolower($title)] > 1 && $item->type) {
                [$title, $source] = [SeoText::title($title.' – '.$item->type->label(), $suffix), 'generated: duplicate, its type added'];
            }

            $seoData = $item->seo->toArray();
            $changed = false;

            if ($source !== null) {
                $seoData['title'] = $title;
                $changed = true;
                $this->note($path, 'title', $title, $source);
            }

            $kept = (string) $item->seo->description;

            [$description, $dSource] = match (true) {
                $kept === '' || $force => $this->description($item, $path),
                mb_strlen($kept) > SeoText::DESCRIPTION_MAX => [SeoText::description($kept), 'set before, shortened'],
                default => ['', ''],
            };

            if ($description !== '') {
                $seoData['description'] = $description;
                $changed = true;
                $this->note($path, 'description', $description, $dSource);
            }

            if ($changed) {
                $written++;

                if (! $this->option('dry-run')) {
                    $item->seo = SeoData::fromArray($seoData);
                    $item->save();
                }
            }
        }

        // Titles still the same after shortening (two long ones alike up to
        // the cut): written, but named, to be told apart by hand.
        $final = array_count_values(array_map(fn (array $row): string => mb_strtolower($row[2]), array_filter($this->generated, fn (array $row): bool => $row[1] === 'title')));

        foreach ($this->generated as $row) {
            if ($row[1] === 'title' && $final[mb_strtolower($row[2])] > 1) {
                $this->warn("Same title as another page, to rewrite: {$row[0]} — {$row[2]}");
            }
        }

        $written += $this->categories($force);

        $review = array_values(array_filter($this->generated, fn (array $row): bool => $row[3] !== 'old site'));
        $this->table(['Path', 'Field', 'Value', 'Source'], array_map(fn (array $row): array => [$row[0], $row[1], mb_strimwidth($row[2], 0, 70, '…'), $row[3]], $review));

        if (! $this->option('dry-run')) {
            $this->writeReview($review);
        }

        $this->info(($this->option('dry-run') ? 'Would write ' : 'Wrote ').$written.' item(s); '.count($review).' value(s) generated, to review'
            .($this->option('dry-run') ? '.' : ' (storage/app/private/seo-generated.csv).'));

        return self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: string} the title and where it came from
     */
    private function title(Model $item, string $path, string $suffix): array
    {
        $old = $this->yoast[$path]['title'] ?? '';
        $h1 = $item instanceof Page && $item->type !== PageType::Article
            ? (string) (collect($item->blocks ?? [])->firstWhere('type', 'hero')['data']['heading'] ?? $item->title)
            : (string) $item->title;
        $from = SeoText::plain($old) !== '' ? $old : $h1;
        $title = SeoText::title($from, $suffix);

        $source = $from === $old ? 'old site' : 'generated: the H1';

        return [$title, $title === SeoText::title($from, '') ? $source : $source.', shortened'];
    }

    /**
     * @return array{0: string, 1: string} the description and where it came from
     */
    private function description(Model $item, string $path): array
    {
        $old = SeoText::plain($this->yoast[$path]['description'] ?? '');

        if ($old !== '') {
            $description = SeoText::description($old);

            return [$description, $description === $old ? 'old site' : 'old site, shortened'];
        }

        $text = $item instanceof Project ? (string) $item->summary : $this->firstText($item);

        return [SeoText::description($text), 'generated: the start of the page'];
    }

    /**
     * A page's opening text: its hero's subheading, else the paragraphs its
     * first text starts with (enough to fill a description: a question
     * there is followed by its answer), else its excerpt.
     */
    private function firstText(Page $page): string
    {
        foreach ($page->blocks ?? [] as $block) {
            $data = $block['data'] ?? [];
            $text = match ($block['type'] ?? null) {
                'hero' => (string) ($data['subheading'] ?? ''),
                'rich_text' => $this->openingParagraphs((string) ($data['content_html'] ?? '')),
                'features', 'stats' => (string) ($data['intro'] ?? ''),
                default => '',
            };

            if (SeoText::plain($text) !== '') {
                return $text;
            }
        }

        return (string) $page->excerpt;
    }

    /**
     * The blog's categories: a description each, when they have none (the
     * old site had none either). A category's page is its list of articles,
     * so it is said from that: its name and its latest articles' titles.
     */
    private function categories(bool $force): int
    {
        $written = 0;

        foreach (PostCategory::query()->get() as $category) {
            if ($category->seo->description && ! $force) {
                continue;
            }

            $latest = Page::query()->published()->where('type', PageType::Article)->where('post_category_id', $category->id)
                ->latest('published_at')->limit(3)->pluck('title')
                ->map(fn (string $title): string => SeoText::plain($title))->implode('; ');
            // "Web design" in lower case mid-sentence; "SEO", "WordPress" as they are.
            $name = preg_match('/^\p{Lu}[\p{Ll}\s]*$/u', $category->name) === 1 ? mb_strtolower($category->name) : $category->name;
            $description = SeoText::description('Articole Webis despre '.$name.($latest !== '' ? ': '.$latest : '').'.');
            $path = (string) parse_url(PublicUrls::canonical($category), PHP_URL_PATH);

            $this->note($path, 'description', $description, 'generated: the category and its latest articles');
            $written++;

            if (! $this->option('dry-run')) {
                $category->seo = SeoData::fromArray([...$category->seo->toArray(), 'description' => $description]);
                $category->save();
            }
        }

        return $written;
    }

    /**
     * The values to review, in storage/app/private/seo-generated.csv: added to
     * what earlier runs listed, a value written again replacing its row.
     *
     * @param  list<array{0: string, 1: string, 2: string, 3: string}>  $review
     */
    private function writeReview(array $review): void
    {
        $file = (string) ($this->option('review') ?: storage_path('app/private/seo-generated.csv'));
        $rows = [];

        if (File::exists($file)) {
            $handle = fopen($file, 'r');
            fgetcsv($handle, escape: '');

            while (($row = fgetcsv($handle, escape: '')) !== false) {
                $rows[$row[0].'|'.$row[1]] = $row;
            }

            fclose($handle);
        }

        foreach ($review as $row) {
            $rows[$row[0].'|'.$row[1]] = $row;
        }

        $handle = fopen($file, 'w');
        fputcsv($handle, ['path', 'field', 'value', 'source'], escape: '');

        foreach ($rows as $row) {
            fputcsv($handle, $row, escape: '');
        }

        fclose($handle);
    }

    /** The first paragraphs of some HTML, as many as fill a description. */
    private function openingParagraphs(string $html): string
    {
        preg_match_all('~<p>(.*?)</p>~s', $html, $paragraphs);
        $text = '';

        foreach ($paragraphs[1] as $paragraph) {
            $paragraph = SeoText::plain($paragraph);

            // A short question, or a few words alone, is a run-in heading:
            // the text after it says more.
            if ((str_ends_with($paragraph, '?') && mb_strlen($paragraph) < 100) || mb_strlen($paragraph) < 40) {
                continue;
            }

            // A paragraph without its own end (a bold run-in heading) gets one.
            $text = trim($text.' '.(preg_match('/[.!?:…]$/u', $paragraph) ? $paragraph : $paragraph.'.'));

            if (mb_strlen($text) >= SeoText::DESCRIPTION_MAX) {
                break;
            }
        }

        return $text;
    }

    private function note(string $path, string $field, string $value, string $source): void
    {
        $this->generated[] = [$path, $field, $value, $source];
    }

    /**
     * The URL map's Yoast title and description, by target path (the first
     * row naming a path, as the import keeps).
     *
     * @return array<string, array{title: string, description: string}>
     */
    private function readMap(string $path): array
    {
        if (! File::exists($path)) {
            $this->warn("No URL map at {$path}: titles and descriptions all come from the pages.");

            return [];
        }

        $file = fopen($path, 'r');
        $head = fgetcsv($file, escape: '') ?: [];
        $map = [];

        while (($row = fgetcsv($file, escape: '')) !== false) {
            if (count($row) !== count($head)) {
                continue;
            }

            $row = array_combine($head, $row);

            if (($row['target_path'] ?? '') !== '' && ($row['decision'] ?? '') !== 'redirect') {
                $map[$row['target_path']] ??= ['title' => $row['title'] ?? '', 'description' => $row['meta_description'] ?? ''];
            }
        }

        fclose($file);

        return $map;
    }
}
