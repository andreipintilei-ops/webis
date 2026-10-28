<?php

namespace App\Support\Legacy;

/**
 * The pre-filled decision for each old WordPress URL: what it becomes on the
 * new site and where it lands. Only a starting point — the URL map is
 * reviewed by hand, and the reviewed file is what everything downstream reads.
 *
 * Targets carry the trailing slash, the canonical form on the new site.
 */
final class UrlClassifier
{
    /** Spam injection and leftovers: answered with 410, never redirected into real pages. */
    public const array JUNK_SLUGS = ['wazambacasinoaustralia-org', '12705-2', 'uncategorized'];

    /** Old root paths the new site already serves, and where each lands there. */
    public const array LIVE_PATHS = [
        '/blog' => '/blog/',
        '/contact' => '/contact/',
        '/portofoliu' => '/portofoliu/',
        '/proiect' => '/portofoliu/',
    ];

    public const string NEEDS_DECISION = 'Needs a manual decision';

    /**
     * @param  list<string>  $categorySlugs  every WordPress category, so tags can follow their namesakes
     */
    public function __construct(private readonly array $categorySlugs = []) {}

    /**
     * @return array{decision: string, target_path: string, notes: string}
     */
    public function classify(string $objectType, ?string $subType, string $slug, string $oldPath): array
    {
        if (in_array($slug, self::JUNK_SLUGS, true)) {
            return $this->result('gone', '', 'Junk (spam injection or leftover): 410, never redirected');
        }

        if ($objectType === 'home-page' || $oldPath === '/') {
            return $this->result('skip', '/', 'Home page');
        }

        if (array_key_exists($oldPath, self::LIVE_PATHS)) {
            $note = $oldPath === '/proiect'
                ? 'Already live: the /proiect route redirects to /portofoliu/'
                : 'Already live on the new site';

            return $this->result('skip', self::LIVE_PATHS[$oldPath], $note);
        }

        return match ([$objectType, $subType]) {
            ['post', 'proiect'] => $this->result('project', "/proiect/{$slug}/"),
            ['post', 'post'] => $this->result('article', "/{$slug}/"),
            ['post', 'domenii'] => $this->result('industry', "/servicii/{$slug}/"),
            ['post', 'page'] => $slug === 'politica-de-confidentialitate'
                ? $this->result('legal', "/{$slug}/")
                : $this->result('page', "/{$slug}/"),
            ['post', 'carte'] => $this->result('', '', 'Open item: /carte pages, real pages or a prefix redirect? '.self::NEEDS_DECISION),
            ['term', 'category'] => $this->result('category', "/blog/categorie/{$slug}/"),
            ['term', 'post_tag'] => $this->tag($slug),
            ['term', 'categorie_proiect'] => $this->result('redirect', '/portofoliu/'),
            ['post-type-archive', 'proiect'] => $this->result('skip', '/portofoliu/', 'Already live: the /proiect route redirects to /portofoliu/'),
            default => $this->result('', '', "No rule for {$objectType}/{$subType}. ".self::NEEDS_DECISION),
        };
    }

    /**
     * The URL shapes WordPress answers on that Yoast never lists. Prefix rows
     * say so in their notes; everything else is an exact path.
     *
     * @return list<array{old_path: string, object_sub_type: string, decision: string, target_path: string, notes: string}>
     */
    public static function wordpressShapes(): array
    {
        return [
            ['old_path' => '/feed', 'object_sub_type' => 'feed', 'decision' => 'gone', 'target_path' => '', 'notes' => 'WordPress feed: the new site publishes no RSS'],
            ['old_path' => '/comments/feed', 'object_sub_type' => 'feed', 'decision' => 'gone', 'target_path' => '', 'notes' => 'WordPress comments feed: the new site publishes no RSS'],
            ['old_path' => '/page', 'object_sub_type' => 'pagination', 'decision' => 'redirect', 'target_path' => '/blog/', 'notes' => 'Prefix row: /page/N/ pagination of the old blog home'],
            ['old_path' => '/author', 'object_sub_type' => 'author', 'decision' => 'redirect', 'target_path' => '/blog/', 'notes' => 'Prefix row: author archives'],
        ];
    }

    /**
     * The addresses a post had before its current one, which WordPress kept
     * answering with a redirect: each `_wp_old_slug`, and for blog posts the
     * date-based permalinks of the site's earlier `/%year%/%monthnum%/%day%/%postname%/`
     * structure, built from each `_wp_old_date`. Numeric old slugs are
     * WordPress's placeholder names for drafts, never public.
     *
     * @param  list<string>  $oldSlugs
     * @param  list<string>  $oldDates  `Y-m-d`
     * @return list<string>
     */
    public static function oldPaths(string $currentPath, string $postType, string $slug, array $oldSlugs, array $oldDates): array
    {
        $oldSlugs = array_values(array_filter($oldSlugs, fn (string $old): bool => $old !== '' && $old !== $slug && ! ctype_digit($old)));
        $base = substr($currentPath, 0, (int) strrpos($currentPath, '/'));
        $paths = array_map(fn (string $old): string => "{$base}/{$old}", $oldSlugs);

        if ($postType === 'post') {
            foreach ($oldDates as $date) {
                if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $date, $d) && $d[1] !== '0000') {
                    foreach ([$slug, ...$oldSlugs] as $name) {
                        $paths[] = "/{$d[1]}/{$d[2]}/{$d[3]}/{$name}";
                    }
                }
            }
        }

        return array_values(array_unique(array_diff($paths, [$currentPath])));
    }

    /**
     * WordPress serves an attachment page at its parent's URL plus its own
     * name, with `attachment/` in between when the name is numeric.
     */
    public static function attachmentPagePath(string $parentPath, string $attachmentName): string
    {
        $segment = ctype_digit($attachmentName) ? "attachment/{$attachmentName}" : $attachmentName;

        return rtrim($parentPath, '/').'/'.$segment;
    }

    /** @return array{decision: string, target_path: string, notes: string} */
    private function tag(string $slug): array
    {
        if (! in_array($slug, $this->categorySlugs, true)) {
            return $this->result('', '', 'No category with the same slug. '.self::NEEDS_DECISION);
        }

        // The category's own target: `blog` lands on /blog/, the rest on their category page.
        $category = $this->classify('term', 'category', $slug, "/{$slug}");

        return $this->result('redirect', $category['target_path'], 'To the category with the same slug');
    }

    /** @return array{decision: string, target_path: string, notes: string} */
    private function result(string $decision, string $target, string $notes = ''): array
    {
        return ['decision' => $decision, 'target_path' => $target, 'notes' => $notes];
    }
}
