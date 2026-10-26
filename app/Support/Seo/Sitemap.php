<?php

namespace App\Support\Seo;

use App\Actions\Seo\ResolveRedirect;
use App\Enums\PageType;
use App\Models\Page;
use App\Models\PostCategory;
use App\Models\Project;
use App\Models\Redirect;
use App\Support\PublicUrls;
use App\Support\ReservedSlugs;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

/**
 * /sitemap.xml (Public\SitemapController): every indexable public page, each
 * at its canonical URL (PublicUrls::canonical — the canonical site, trailing
 * slash), so the sitemap and the canonical tags never disagree.
 *
 * In it: the section pages, the published CMS pages (articles and industry
 * pages among them), the published projects, and the blog categories with at
 * least MIN_CATEGORY_ARTICLES articles. Left out: anything set to noindex,
 * the design trials, a CMS page at a reserved slug (an app route answers
 * there, not it), and any address the redirects table would send elsewhere.
 *
 * Cached (CACHE_KEY); a page, project or category saved or deleted, or
 * ContentPublished, forgets it (AppServiceProvider, ForgetSitemap).
 */
final class Sitemap
{
    public const string CACHE_KEY = 'sitemap.xml';

    /** A category with fewer articles is a thin page: listed, never offered. */
    public const int MIN_CATEGORY_ARTICLES = 3;

    /** How long the cached sitemap lasts if nothing forgets it sooner. */
    private const int TTL_SECONDS = 3600;

    /** The section pages that are app routes rather than CMS pages. */
    private const array SECTION_ROUTES = [
        'home', 'services.index', 'services.web', 'services.software', 'services.products',
        'projects.index', 'posts.index',
    ];

    public function __construct(private readonly ResolveRedirect $resolveRedirect) {}

    /** The sitemap's XML, from the cache. */
    public function cached(): string
    {
        return Cache::remember(self::CACHE_KEY, self::TTL_SECONDS, fn (): string => $this->xml());
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function xml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($this->entries() as $entry) {
            $xml .= '  <url><loc>'.htmlspecialchars($entry['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc>'
                .($entry['lastmod'] !== null ? '<lastmod>'.$entry['lastmod']->toAtomString().'</lastmod>' : '')
                .'</url>'."\n";
        }

        return $xml.'</urlset>'."\n";
    }

    /**
     * Each URL once, in order: sections, pages, projects, categories.
     *
     * @return list<array{loc: string, lastmod: CarbonInterface|null}>
     */
    public function entries(): array
    {
        /** @var array<string, array{path: string, lastmod: CarbonInterface|null}> $entries */
        $entries = [];
        $add = function (string $path, ?CarbonInterface $lastmod) use (&$entries): void {
            $entries[PublicUrls::canonical($path)] ??= ['path' => $path, 'lastmod' => $lastmod];
        };

        foreach (self::SECTION_ROUTES as $name) {
            $add(route($name, absolute: false), null);
        }

        Page::query()->published()
            ->where('type', '!=', PageType::Home->value)
            ->orderBy('type')->orderBy('slug')
            ->get(['id', 'type', 'slug', 'seo', 'updated_at'])
            ->reject(fn (Page $page): bool => $page->seo->noindex || ReservedSlugs::contains($page->slug))
            ->each(fn (Page $page) => $add(PublicUrls::for($page), $page->updated_at));

        Project::query()->published()
            ->orderBy('slug')
            ->get(['id', 'slug', 'seo', 'updated_at'])
            ->reject(fn (Project $project): bool => $project->seo->noindex)
            ->each(fn (Project $project) => $add(PublicUrls::for($project), $project->updated_at));

        PostCategory::query()
            ->withCount(['pages' => fn ($query) => $query->published()])
            ->orderBy('slug')
            ->get()
            ->reject(fn (PostCategory $category): bool => $category->seo->noindex || $category->pages_count < self::MIN_CATEGORY_ARTICLES)
            ->each(fn (PostCategory $category) => $add(PublicUrls::for($category), $category->updated_at));

        return collect($entries)
            ->reject(fn (array $entry): bool => $entry['path'] !== '/'
                && ($this->resolveRedirect)(Redirect::normalisePath($entry['path'])) !== null)
            ->map(fn (array $entry, string $loc): array => ['loc' => $loc, 'lastmod' => $entry['lastmod']])
            ->values()
            ->all();
    }
}
