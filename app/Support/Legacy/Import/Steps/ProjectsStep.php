<?php

namespace App\Support\Legacy\Import\Steps;

use App\Enums\ClientType;
use App\Enums\ContentStatus;
use App\Enums\ProjectType;
use App\Models\Client;
use App\Models\Project;
use App\Support\Legacy\AttachmentMetadata;
use App\Support\Legacy\Import\ImportContext;
use App\Support\Legacy\Import\LegacyPost;
use App\Support\Legacy\Import\ReviewedMap;
use App\Support\Legacy\Import\SpamReview;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The 80 portfolio projects (`proiect` posts): classic HTML of a sentence or
 * two, plus Pods fields — `website_link` / `text_link`, a `galerie_logo`
 * gallery on the logo projects, and on a few `cercetare_si_planificare` /
 * `dezvoltare_i_implementare` (what the client needed, what was built).
 *
 * The portfolio cards come first: title, type, client, cover, link, summary.
 * Projects are keyed by slug; a re-run refreshes title, summary, SEO and date,
 * and fills the rest only where it is still empty (or with --force).
 */
final class ProjectsStep
{
    public const string NAME = 'projects';

    /** `categorie_proiect` term → the portfolio filter. */
    public const array TYPES = [
        'magazin-online' => ProjectType::Shop,
        'site-de-prezentare' => ProjectType::Presentation,
        'logo-design' => ProjectType::Logo,
    ];

    /** Pods `tip_de_proiect` labels, should a project have no term. */
    private const array TYPE_LABELS = [
        'magazin online' => ProjectType::Shop,
        'site de prezentare' => ProjectType::Presentation,
        'logo design' => ProjectType::Logo,
    ];

    /** Fields only filled while empty, or replaced with --force. */
    private const array CONTENT_FIELDS = ['type', 'url', 'year', 'client_id', 'cover_asset_id', 'images', 'need', 'built'];

    /** @var Collection<int, Client> */
    private Collection $clients;

    public function run(ImportContext $context): void
    {
        $report = $context->report;
        $rows = $context->map->decided('project');
        $ids = array_values(array_filter(array_map(fn (array $row): int => (int) $row['wp_id'], $rows)));
        $posts = $context->legacy->posts($ids);
        $terms = $context->legacy->postTerms($ids, 'categorie_proiect');
        $yoastTitles = $context->legacy->yoastTitles('post', $ids);
        $this->clients = Client::query()->get();

        // Websites before logos, so a client is named after its site's
        // project ("Implantofix Dental Clinic"), not its logo's.
        usort($rows, fn (array $a, array $b): int => (int) ($this->typeOf($a, null, []) === ProjectType::Logo) <=> (int) ($this->typeOf($b, null, []) === ProjectType::Logo));

        foreach ($rows as $row) {
            $post = $posts[(int) $row['wp_id']] ?? null;

            if (! SpamReview::allows($row)) {
                $report->count(self::NAME, 'refused_spam');
                $report->add(self::NAME, 'refused', ['path' => $row['old_path'], 'reason' => 'spam flag not cleared: '.$row['spam_flags']]);

                continue;
            }

            if ($post === null) {
                $report->count(self::NAME, 'missing_post');
                $report->add(self::NAME, 'refused', ['path' => $row['old_path'], 'reason' => "post #{$row['wp_id']} not in the legacy database"]);

                continue;
            }

            $this->importOne($context, $row, $post, $terms[$post->id] ?? [], $yoastTitles[$post->id] ?? '');
        }
    }

    /**
     * @param  array<string, string>  $row
     * @param  list<string>  $terms
     */
    private function importOne(ImportContext $context, array $row, LegacyPost $post, array $terms, string $yoastTitle): void
    {
        $report = $context->report;
        $slug = ReviewedMap::targetSlug($row);
        $existing = Project::withTrashed()->where('slug', $slug)->first();

        if ($existing?->trashed()) {
            $report->count(self::NAME, 'exists_kept');
            $report->add(self::NAME, 'exists_kept', ['slug' => $slug, 'reason' => 'a deleted project holds the slug']);

            return;
        }

        $title = $post->title !== '' ? $post->title : $row['title'];
        $type = $this->typeOf($row, $post, $terms);
        $url = $this->website($post);
        $date = ImportContext::legacyDate($row['published_at'] !== '' ? $row['published_at'] : $post->date);
        $images = $this->gallery($context, $post);

        $values = [
            'title' => $title,
            'summary' => $this->summary($post->content),
            'published_at' => $date,
            'seo' => $context->legacy->yoast()->seo([
                'title' => $yoastTitle,
                'description' => $row['meta_description'] ?? '',
                'canonical' => $row['canonical'] ?? '',
                'noindex' => $row['noindex'] ?? '',
                'focus_keyword' => $row['focus_keyword'] ?? '',
            ], $title, fn (string $canonical) => $report->add(self::NAME, 'canonicals_discarded', ['path' => $row['old_path'], 'canonical' => $canonical])),
        ];

        $content = [
            'type' => $type,
            'url' => $url,
            'year' => $date?->setTimezone(ImportContext::LEGACY_ZONE)->year,
            'client_id' => $this->client($title, $url, $post)->id,
            'cover_asset_id' => $context->assetForAttachment($post->thumbnailId()),
            'images' => $images !== [] ? $images : null,
            'need' => $this->plain($post->meta('cercetare_si_planificare')),
            'built' => $this->plain($post->meta('dezvoltare_i_implementare')),
        ];

        if ($type === null) {
            $report->add(self::NAME, 'no_type', $slug);
        }

        if ($post->thumbnailId() !== null && $content['cover_asset_id'] === null) {
            $report->count(self::NAME, 'cover_unmapped');
            $report->add(self::NAME, 'cover_unmapped', ['slug' => $slug, 'attachment' => $post->thumbnailId()]);
        }

        $project = $existing ?? new Project(['slug' => $slug, 'status' => ContentStatus::Draft]);
        $project->fill($values);

        foreach (self::CONTENT_FIELDS as $field) {
            $current = $project->getAttribute($field);

            if ($content[$field] !== null && ($context->force || $current === null || $current === '' || $current === [])) {
                $project->setAttribute($field, $content[$field]);
            }
        }

        if ($context->publish) {
            $project->status = ContentStatus::Published;
        }

        $state = $project->exists ? ($project->isDirty() ? 'updated' : 'unchanged') : 'created';
        $project->save();

        $report->count(self::NAME, $state);
        $report->count(self::NAME, 'type_'.($project->type->value ?? 'none'));
    }

    /**
     * The portfolio filter: the reviewed map's primary category, else the
     * post's own `categorie_proiect` terms, else its Pods label.
     *
     * @param  array<string, string>  $row
     * @param  list<string>  $terms
     */
    private function typeOf(array $row, ?LegacyPost $post, array $terms): ?ProjectType
    {
        foreach ([$row['primary_category'] ?? '', ...$terms] as $slug) {
            if (isset(self::TYPES[$slug])) {
                return self::TYPES[$slug];
            }
        }

        $label = Str::lower(trim((string) $post?->meta('tip_de_proiect')));

        return self::TYPE_LABELS[$label] ?? null;
    }

    /** The live site: `website_link`, else the displayed `text_link`. */
    private function website(LegacyPost $post): ?string
    {
        foreach ([$post->meta('website_link'), $post->meta('text_link')] as $value) {
            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            if (! preg_match('~^https?://~i', $value)) {
                $value = 'https://'.ltrim($value, '/');
            }

            if (filter_var($value, FILTER_VALIDATE_URL) !== false && parse_url($value, PHP_URL_HOST)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * The gallery's assets, in the order Pods kept (`_pods_galerie_logo`),
     * else the order of the meta rows.
     *
     * @return list<int>
     */
    private function gallery(ImportContext $context, LegacyPost $post): array
    {
        $order = array_values(array_filter(AttachmentMetadata::unserialise($post->meta('_pods_galerie_logo')), is_numeric(...)));
        $ids = $order !== [] ? $order : array_values(array_filter($post->metaValues('galerie_logo'), ctype_digit(...)));
        $assets = [];

        foreach ($ids as $id) {
            $assetId = $context->assetForAttachment((int) $id);

            if ($assetId === null) {
                $context->report->add(self::NAME, 'gallery_unmapped', ['post' => $post->id, 'attachment' => (int) $id]);

                continue;
            }

            $assets[] = $assetId;
        }

        return array_values(array_unique($assets));
    }

    /**
     * The client: the one already holding this website, else the one of that
     * name, else a new one named after the project ("Zyanya Logo" → "Zyanya").
     * New clients stay out of the logo strip until someone gives them a logo.
     */
    private function client(string $title, ?string $url, LegacyPost $post): Client
    {
        $name = trim((string) preg_replace(['/\blogo\b/iu', '/\s+/u'], ['', ' '], $title), " \t-–|");
        $name = $name !== '' ? $name : $title;
        $host = self::host($url);

        $client = ($host !== null ? $this->clients->first(fn (Client $client): bool => self::host($client->url) === $host) : null)
            ?? $this->clients->first(fn (Client $client): bool => Str::lower($client->name) === Str::lower($name));

        $sector = $this->plain($post->meta('domeniu_de_activitate'));

        if ($client === null) {
            $client = Client::query()->create([
                'name' => $name,
                'url' => $url,
                'sector' => $sector,
                'type' => ClientType::Company,
                'show_in_logos' => false,
            ]);
            $this->clients->push($client);

            return $client;
        }

        $client->fill(array_filter([
            'url' => $client->url ?: $url,
            'sector' => $client->sector ?: $sector,
        ]));

        if ($client->isDirty()) {
            $client->save();
        }

        return $client;
    }

    /**
     * One or two sentences of the post's text, for the portfolio card.
     */
    private function summary(string $html): ?string
    {
        $text = $this->plain($html);

        if ($text === null) {
            return null;
        }

        $sentences = preg_split('/(?<=[.!?])\s+(?=\p{Lu})/u', $text) ?: [$text];
        $summary = $sentences[0];

        if (isset($sentences[1]) && mb_strlen($summary.' '.$sentences[1]) <= 320) {
            $summary .= ' '.$sentences[1];
        }

        return Str::limit($summary, 320, '…', preserveWords: true);
    }

    /** HTML or a Pods field as plain text: tags, shortcodes and entities gone. */
    private function plain(?string $value): ?string
    {
        $text = (string) preg_replace('/\[[^\]]+\]/', ' ', (string) $value);
        $text = html_entity_decode(strip_tags((string) preg_replace('/<(br|\/p|\/li|\/h\d)\b[^>]*>/i', ' $0', $text)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/\s+/u', ' ', str_replace("\u{a0}", ' ', $text)));

        return $text === '' ? null : $text;
    }

    private static function host(?string $url): ?string
    {
        $host = Str::lower((string) parse_url((string) $url, PHP_URL_HOST));

        return $host === '' ? null : (str_starts_with($host, 'www.') ? substr($host, 4) : $host);
    }
}
