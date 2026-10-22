<?php

namespace App\Console\Commands;

use App\Blocks\BlockRegistry;
use App\Support\Legacy\BlockListValidator;
use App\Support\Legacy\ContentSource;
use App\Support\Legacy\Conversion;
use App\Support\Legacy\ElementorToBlocks;
use App\Support\Legacy\HtmlToBlocks;
use App\Support\Legacy\LegacyMediaIndex;
use App\Support\Legacy\LegacyUrlIndex;
use App\Support\Legacy\PodsToBlocks;
use App\Support\Legacy\SpamScanner;
use App\Support\Legacy\UploadsArchive;
use App\Support\Legacy\WordPressHtml;
use App\Support\RichText\RichText;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use stdClass;

/**
 * Previews the content conversion: runs the converters over the old posts
 * (the `legacy` connection) and shows the blocks they produce, writing
 * nothing to any database. With `--all`, writes `conversion-report.json`:
 * per post its block types, word count and what was dropped or left
 * unresolved, and the totals — above all the posts whose converted body is
 * under 150 words, the sign that the extractor lost content.
 *
 * Images resolve to placeholder ids (the WordPress attachment id) when their
 * original is in the uploads archive; real asset ids come with the import.
 */
class ConvertLegacyContent extends Command
{
    protected $signature = 'legacy:convert
        {wp_id? : The WordPress post id to convert}
        {--all : Convert every live article, page, industry page and project}
        {--json : Print the blocks and report as JSON}
        {--source= : Directory holding url-map.csv, legacy-ids.json and media-source.json (default: storage/app/private/legacy)}
        {--output= : Directory to write conversion-report.json into (default: storage/app/private/legacy)}
        {--archive= : Zip of the old public_html, to tell which unresolved images exist as files (default: webis-export/webis-bk.zip)}';

    protected $description = 'Preview the conversion of old WordPress content into blocks, writing nothing';

    /** The post types converted, and what each becomes. */
    public const array POST_TYPES = ['post' => 'article', 'page' => 'page', 'domenii' => 'industry', 'proiect' => 'project'];

    public const int MIN_WORDS = 150;

    /** Elementor settings that hold visible text, for the source word count. */
    private const array TEXT_SETTINGS = [
        'title', 'editor', 'title_text', 'description_text', 'tab_title', 'tab_content', 'text', 'item_text',
        'heading', 'sub_heading', 'before_text', 'highlighted_text', 'rotating_text', 'after_text', 'caption',
    ];

    /** Elementor widgets the source word count leaves out: chrome, not content. */
    private const array CHROME_WIDGETS = ['form', 'button', 'hotspot', 'testimonial-carousel', 'loop-grid', 'posts', 'portfolio', 'shortcode', 'html'];

    private ConnectionInterface $db;

    public function handle(): int
    {
        $id = $this->argument('wp_id');

        if (! $this->option('all') && ! is_numeric($id)) {
            $this->error('Give a WordPress post id, or --all.');

            return self::FAILURE;
        }

        $source = rtrim((string) ($this->option('source') ?: storage_path('app/private/legacy')), '/\\');

        if (! is_file("{$source}/url-map.csv") || ! is_file("{$source}/media-source.json")) {
            $this->error("No url-map.csv / media-source.json in {$source}: run legacy:inventory first, or pass --source.");

            return self::FAILURE;
        }

        $this->db = DB::connection('legacy');

        $html = new WordPressHtml(LegacyMediaIndex::fromFile("{$source}/media-source.json"), LegacyUrlIndex::fromDirectory($source), app(RichText::class));
        $registry = app(BlockRegistry::class);
        $validator = new BlockListValidator($registry, assumeAssetsExist: true);
        $converters = [
            'elementor' => new ElementorToBlocks($html, $validator, $registry),
            'pods' => new PodsToBlocks($html, $validator, $registry),
            'html' => new HtmlToBlocks($html, $validator, $registry),
        ];

        $query = $this->db->table('posts')->whereIn('post_type', array_keys(self::POST_TYPES));
        $query = $this->option('all') ? $query->where('post_status', 'publish') : $query->where('ID', (int) $id);
        $posts = $query->orderBy('post_type')->orderBy('ID')->get(['ID', 'post_name', 'post_title', 'post_type', 'post_content']);

        if ($posts->isEmpty()) {
            $this->error('No such post among the converted types ('.implode(', ', array_keys(self::POST_TYPES)).').');

            return self::FAILURE;
        }

        $meta = $this->meta(array_values($posts->map(fn (stdClass $post): int => (int) $post->ID)->all()));
        $spamFlags = $this->spamFlags($source);
        $scanner = new SpamScanner;
        $results = [];

        foreach ($posts as $post) {
            $postMeta = $meta[(int) $post->ID] ?? [];
            [$via, $conversion, $sourceWords] = $this->convert($post, $postMeta, $converters);
            $results[] = $this->postReport($post, $via, $conversion, $sourceWords, $spamFlags[(int) $post->ID] ?? '', $scanner);

            if (! $this->option('all')) {
                $this->show($post, $via, $conversion, end($results));
            }
        }

        if ($this->option('all')) {
            $output = rtrim((string) ($this->option('output') ?: storage_path('app/private/legacy')), '/\\');
            File::ensureDirectoryExists($output);
            $report = $this->report($results, $source);
            File::put("{$output}/conversion-report.json", json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)."\n");
            $this->summarise($report, $output);
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string|list<string>>  $meta
     * @param  array{elementor: ElementorToBlocks, pods: PodsToBlocks, html: HtmlToBlocks}  $converters
     * @return array{0: string, 1: Conversion, 2: int}
     */
    private function convert(stdClass $post, array $meta, array $converters): array
    {
        $title = html_entity_decode((string) $post->post_title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $elementor = is_string($meta['_elementor_data'] ?? null) ? $meta['_elementor_data'] : null;

        if ($post->post_type === 'domenii') {
            $fields = array_filter($meta, fn (string $key): bool => ! str_starts_with($key, 'litespeed'), ARRAY_FILTER_USE_KEY);
            $text = implode(' ', array_map(fn (string|array $v): string => is_array($v) ? '' : $v, array_filter($fields, fn (string $key): bool => ! str_starts_with($key, '_'), ARRAY_FILTER_USE_KEY)));

            return ['pods', $converters['pods']->convert($fields, $title), self::words(strip_tags($text))];
        }

        if ($post->post_type !== 'proiect' && ContentSource::hasElementorData($elementor)) {
            $data = json_decode((string) $elementor, true);

            return ['elementor', $converters['elementor']->convert(is_array($data) ? $data : [], $title, $post->post_type === 'post'), $this->elementorWords(is_array($data) ? $data : [])];
        }

        $content = (string) $post->post_content;

        return ['html', $converters['html']->convert($content, $title, $post->post_type !== 'proiect'), self::words(WordPressHtml::plainText($content))];
    }

    /**
     * @param  list<int>  $postIds
     * @return array<int, array<string, string|list<string>>>
     */
    private function meta(array $postIds): array
    {
        $meta = [];

        $this->db->table('postmeta')->whereIn('post_id', $postIds)->orderBy('meta_id')
            ->get(['post_id', 'meta_key', 'meta_value'])
            ->each(function (object $row) use (&$meta): void {
                $id = (int) $row->post_id;
                $key = (string) $row->meta_key;
                $value = (string) $row->meta_value;

                if (isset($meta[$id][$key])) {
                    $meta[$id][$key] = [...(array) $meta[$id][$key], $value];
                } else {
                    $meta[$id][$key] = $value;
                }
            });

        return $meta;
    }

    /**
     * The inventory's spam flags per post, from the URL map.
     *
     * @return array<int, string>
     */
    private function spamFlags(string $source): array
    {
        $flags = [];
        $handle = fopen("{$source}/url-map.csv", 'r');

        if ($handle === false) {
            return [];
        }

        $header = fgetcsv($handle, null, ',', '"', '') ?: [];

        while (($values = fgetcsv($handle, null, ',', '"', '')) !== false) {
            $row = count($values) === count($header) ? array_combine($header, $values) : null;

            if ($row !== null && $row['wp_id'] !== '' && $row['spam_flags'] !== '' && $row['object_type'] === 'post') {
                $flags[(int) $row['wp_id']] = $row['spam_flags'];
            }
        }

        fclose($handle);

        return $flags;
    }

    /**
     * @return array<string, mixed>
     */
    private function postReport(stdClass $post, string $via, Conversion $conversion, int $sourceWords, string $spamFlags, SpamScanner $scanner): array
    {
        $words = self::blockWords($conversion->blocks);
        $output = implode("\n", self::strings($conversion->blocks, withHtml: true));
        $spam = array_map(fn (array $hit): string => $hit['type'].($hit['detail'] !== '' ? ':'.$hit['detail'] : ''), $scanner->scan($output)['hits']);

        return [
            'wp_id' => (int) $post->ID,
            'type' => self::POST_TYPES[$post->post_type],
            'post_type' => $post->post_type,
            'slug' => $post->post_name,
            'converter' => $via,
            'blocks' => $conversion->blockTypes(),
            'words' => $words,
            'source_words' => $sourceWords,
            'dropped' => $conversion->report->dropped(),
            'dropped_count' => $conversion->report->droppedCount(),
            'unresolved' => $conversion->report->unresolvedItems(),
            'unresolved_count' => $conversion->report->unresolvedCount(),
            'notes' => $conversion->report->notes(),
            'inventory_spam_flags' => $spamFlags,
            'spam_hits_in_output' => array_values(array_unique($spam)),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $results
     * @return array<string, mixed>
     */
    private function report(array $results, string $source): array
    {
        $byType = [];
        $sequences = [];
        $dropped = [];
        $unresolved = [];

        foreach ($results as $result) {
            $type = $result['type'];
            $byType[$type] ??= ['posts' => 0, 'blocks' => []];
            $byType[$type]['posts']++;

            foreach ($result['blocks'] as $block) {
                $byType[$type]['blocks'][$block] = ($byType[$type]['blocks'][$block] ?? 0) + 1;
            }

            $sequence = implode(' → ', $this->squash($result['blocks']));
            $sequences[$type][$sequence] = ($sequences[$type][$sequence] ?? 0) + 1;

            foreach ($result['dropped'] as $kind => $count) {
                $dropped[$kind] = ($dropped[$kind] ?? 0) + $count;
            }

            foreach ($result['unresolved'] as $kind => $urls) {
                foreach ($urls as $url) {
                    $unresolved[$kind][$url] = ($unresolved[$kind][$url] ?? 0) + 1;
                }
            }
        }

        foreach ($byType as &$type) {
            arsort($type['blocks']);
        }

        unset($type);

        foreach ($sequences as &$list) {
            arsort($list);
            $list = array_slice($list, 0, 10, true);
        }

        unset($list);
        arsort($dropped);

        $short = array_values(array_map(fn (array $r): array => [
            'wp_id' => $r['wp_id'],
            'type' => $r['type'],
            'slug' => $r['slug'],
            'words' => $r['words'],
            'source_words' => $r['source_words'],
            'reason' => $r['source_words'] < self::MIN_WORDS
                ? "The source itself is short ({$r['source_words']} words)"
                : "Converted {$r['words']} of about {$r['source_words']} source words: check what was lost",
        ], array_filter($results, fn (array $r): bool => $r['words'] < self::MIN_WORDS)));

        return [
            'generated_at' => now()->toIso8601String(),
            'source' => $source,
            'note' => 'A preview: nothing was written to any database. Image ids are placeholders (the WordPress attachment id).',
            'totals' => [
                'posts' => count($results),
                'by_type' => $byType,
                'block_sequences' => $sequences,
                'under_'.self::MIN_WORDS.'_words' => $short,
                'dropped' => $dropped,
                'unresolved' => array_map(fn (array $urls): int => array_sum($urls), $unresolved),
                'unresolved_urls' => array_map(fn (array $urls): array => array_keys($urls), $unresolved),
                'unresolved_images_in_archive' => $this->inArchive(array_keys($unresolved['image'] ?? [])),
                'posts_with_invalid_blocks' => array_column(array_filter($results, fn (array $r): bool => preg_grep('/^invalid:/', array_keys($r['dropped'])) !== []), 'slug'),
                'spam_hits_in_output' => array_values(array_filter(array_map(fn (array $r): ?array => $r['spam_hits_in_output'] !== [] ? ['slug' => $r['slug'], 'hits' => $r['spam_hits_in_output']] : null, $results))),
                'inventory_spam_flags' => array_values(array_filter(array_map(fn (array $r): ?array => $r['inventory_spam_flags'] !== '' ? ['slug' => $r['slug'], 'flags' => $r['inventory_spam_flags']] : null, $results))),
            ],
            'posts' => $results,
        ];
    }

    /**
     * Of the unresolved images, those whose file is in the backup archive
     * all the same — uploads with no attachment row left in the library,
     * which the media import has to bring in by file. Null without an archive.
     *
     * @param  list<string>  $urls
     * @return array{archive: string, present: int, missing: int, present_urls: list<string>}|null
     */
    private function inArchive(array $urls): ?array
    {
        $path = (string) ($this->option('archive') ?: base_path('webis-export/webis-bk.zip'));

        if (! is_file($path)) {
            return null;
        }

        $archive = UploadsArchive::fromZip($path);
        $present = array_values(array_filter($urls, function (string $url) use ($archive): bool {
            $path = rawurldecode((string) parse_url($url, PHP_URL_PATH));
            $position = strpos($path, '/wp-content/uploads/');

            return $position !== false && $archive->has(substr($path, $position + strlen('/wp-content/uploads/')));
        }));

        return ['archive' => $path, 'present' => count($present), 'missing' => count($urls) - count($present), 'present_urls' => $present];
    }

    /**
     * Runs of the same block type, folded: rich_text ×3.
     *
     * @param  list<string>  $types
     * @return list<string>
     */
    private function squash(array $types): array
    {
        $runs = [];

        foreach ($types as $type) {
            $last = count($runs) - 1;

            if ($last >= 0 && $runs[$last][0] === $type) {
                $runs[$last][1]++;
            } else {
                $runs[] = [$type, 1];
            }
        }

        return array_map(fn (array $run): string => $run[1] > 1 ? "{$run[0]} ×{$run[1]}" : $run[0], $runs);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function show(stdClass $post, string $via, Conversion $conversion, array $result): void
    {
        if ($this->option('json')) {
            $this->line((string) json_encode(['post' => $result, 'blocks' => $conversion->blocks], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return;
        }

        $this->info("#{$post->ID} {$post->post_type} /{$post->post_name}/ — via {$via}, {$result['words']} words (source ~{$result['source_words']})");

        foreach ($conversion->blocks as $i => $block) {
            $data = $block['data'];
            $summary = match ($block['type']) {
                'rich_text', 'image_text' => trim(($data['heading'] ?? '').' '.mb_substr(app(RichText::class)->text($data['content']), 0, 140)),
                'features' => ($data['heading'] ?? '').' ['.implode(' | ', array_column($data['items'], 'title')).']',
                'faq' => ($data['heading'] ?? '').' ['.implode(' | ', array_column($data['items'], 'question')).']',
                default => implode(' · ', array_filter(self::strings([$block]), fn (string $s): bool => mb_strlen($s) > 1)),
            };
            $this->line(sprintf('  %2d. %-14s %s', $i + 1, $block['type'], mb_substr($summary, 0, 200)));
        }

        foreach ($conversion->report->dropped() as $kind => $count) {
            $this->line("  dropped {$kind}: {$count}");
        }

        foreach ($conversion->report->unresolvedItems() as $kind => $urls) {
            $this->line("  unresolved {$kind}: ".implode(', ', $urls));
        }

        foreach ($conversion->report->notes() as $note) {
            $this->line("  note: {$note}");
        }
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function summarise(array $report, string $output): void
    {
        $totals = $report['totals'];
        $this->info("Converted {$totals['posts']} posts. Report: {$output}/conversion-report.json");

        foreach ($totals['by_type'] as $type => $info) {
            $this->line("  {$type}: {$info['posts']} posts — ".implode(', ', array_map(fn (string|int $b, int $n): string => "{$b} {$n}", array_keys($info['blocks']), $info['blocks'])));
        }

        $short = $totals['under_'.self::MIN_WORDS.'_words'];
        $this->line('Under '.self::MIN_WORDS.' words: '.count($short));

        foreach ($short as $row) {
            $this->line("  {$row['type']} {$row['slug']}: {$row['words']} — {$row['reason']}");
        }

        $this->line('Dropped: '.implode(', ', array_map(fn (string|int $k, int $n): string => "{$k} {$n}", array_keys($totals['dropped']), $totals['dropped'])));
        $this->line('Unresolved: '.(json_encode($totals['unresolved']) ?: ''));
    }

    /**
     * Words of visible text across the blocks (rich text, headings, cards,
     * questions …), not counting URLs or settings.
     *
     * @param  list<array<string, mixed>>  $blocks
     */
    public static function blockWords(array $blocks): int
    {
        return self::words(implode(' ', self::strings($blocks)));
    }

    public static function words(string $text): int
    {
        return count(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    /**
     * Every text value in the blocks' data.
     *
     * @param  list<array<string, mixed>>  $blocks
     * @return list<string>
     */
    private static function strings(array $blocks, bool $withHtml = false): array
    {
        $skip = ['url', 'layout', 'background', 'align', 'variant', 'icon', 'image_position', 'youtube_id', 'columns', 'type'];
        $strings = [];
        $richText = app(RichText::class);

        $walk = function (mixed $value, string|int $key) use (&$walk, &$strings, $skip, $richText, $withHtml): void {
            if (is_array($value) && ($value['type'] ?? null) === 'doc') {
                $strings[] = $richText->text($value);

                return;
            }

            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $walk($v, $k);
                }

                return;
            }

            if (is_string($value) && ! in_array($key, $skip, true) && (! str_ends_with((string) $key, '_html') || $withHtml)) {
                $strings[] = $value;
            }
        };

        foreach ($blocks as $block) {
            $walk($block['data'] ?? [], 'data');
        }

        return $strings;
    }

    /**
     * Words of text in an Elementor tree's content settings.
     *
     * @param  array<mixed>  $elements
     */
    private function elementorWords(array $elements): int
    {
        $text = [];

        $walk = function (array $elements) use (&$walk, &$text): void {
            foreach ($elements as $element) {
                if (! is_array($element)) {
                    continue;
                }

                if (! in_array($element['widgetType'] ?? null, self::CHROME_WIDGETS, true)) {
                    $settings = (array) ($element['settings'] ?? []);

                    array_walk_recursive($settings, function (mixed $value, string|int $key) use (&$text): void {
                        if (is_string($value) && in_array($key, self::TEXT_SETTINGS, true)) {
                            $text[] = WordPressHtml::plainText($value);
                        }
                    });
                }

                $walk((array) ($element['elements'] ?? []));
            }
        };

        $walk($elements);

        return self::words(implode(' ', $text));
    }
}
