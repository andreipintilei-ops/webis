<?php

use App\Enums\PageType;
use App\Enums\ProjectType;
use App\Models\Page;
use App\Models\PostCategory;
use App\Models\Project;
use App\Models\Redirect;
use App\Support\Seo\Sitemap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

/*
| The canonical tag and the sitemap: both name each page by its canonical
| URL — the canonical site (config site.canonical_url), trailing slash, no
| query string — so they never disagree. The one exception: the later pages
| of a blog listing and of the whole portfolio name themselves with their
| ?pagina; the sitemap lists only each listing's first page.
*/

function canonicalOf(string $html): ?string
{
    return preg_match('#<link rel="canonical" href="([^"]*)">#', $html, $match) === 1 ? $match[1] : null;
}

it('names every page by its canonical URL', function (string $path, string $canonical) {
    Page::factory()->ofType(PageType::Home)->create(['slug' => 'acasa']);
    Page::factory()->create(['slug' => 'creare-magazin-online']);
    Page::factory()->ofType(PageType::Industry)->create(['slug' => 'creare-site-stomatologie']);
    Project::factory()->create(['slug' => 'duo-stone']);

    $html = $this->asWritten()->get($path)->assertOk()->getContent();

    expect(canonicalOf($html))->toBe($canonical);
})->with([
    'home' => ['/', 'https://www.webis.ro/'],
    'a CMS page' => ['/creare-magazin-online/', 'https://www.webis.ro/creare-magazin-online/'],
    'an industry page' => ['/servicii/creare-site-stomatologie/', 'https://www.webis.ro/servicii/creare-site-stomatologie/'],
    'a project' => ['/proiect/duo-stone/', 'https://www.webis.ro/proiect/duo-stone/'],
    'a section' => ['/servicii/', 'https://www.webis.ro/servicii/'],
    'a filter, without it' => ['/portofoliu/?tip=magazin', 'https://www.webis.ro/portofoliu/'],
    'a query string, without it' => ['/web/?utm_source=x', 'https://www.webis.ro/web/'],
]);

it('names each page of a blog listing by itself', function (string $path, string $canonical) {
    $seo = PostCategory::factory()->create(['slug' => 'seo']);
    // Eleven: the lead and nine cards on /blog's first page, one on its second;
    // nine to a page in the category, so two there too.
    Page::factory()->ofType(PageType::Article)->count(11)
        ->sequence(fn ($sequence) => ['slug' => 'articol-'.($sequence->index + 1)])
        ->create(['post_category_id' => $seo->id]);

    $html = $this->asWritten()->get($path)->assertOk()->getContent();

    expect(canonicalOf($html))->toBe($canonical);
})->with([
    'the first page' => ['/blog/', 'https://www.webis.ro/blog/'],
    'the first page, asked for by number' => ['/blog/?pagina=1', 'https://www.webis.ro/blog/'],
    'a later page' => ['/blog/?pagina=2', 'https://www.webis.ro/blog/?pagina=2'],
    'a category\'s first page' => ['/blog/categorie/seo/', 'https://www.webis.ro/blog/categorie/seo/'],
    'a category\'s later page' => ['/blog/categorie/seo/?pagina=2', 'https://www.webis.ro/blog/categorie/seo/?pagina=2'],
    'a later page, other parameters dropped' => ['/blog/?pagina=2&utm_source=x', 'https://www.webis.ro/blog/?pagina=2'],
]);

it('names each page of the portfolio by itself, a type\'s list by the portfolio', function (string $path, string $canonical) {
    // Thirteen shops: twelve to a page, so two pages, filtered or not.
    Project::factory()->count(13)
        ->sequence(fn ($sequence) => ['slug' => 'proiect-'.($sequence->index + 1)])
        ->create(['type' => ProjectType::Shop]);

    $html = $this->asWritten()->get($path)->assertOk()->getContent();

    expect(canonicalOf($html))->toBe($canonical);
})->with([
    'the first page' => ['/portofoliu/', 'https://www.webis.ro/portofoliu/'],
    'the first page, asked for by number' => ['/portofoliu/?pagina=1', 'https://www.webis.ro/portofoliu/'],
    'a later page' => ['/portofoliu/?pagina=2', 'https://www.webis.ro/portofoliu/?pagina=2'],
    'a type' => ['/portofoliu/?tip=magazin', 'https://www.webis.ro/portofoliu/'],
    'a type, a later page' => ['/portofoliu/?tip=magazin&pagina=2', 'https://www.webis.ro/portofoliu/'],
]);

it('takes a page\'s own canonical from its SEO data', function () {
    Page::factory()->create(['slug' => 'copie', 'seo' => ['canonical' => 'https://www.webis.ro/original/']]);

    expect(canonicalOf($this->asWritten()->get('/copie/')->getContent()))->toBe('https://www.webis.ro/original/');
});

it('names no canonical on an error page', function () {
    expect(canonicalOf($this->get('/nu-exista/')->assertNotFound()->getContent()))->toBeNull();
});

it('keeps a page set to noindex out of search engines in production', function () {
    Page::factory()->create(['slug' => 'ascunsa', 'seo' => ['noindex' => true]]);
    Page::factory()->create(['slug' => 'vizibila']);
    app()->detectEnvironment(fn () => 'production');

    // Production is indexable on the canonical host only (Support\Seo\Indexing).
    expect($this->asWritten()->get('https://www.webis.ro/ascunsa/')->getContent())->toContain('<meta name="robots" content="noindex, follow">')
        ->and($this->asWritten()->get('https://www.webis.ro/vizibila/')->getContent())->not->toContain('name="robots"');
});

describe('sitemap.xml', function () {
    beforeEach(function () {
        Page::factory()->ofType(PageType::Home)->create(['slug' => 'acasa']);
        Page::factory()->create(['slug' => 'creare-magazin-online']);
        Page::factory()->ofType(PageType::Industry)->create(['slug' => 'creare-site-stomatologie']);
        Page::factory()->ofType(PageType::Legal)->create(['slug' => 'politica-de-confidentialitate']);
        Page::factory()->draft()->create(['slug' => 'ciorna']);
        Page::factory()->create(['slug' => 'ascunsa', 'seo' => ['noindex' => true]]);
        // A CMS page at a reserved slug: the app's route answers there.
        Page::factory()->create(['slug' => 'produse']);
        // A page the redirects table sends elsewhere, once a row says so.
        Page::factory()->create(['slug' => 'mutata']);

        $seo = PostCategory::factory()->create(['slug' => 'seo']);
        $thin = PostCategory::factory()->create(['slug' => 'gazduire']);
        Page::factory()->ofType(PageType::Article)->count(3)
            ->sequence(fn ($sequence) => ['slug' => 'articol-'.($sequence->index + 1)])
            ->create(['post_category_id' => $seo->id]);
        Page::factory()->ofType(PageType::Article)->create(['slug' => 'singur', 'post_category_id' => $thin->id]);

        Project::factory()->create(['slug' => 'duo-stone']);
        Project::factory()->draft()->create(['slug' => 'in-lucru']);
    });

    it('lists every indexable page at its canonical URL', function () {
        $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = simplexml_load_string($response->getContent());

        expect($xml)->not->toBeFalse();

        $locs = array_map(fn ($url) => (string) $url->loc, iterator_to_array($xml->url, false));

        expect($locs)->toContain(
            'https://www.webis.ro/',
            'https://www.webis.ro/web/',
            'https://www.webis.ro/produse/',
            'https://www.webis.ro/blog/',
            'https://www.webis.ro/creare-magazin-online/',
            'https://www.webis.ro/servicii/creare-site-stomatologie/',
            'https://www.webis.ro/politica-de-confidentialitate/',
            'https://www.webis.ro/articol-1/',
            'https://www.webis.ro/singur/',
            'https://www.webis.ro/proiect/duo-stone/',
            'https://www.webis.ro/blog/categorie/seo/',
        )->not->toContain(
            'https://www.webis.ro/ciorna/',
            'https://www.webis.ro/ascunsa/',
            'https://www.webis.ro/proiect/in-lucru/',
            // Thin: one article.
            'https://www.webis.ro/blog/categorie/gazduire/',
            // The design trials.
            'https://www.webis.ro/poveste/',
        )->and($locs)->toBe(array_values(array_unique($locs)));

        foreach ($locs as $loc) {
            expect($loc)->toStartWith('https://www.webis.ro/')->toEndWith('/');
        }
    });

    it('leaves out an address the redirects table would send elsewhere', function () {
        Redirect::factory()->create(['source_path' => '/mutata', 'target' => '/creare-magazin-online/']);

        expect($this->get('/sitemap.xml')->getContent())
            ->toContain('<loc>https://www.webis.ro/creare-magazin-online/</loc>')
            ->not->toContain('<loc>https://www.webis.ro/mutata/</loc>');
    });

    it('is cached, and rebuilt when the content changes', function () {
        $this->get('/sitemap.xml');

        expect(Cache::has(Sitemap::CACHE_KEY))->toBeTrue();

        Page::factory()->create(['slug' => 'noua']);

        expect(Cache::has(Sitemap::CACHE_KEY))->toBeFalse()
            ->and($this->get('/sitemap.xml')->getContent())->toContain('<loc>https://www.webis.ro/noua/</loc>');
    });

    it('is named in robots.txt', function () {
        expect(file_get_contents(public_path('robots.txt')))->toContain('Sitemap: https://www.webis.ro/sitemap.xml');
    });
});
