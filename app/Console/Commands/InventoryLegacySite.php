<?php

namespace App\Console\Commands;

use App\Models\Redirect;
use App\Support\Legacy\AttachmentMetadata;
use App\Support\Legacy\ContentSource;
use App\Support\Legacy\ExtraUrlSources;
use App\Support\Legacy\SpamScanner;
use App\Support\Legacy\UploadsArchive;
use App\Support\Legacy\UrlClassifier;
use App\Support\Legacy\UrlMap;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Inventories the old WordPress site from its dump (the `legacy` connection)
 * into the reviewable URL map and its companions:
 *
 * - `url-map.csv` — every old URL with a pre-filled decision and target;
 * - `legacy-ids.json` — WordPress post id → new path, for `/?p=123` links;
 * - `media-source.json` — every attachment with all its derivative URLs;
 * - `spam-report.json` — every spam-scan hit, and the outbound domains.
 *
 * Reads only. The rules live in `App\Support\Legacy`; this command gathers the
 * rows they run on and writes the files.
 */
class InventoryLegacySite extends Command
{
    protected $signature = 'legacy:inventory
        {--output= : Directory to write into (default: storage/app/private/legacy)}
        {--archive= : Zip of the old public_html, to check which uploads exist (default: webis-export/webis-bk.zip)}';

    protected $description = 'Inventory the old WordPress site into a reviewable URL map';

    /** Post-type taxonomies whose term counts as a post's category. */
    private const array CATEGORY_TAXONOMY = ['post' => 'category', 'proiect' => 'categorie_proiect'];

    private ConnectionInterface $db;

    public function handle(): int
    {
        $directory = rtrim((string) ($this->option('output') ?: storage_path('app/private/legacy')), '/\\');
        File::ensureDirectoryExists($directory);

        $this->db = DB::connection('legacy');

        $options = $this->db->table('options')
            ->whereIn('option_name', ['home', 'upload_url_path'])
            ->pluck('option_value', 'option_name');
        $uploadsUrl = rtrim((string) ($options['upload_url_path'] ?? '') ?: rtrim((string) ($options['home'] ?? UrlMap::SITE_URL), '/').'/wp-content/uploads', '/');

        $indexables = $this->db->table('yoast_indexable')->whereNotNull('permalink')->orderBy('id')->get();
        $postIds = $indexables->where('object_type', 'post')->pluck('object_id')->map(fn ($id): int => (int) $id)->all();

        $posts = $this->db->table('posts')->whereIn('ID', $postIds)
            ->get(['ID', 'post_name', 'post_title', 'post_status', 'post_date', 'post_content', 'post_type'])
            ->keyBy('ID');
        $meta = $this->meta($postIds);
        $oldValues = $this->oldValues($postIds);
        $terms = $this->db->table('terms')
            ->join('term_taxonomy', 'term_taxonomy.term_id', '=', 'terms.term_id')
            ->get(['terms.term_id', 'terms.name', 'terms.slug', 'term_taxonomy.taxonomy', 'term_taxonomy.term_taxonomy_id']);
        $termsById = $terms->keyBy('term_id');
        $relations = $this->db->table('term_relationships')
            ->join('posts', 'posts.ID', '=', 'term_relationships.object_id')
            ->where('posts.post_status', 'publish')
            ->orderBy('term_relationships.term_order')
            ->orderBy('term_relationships.term_taxonomy_id')
            ->get(['term_relationships.object_id', 'term_relationships.term_taxonomy_id', 'posts.post_type']);
        $primaryTerms = $this->db->table('yoast_primary_term')->get(['post_id', 'term_id', 'taxonomy']);
        $attachments = $this->attachments();

        $scanner = new SpamScanner($this->clientDomains());
        $classifier = new UrlClassifier($terms->where('taxonomy', 'category')->pluck('slug')->all());
        $map = new UrlMap;
        $legacyIds = [];
        $oldUrls = [];
        $unpublished = 0;
        $spam = ['hits' => [], 'domains' => []];

        foreach ($indexables as $ix) {
            if (str_contains((string) $ix->permalink, '?')) {
                $unpublished++; // A draft: Yoast gives it a `?p=` link, not a public URL.

                continue;
            }

            $path = Redirect::normalisePath($ix->permalink);
            $post = $ix->object_type === 'post' ? $posts->get($ix->object_id) : null;
            $term = $ix->object_type === 'term' ? $termsById->get($ix->object_id) : null;
            $slug = $post->post_name ?? $term->slug ?? (string) basename($path);

            $rule = $classifier->classify($ix->object_type, $ix->object_sub_type, $slug, $path);
            $row = [
                'old_url' => $ix->permalink,
                'old_path' => $path,
                'sources' => 'yoast',
                'object_type' => $ix->object_type,
                'object_sub_type' => $ix->object_sub_type,
                'wp_id' => $ix->object_id,
                'wp_status' => $post->post_status ?? '',
                'noindex' => $ix->is_robots_noindex,
                'title' => $ix->title ?: ($ix->breadcrumb_title ?: ($post->post_title ?? $term->name ?? '')),
                'meta_description' => $ix->description,
                'focus_keyword' => $ix->primary_focus_keyword,
                'canonical' => $ix->canonical,
                'inbound_internal_links' => $ix->incoming_link_count,
                ...$rule,
            ];
            $notes = [$rule['notes']];

            if ($post !== null) {
                $postMeta = $meta[$post->ID] ?? [];
                $source = ContentSource::detect($post->post_content, $postMeta['_elementor_data'] ?? null);
                $fields = $this->contentFields($postMeta);
                $hits = $this->scanPost($scanner, $post, $postMeta['_elementor_data'] ?? '', $fields, $path, $spam);

                $row['published_at'] = str_starts_with((string) $post->post_date, '0000') ? '' : $post->post_date;
                $row['primary_category'] = $this->primaryCategory($post, $primaryTerms, $relations, $terms);
                $row['content_source'] = $source->value;
                $row['has_elementor'] = ContentSource::hasElementorData($postMeta['_elementor_data'] ?? null) ? '1' : '0';
                $row['images'] = $this->imageCount($post->post_content.($postMeta['_elementor_data'] ?? '').implode("\n", $fields), $postMeta, $attachments);
                $row['spam_flags'] = $this->flags($hits);

                if ($source === ContentSource::Empty && $fields !== []) {
                    $notes[] = 'Body kept in custom fields (Pods postmeta), not in post_content';
                }

                $old = $oldValues[(int) $post->ID] ?? ['slugs' => [], 'dates' => []];
                $bodyLength = mb_strlen(trim(strip_tags((string) $post->post_content)));

                $firstDate = $old['dates'] !== [] ? min($old['dates']) : null;

                if ($post->post_type === 'post' && $firstDate !== null && $firstDate < substr((string) $post->post_date, 0, 10)) {
                    $notes[] = "First published {$firstDate} (_wp_old_date); WordPress later re-dated it";
                }

                if ($rule['decision'] === 'gone' && $bodyLength > 500) {
                    $notes[] = "Has a real body ({$bodyLength} characters): check it is junk before the 410"
                        .($old['slugs'] !== [] ? '. Old slug(s): '.implode(', ', $old['slugs']) : '');
                }

                if ($rule['target_path'] !== '') {
                    $legacyIds[(int) $post->ID] = $rule['target_path'];

                    foreach (UrlClassifier::oldPaths($path, $post->post_type, $post->post_name, $old['slugs'], $old['dates']) as $oldPath) {
                        $oldUrls[] = ['old_path' => $oldPath, 'wp_id' => $post->ID, 'title' => $post->post_title, 'target_path' => $rule['target_path'], 'object_sub_type' => $post->post_type];
                    }
                }
            }

            if ($term !== null && in_array($term->taxonomy, ['category', 'post_tag'], true)) {
                $row['article_count'] = $relations->where('term_taxonomy_id', $term->term_taxonomy_id)->where('post_type', 'post')->count();

                if ($row['article_count'] === 0 && $rule['decision'] === 'category') {
                    $notes[] = 'Holds no published articles';
                }
            }

            $row['notes'] = implode(' | ', array_filter($notes));
            $map->add($row);
        }

        foreach (UrlClassifier::wordpressShapes() as $shape) {
            $map->add(['sources' => 'wp-shape', 'object_type' => 'wordpress', ...$shape]);
        }

        $oldUrlRows = 0;

        foreach ($oldUrls as $old) {
            // An old slug another post has since taken is that post's URL now.
            if (! $map->has($old['old_path'])) {
                $oldUrlRows += (int) $map->add([
                    ...$old,
                    'sources' => 'wp-old-url',
                    'object_type' => 'post',
                    'decision' => 'redirect',
                    'notes' => 'Old address of this post (_wp_old_slug / _wp_old_date): WordPress redirected it to the current one',
                ]);
            }
        }

        $attachmentPages = $this->addAttachmentPages($map, $attachments);
        $extra = ['analytics' => $this->analyticsUrls(), ...ExtraUrlSources::read($directory)];
        $skippedExtra = $map->mergeExtra($extra);
        $this->flagSpamlikeUrls($map, $scanner);

        ksort($legacyIds);
        $archive = $this->uploadsArchive();
        $media = $this->mediaSource($attachments, $uploadsUrl, $archive);

        $this->writeCsv("{$directory}/url-map.csv", $map);
        $this->writeJson("{$directory}/legacy-ids.json", (object) $legacyIds);
        $this->writeJson("{$directory}/media-source.json", $media);
        $this->writeJson("{$directory}/spam-report.json", $this->spamReport($spam, $scanner));

        $this->summarise($map, $spam, $media, $attachmentPages, $oldUrlRows, $unpublished, $extra, $skippedExtra, $directory);

        return self::SUCCESS;
    }

    /**
     * The postmeta the inventory reads, per post.
     *
     * @param  list<int>  $postIds
     * @return array<int, array<string, string>>
     */
    private function meta(array $postIds): array
    {
        $meta = [];

        $this->db->table('postmeta')->whereIn('post_id', $postIds)->orderBy('meta_id')
            ->get(['post_id', 'meta_key', 'meta_value'])
            ->each(function (object $row) use (&$meta): void {
                $meta[(int) $row->post_id][(string) $row->meta_key] = (string) $row->meta_value;
            });

        return $meta;
    }

    /**
     * Each post's earlier slugs and dates, from which WordPress kept
     * redirecting its old addresses.
     *
     * @param  list<int>  $postIds
     * @return array<int, array{slugs: list<string>, dates: list<string>}>
     */
    private function oldValues(array $postIds): array
    {
        $old = [];

        $this->db->table('postmeta')->whereIn('post_id', $postIds)
            ->whereIn('meta_key', ['_wp_old_slug', '_wp_old_date'])->orderBy('meta_id')
            ->get(['post_id', 'meta_key', 'meta_value'])
            ->each(function (object $row) use (&$old): void {
                $old[(int) $row->post_id] ??= ['slugs' => [], 'dates' => []];
                $old[(int) $row->post_id][$row->meta_key === '_wp_old_slug' ? 'slugs' : 'dates'][] = (string) $row->meta_value;
            });

        return $old;
    }

    /**
     * Page paths from the Google Analytics reports MonsterInsights cached in
     * the options table.
     *
     * @return list<string>
     */
    private function analyticsUrls(): array
    {
        return $this->db->table('options')->where('option_name', 'like', 'monsterinsights\_report\_data\_%')
            ->pluck('option_value')
            ->flatMap(fn (?string $value): array => ExtraUrlSources::fromAnalytics(AttachmentMetadata::unserialise($value)))
            ->unique()->values()->all();
    }

    /**
     * Marks the undecided URLs whose own address reads like injected spam,
     * such as the deleted `/2025/11/13/das-abenteuer-im-lucky-hunter-casino/`.
     */
    private function flagSpamlikeUrls(UrlMap $map, SpamScanner $scanner): void
    {
        foreach ($map->rows() as $path => $row) {
            if ($row['decision'] !== '') {
                continue;
            }

            $words = array_column($scanner->scan(str_replace(['/', '-'], ' ', $path))['hits'], 'detail');

            if ($words !== []) {
                $map->update($path, [
                    'spam_flags' => 'url:'.implode(', ', array_unique($words)),
                    'notes' => $row['notes'].' | The address reads like injected spam: 410 suggested',
                ]);
            }
        }
    }

    /**
     * Custom fields that hold page content (the `domenii` keep their whole
     * body in Pods fields): public keys, minus caching plugins' bookkeeping.
     *
     * @param  array<string, string>  $postMeta
     * @return array<string, string>
     */
    private function contentFields(array $postMeta): array
    {
        return array_filter(
            $postMeta,
            fn (string $value, string $key): bool => ! str_starts_with($key, '_')
                && ! str_starts_with($key, 'litespeed')
                && ! is_numeric($value)
                && trim($value) !== '',
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * @param  array<string, string>  $fields
     * @param  array{hits: list<array<string, mixed>>, domains: array<string, array{count: int, posts: list<int>}>}  $spam
     * @return list<array{type: string, detail: string, snippet: string}>
     */
    private function scanPost(SpamScanner $scanner, object $post, string $elementorData, array $fields, string $path, array &$spam): array
    {
        $results = [
            'post_title' => $scanner->scan((string) $post->post_title),
            'post_content' => $scanner->scan((string) $post->post_content),
            '_elementor_data' => ContentSource::hasElementorData($elementorData) ? $scanner->scanElementor($elementorData) : ['hits' => [], 'domains' => []],
            'custom_fields' => $scanner->scan(implode("\n", $fields)),
        ];

        $hits = [];

        foreach ($results as $field => $result) {
            foreach ($result['hits'] as $hit) {
                $hits[] = $hit;
                $spam['hits'][] = [
                    'wp_id' => (int) $post->ID,
                    'post_type' => $post->post_type,
                    'slug' => $post->post_name,
                    'old_path' => $path,
                    'field' => $field,
                    ...$hit,
                ];
            }

            foreach ($result['domains'] as $domain => $count) {
                $spam['domains'][$domain]['count'] = ($spam['domains'][$domain]['count'] ?? 0) + $count;
                $spam['domains'][$domain]['posts'][] = (int) $post->ID;
            }
        }

        return $hits;
    }

    /** @param list<array{type: string, detail: string, snippet: string}> $hits */
    private function flags(array $hits): string
    {
        $flags = array_map(fn (array $hit): string => $hit['type'].($hit['detail'] !== '' ? ':'.$hit['detail'] : ''), $hits);

        return implode('; ', array_values(array_unique($flags)));
    }

    /**
     * The post's primary category slug: Yoast's pick, else its first assigned one.
     *
     * @param  Collection<int, object>  $primaryTerms
     * @param  Collection<int, object>  $relations
     * @param  Collection<int, object>  $terms
     */
    private function primaryCategory(object $post, Collection $primaryTerms, Collection $relations, Collection $terms): string
    {
        $taxonomy = self::CATEGORY_TAXONOMY[$post->post_type] ?? null;

        if ($taxonomy === null) {
            return '';
        }

        $primary = $primaryTerms->first(fn (object $row): bool => (int) $row->post_id === (int) $post->ID && $row->taxonomy === $taxonomy);
        $term = $primary !== null
            ? $terms->first(fn (object $t): bool => (int) $t->term_id === (int) $primary->term_id && $t->taxonomy === $taxonomy)
            : null;

        if ($term === null) {
            $assigned = $relations->where('object_id', $post->ID)->pluck('term_taxonomy_id')->all();
            $term = $terms->first(fn (object $t): bool => $t->taxonomy === $taxonomy && in_array($t->term_taxonomy_id, $assigned));
        }

        return $term->slug ?? '';
    }

    /**
     * Distinct uploaded images a post uses: those its content links (resized
     * copies counted once, as their original), its featured image and the
     * attachment ids in its Pods galleries.
     *
     * @param  array<string, string>  $postMeta
     * @param  array<int, object>  $attachments
     */
    private function imageCount(string $content, array $postMeta, array $attachments): int
    {
        $files = [];
        $content = str_replace('\/', '/', $content);

        if (preg_match_all('#wp-content/uploads/([^"\'\s()<>\\\\?\#]+\.(?:jpe?g|png|gif|webp|svg|avif))#i', $content, $matches)) {
            foreach ($matches[1] as $file) {
                $files[self::originalFile($file)] = true;
            }
        }

        $ids = [];

        foreach ($postMeta as $key => $value) {
            if ($key === '_thumbnail_id' && is_numeric($value)) {
                $ids[] = (int) $value;
            } elseif (str_starts_with($key, '_pods_')) {
                $ids = [...$ids, ...array_filter(AttachmentMetadata::unserialise($value), 'is_numeric')];
            }
        }

        foreach ($ids as $id) {
            $attachment = $attachments[(int) $id] ?? null;

            if ($attachment !== null && str_starts_with((string) $attachment->post_mime_type, 'image/')) {
                $files[self::originalFile($attachment->file)] = true;
            }
        }

        return count($files);
    }

    private static function originalFile(string $file): string
    {
        return strtolower((string) preg_replace('/(-\d+x\d+|-scaled)(?=\.[a-z0-9]+$)/i', '', $file));
    }

    /**
     * Every attachment with the postmeta that describes its files.
     *
     * @return array<int, object>
     */
    private function attachments(): array
    {
        $attachments = $this->db->table('posts')->where('post_type', 'attachment')->orderBy('ID')
            ->get(['ID', 'post_name', 'post_title', 'post_parent', 'post_mime_type', 'guid'])
            ->keyBy('ID')->all();

        $keys = ['_wp_attached_file' => 'file', '_wp_attachment_metadata' => 'metadata', '_wp_attachment_image_alt' => 'alt', '_wp_attachment_backup_sizes' => 'backup_sizes'];

        foreach ($attachments as $attachment) {
            foreach ($keys as $property) {
                $attachment->{$property} = '';
            }
        }

        $this->db->table('postmeta')->whereIn('meta_key', array_keys($keys))
            ->whereIn('post_id', array_keys($attachments))
            ->get(['post_id', 'meta_key', 'meta_value'])
            ->each(function (object $row) use ($attachments, $keys): void {
                $attachments[$row->post_id]->{$keys[$row->meta_key]} = (string) $row->meta_value;
            });

        return $attachments;
    }

    /**
     * The clients' own sites, as the projects record them: links to them are
     * expected, not spam.
     *
     * @return list<string>
     */
    private function clientDomains(): array
    {
        return $this->db->table('postmeta')
            ->join('posts', 'posts.ID', '=', 'postmeta.post_id')
            ->where('posts.post_type', 'proiect')
            ->whereIn('postmeta.meta_key', ['website_link', 'text_link'])
            ->pluck('postmeta.meta_value')
            ->map(fn (?string $value): string => SpamScanner::host(trim((string) $value)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * One exact row per attachment page, at its live parent's URL plus its
     * own name. Unattached files and those under unpublished parents had no
     * page to keep.
     *
     * @param  array<int, object>  $attachments
     */
    private function addAttachmentPages(UrlMap $map, array $attachments): int
    {
        $parentPaths = [];

        foreach ($map->rows() as $row) {
            if ($row['object_type'] === 'post' && $row['wp_id'] !== '' && $row['wp_status'] === 'publish') {
                $parentPaths[(int) $row['wp_id']] = $row['old_path'];
            }
        }

        $added = 0;

        foreach ($attachments as $attachment) {
            $parentPath = $parentPaths[(int) $attachment->post_parent] ?? null;

            if ($parentPath === null || $attachment->post_name === '') {
                continue;
            }

            $added += (int) $map->add([
                'old_path' => UrlClassifier::attachmentPagePath($parentPath, $attachment->post_name),
                'sources' => 'wordpress',
                'object_type' => 'post',
                'object_sub_type' => 'attachment',
                'wp_id' => $attachment->ID,
                'wp_status' => 'inherit',
                'title' => $attachment->post_title,
                'decision' => 'gone',
                'notes' => 'Attachment page (WordPress redirected these to the file; 410 on the new site)',
            ]);
        }

        return $added;
    }

    /**
     * The backup of the old site's files, when it is on disk: `--archive`, or
     * `webis-export/webis-bk.zip` by default. Only its index is read.
     */
    private function uploadsArchive(): ?UploadsArchive
    {
        $path = (string) ($this->option('archive') ?: base_path('webis-export/webis-bk.zip'));

        return is_file($path) ? UploadsArchive::fromZip($path) : null;
    }

    /**
     * Every attachment, with whether its original and each derivative are in
     * the backup archive (null throughout when there is no archive).
     *
     * @param  array<int, object>  $attachments
     * @return list<array<string, mixed>>
     */
    private function mediaSource(array $attachments, string $uploadsUrl, ?UploadsArchive $archive): array
    {
        $media = [];

        foreach ($attachments as $attachment) {
            $file = $attachment->file !== '' ? $attachment->file : (string) (AttachmentMetadata::unserialise($attachment->metadata)['file'] ?? '');
            $derivatives = array_map(
                fn (array $derivative): array => [...$derivative, 'in_archive' => $archive?->has($derivative['file'])],
                AttachmentMetadata::derivatives($attachment->metadata, $file, $uploadsUrl, $attachment->backup_sizes),
            );

            $media[] = [
                'wp_id' => (int) $attachment->ID,
                'url' => $file !== '' ? AttachmentMetadata::url($uploadsUrl, $file) : (string) $attachment->guid,
                'file' => $file,
                'mime_type' => (string) $attachment->post_mime_type,
                'alt' => $attachment->alt,
                'title' => (string) $attachment->post_title,
                'post_parent' => (int) $attachment->post_parent,
                'in_archive' => $archive === null ? null : ($file !== '' && $archive->has($file)),
                'derivatives_in_archive' => $archive === null ? null : count(array_filter(array_column($derivatives, 'in_archive'))),
                'derivatives' => $derivatives,
            ];
        }

        return $media;
    }

    /**
     * @param  array{hits: list<array<string, mixed>>, domains: array<string, array{count: int, posts: list<int>}>}  $spam
     * @return array<string, mixed>
     */
    private function spamReport(array $spam, SpamScanner $scanner): array
    {
        $domains = [];

        foreach ($spam['domains'] as $domain => $info) {
            $domains[] = [
                'domain' => $domain,
                'links' => $info['count'],
                'posts' => count(array_unique($info['posts'])),
                'allowed' => $scanner->isAllowed($domain),
                'post_ids' => array_values(array_unique($info['posts'])),
            ];
        }

        usort($domains, fn (array $a, array $b): int => [$b['links'], $a['domain']] <=> [$a['links'], $b['domain']]);

        $byType = array_count_values(array_column($spam['hits'], 'type'));
        arsort($byType);

        return [
            'generated_at' => now()->toIso8601String(),
            'scanned' => 'post_title, post_content, _elementor_data and public custom fields of every URL-map row backed by a post',
            'hits_by_type' => $byType,
            'hits' => $spam['hits'],
            'outbound_domains' => $domains,
        ];
    }

    private function writeCsv(string $path, UrlMap $map): void
    {
        $handle = fopen($path, 'w');
        $map->writeCsv($handle);
        fclose($handle);
    }

    private function writeJson(string $path, mixed $data): void
    {
        File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)."\n");
    }

    /**
     * @param  array{hits: list<array<string, mixed>>, domains: array<string, mixed>}  $spam
     * @param  list<array<string, mixed>>  $media
     * @param  array<string, list<string>>  $extra
     */
    private function summarise(UrlMap $map, array $spam, array $media, int $attachmentPages, int $oldUrlRows, int $unpublished, array $extra, int $skippedExtra, string $directory): void
    {
        $rows = collect($map->rows());

        $this->info("Wrote {$rows->count()} URL rows to {$directory}");

        $this->table(['object_type', 'object_sub_type', 'rows'], $rows->countBy(fn (array $row): string => ($row['object_type'] ?: '(extra source only)')."\t".$row['object_sub_type'])
            ->sortKeys()->map(fn (int $n, string $type): array => [...explode("\t", $type, 2), $n])->values()->all());

        $this->table(['decision', 'rows'], $rows->countBy(fn (array $row): string => $row['decision'] ?: '(needs decision)')
            ->sortKeys()->map(fn (int $n, string $decision): array => [$decision, $n])->values()->all());

        $undecided = $rows->filter(fn (array $row): bool => $row['decision'] === '');
        $this->line("Rows needing a manual decision: {$undecided->count()}");
        $undecided->each(fn (array $row) => $this->line("  {$row['old_path']} — {$row['notes']}"));

        $this->line("Skipped {$unpublished} unpublished Yoast row(s) (drafts with a ?p= link, no public URL).");
        $this->line("Attachment pages: {$attachmentPages} exact row(s).");
        $this->line("Old addresses of live posts (old slugs, date-based permalinks): {$oldUrlRows} row(s).");
        $this->line('Google Analytics paths cached by MonsterInsights: '.count($extra['analytics'] ?? []).'.');

        foreach (ExtraUrlSources::FILES as $label => $file) {
            $this->line(isset($extra[$label]) ? "Extra source {$file}: ".count($extra[$label]).' URL(s).' : "Extra source {$file}: not present.");
        }

        if ($extra !== []) {
            $this->line("Extra-source URLs skipped as off-site, media or bot noise: {$skippedExtra}.");
        }

        $flagged = $rows->filter(fn (array $row): bool => $row['spam_flags'] !== '')->count();
        $this->line('Spam scan: '.count($spam['hits'])." hit(s) on {$flagged} row(s).");

        foreach (array_count_values(array_column($spam['hits'], 'type')) as $type => $n) {
            $this->line("  {$type}: {$n}");
        }

        $derivatives = array_sum(array_map(fn (array $item): int => count($item['derivatives']), $media));
        $this->line('Attachments: '.count($media).", derivative URLs: {$derivatives}.");

        if ($media !== [] && $media[0]['in_archive'] === null) {
            $this->line('Uploads archive: not present, so nothing was checked against it.');

            return;
        }

        $originals = count(array_filter(array_column($media, 'in_archive')));
        $derivativesPresent = array_sum(array_column($media, 'derivatives_in_archive'));
        $this->line('In the uploads archive: originals '.$originals.' present, '.(count($media) - $originals).' missing; '
            ."derivatives {$derivativesPresent} present, ".($derivatives - $derivativesPresent).' missing.');
    }
}
