<?php

use App\Enums\PageType;
use App\Models\Page;
use App\Models\PostCategory;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Every link (<a href>) on the page that stays on the site, as a path with
 * its query string, without the fragment: what a click requests.
 *
 * @return list<string>
 */
function internalLinks(string $html): array
{
    preg_match_all('#<a\b[^>]*?\shref="([^"]*)"#i', $html, $matches);

    $root = rtrim(url('/'), '/');

    return collect($matches[1])
        ->map(fn (string $href) => html_entity_decode($href, ENT_QUOTES | ENT_HTML5))
        ->map(fn (string $href) => str_starts_with($href, $root) ? (substr($href, strlen($root)) ?: '/') : $href)
        ->filter(fn (string $href) => str_starts_with($href, '/') && ! str_starts_with($href, '//'))
        ->map(fn (string $href) => strtok($href, '#') ?: '/')
        ->unique()
        ->values()
        ->all();
}

/*
| The trailing slash is canonical, so a link without it takes a 301 on every
| click. This crawls the public site's main pages and asks for every internal
| link exactly as written: none may answer with a 301. It is the check that
| the menu, the footer, the calls to action and the filters are all clean.
*/
it('links only to canonical addresses: no internal link answers with a 301', function () {
    Page::factory()->ofType(PageType::Home)->create(['slug' => 'acasa']);
    Page::factory()->ofType(PageType::Contact)->create(['slug' => 'contact', 'title' => 'Contact']);
    Page::factory()->create(['slug' => 'despre-noi', 'title' => 'Despre noi']);
    $category = PostCategory::factory()->create(['slug' => 'seo', 'name' => 'SEO']);
    Page::factory()->ofType(PageType::Article)->count(11)
        ->sequence(fn ($sequence) => ['slug' => 'articol-'.($sequence->index + 1), 'published_at' => now()->subDays($sequence->index + 1)])
        ->create(['post_category_id' => $category->id]);
    Project::factory()->create(['slug' => 'duo-stone', 'title' => 'Duo Stone']);
    Project::factory()->create(['slug' => 'little-tokyo', 'title' => 'Little Tokyo']);

    // Page → its status: the 404 page has links too.
    $pages = [
        '/' => 200, '/blog/' => 200, '/blog/?pagina=2' => 200, '/blog/categorie/seo/' => 200,
        '/portofoliu/' => 200, '/contact/' => 200, '/despre-noi/' => 200, '/articol-1/' => 200,
        '/proiect/duo-stone/' => 200, '/web/' => 200, '/servicii/' => 200, '/poveste/' => 200,
        '/pagina-inexistenta/' => 404,
    ];

    $root = rtrim(url('/'), '/');
    // link → what is wrong with it, and the pages it is on
    $problems = [];
    // link → its status
    $checked = [];

    foreach ($pages as $page => $status) {
        $html = $this->asWritten()->get($page)->assertStatus($status)->getContent();

        foreach (internalLinks($html) as $link) {
            $checked[$link] ??= $this->asWritten()->get($link)->getStatusCode();

            // A link to a page the test data lacks 404s rather than 301s: its
            // form must still be the one the URL generator gives.
            $canonical = $link === '/' ? '/' : substr(url($link), strlen($root));

            $problem = match (true) {
                $checked[$link] === 301 => 'answers 301',
                $canonical !== $link => 'should be '.$canonical,
                default => null,
            };

            if ($problem !== null) {
                $problems[$link]['problem'] = $problem;
                $problems[$link]['on'][] = $page;
            }
        }
    }

    expect(count($checked))->toBeGreaterThan(30)
        ->and($problems)->toBe([]);
});

/*
| The same crawl with the pages the site links to in place, so a link to a
| page that doesn't exist (a 404) fails too, not only a 301; and a home page
| whose hero an editor gave slash-less links (/contact, /clienti), which
| render in their canonical form (TrailingSlash::href) all the same.
*/
it('links only to pages that answer 200: no 301, no 404', function () {
    Page::factory()->ofType(PageType::Home)->create(['slug' => 'acasa', 'blocks' => [
        ['id' => 'hero', 'type' => 'hero', 'v' => 1, 'data' => [
            'layout' => 'centered', 'heading' => 'Webis',
            'primary_cta' => ['label' => 'Hai să discutăm', 'url' => '/contact'],
            'secondary_cta' => ['label' => 'Proiecte', 'url' => '/clienti'],
        ]],
    ]]);
    Page::factory()->ofType(PageType::Contact)->create(['slug' => 'contact', 'title' => 'Contact']);
    Page::factory()->ofType(PageType::Legal)->create(['slug' => 'politica-de-confidentialitate']);
    foreach (['creare-site-de-prezentare', 'creare-magazin-online', 'mentenanta-site-wordpress', 'seo-search-engine-optimization'] as $slug) {
        Page::factory()->create(['slug' => $slug]);
    }
    Page::factory()->ofType(PageType::Industry)->create(['slug' => 'creare-site-stomatologie', 'title' => 'Creare site stomatologie']);
    $category = PostCategory::factory()->create(['slug' => 'seo', 'name' => 'SEO']);
    Page::factory()->ofType(PageType::Article)->count(3)
        ->sequence(fn ($sequence) => ['slug' => 'articol-'.($sequence->index + 1), 'published_at' => now()->subDays($sequence->index + 1)])
        ->create(['post_category_id' => $category->id]);
    Project::factory()->create(['slug' => 'duo-stone', 'title' => 'Duo Stone']);

    $pages = ['/', '/servicii/', '/servicii/creare-site-stomatologie/', '/web/', '/produse/', '/software-la-comanda/',
        '/portofoliu/', '/blog/', '/contact/', '/creare-magazin-online/', '/articol-1/', '/proiect/duo-stone/'];

    $problems = [];
    $checked = [];

    foreach ($pages as $page) {
        $html = $this->asWritten()->get($page)->assertOk()->getContent();

        foreach (internalLinks($html) as $link) {
            $checked[$link] ??= $this->asWritten()->get($link)->getStatusCode();

            if ($checked[$link] !== 200) {
                $problems[$link]['status'] = $checked[$link];
                $problems[$link]['on'][] = $page;
            }
        }
    }

    expect(count($checked))->toBeGreaterThan(20)
        ->and($checked)->toHaveKeys(['/contact/', '/portofoliu/', '/servicii/creare-site-stomatologie/'])
        ->and($problems)->toBe([]);
});
