<?php

use App\Enums\PageType;
use App\Enums\ProjectType;
use App\Models\Page;
use App\Models\Project;
use App\Support\Seo\Sitemap;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
| /creare-magazin-online/: the online shops page, in code (public/shop),
| at the old site's address.
*/

it('is the page for online shops, at its old address', function () {
    // The CMS page of that name no longer shows there.
    Page::factory()->ofType(PageType::Page)->create(['slug' => 'creare-magazin-online', 'title' => 'Pagina veche']);

    $response = $this->get('/creare-magazin-online/')->assertOk();

    $response->assertSee('<title>Creare magazin online: design, plăți și livrare | Webis</title>', false)
        ->assertSee('Realizare magazin online pe brandul tău', false)
        ->assertSee('Cât costă un magazin online.')
        ->assertSee('Vrei să vinzi online?')
        ->assertDontSee('Pagina veche');

    expect($response->getContent())->toMatch('~<h1[^>]*>\s*<span class="block">Creare magazin</span>\s*<span class="block">online\.</span>~');
});

it('sits under Web și e-commerce in its breadcrumbs, without FAQ markup', function () {
    $html = $this->get('/creare-magazin-online/')->getContent();

    preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $json);
    $graph = collect(json_decode($json[1], true)['@graph']);
    $crumbs = $graph->firstWhere('@type', 'BreadcrumbList')['itemListElement'];

    expect(array_column($crumbs, 'name'))->toBe(['Acasă', 'Web și e-commerce', 'Creare magazin online'])
        ->and($graph->pluck('@type')->flatten())->not->toContain('FAQPage');
});

it('keeps to the copy rules', function () {
    $text = mb_strtolower(strip_tags($this->get('/creare-magazin-online/')->getContent()));

    foreach (['trebuie', 'organizați', 'de-a gata', 'wordpress', 'woocommerce', 'cafele', 'visul tău'] as $word) {
        expect($text)->not->toContain($word);
    }
});

it('shows online shops among its projects, and is in the sitemap', function () {
    Project::factory()->create(['slug' => 'zyanya', 'title' => 'Zyanya', 'type' => ProjectType::Shop]);
    Project::factory()->create(['slug' => 'un-software', 'title' => 'Un software', 'type' => ProjectType::Software]);

    $this->get('/creare-magazin-online/')
        ->assertSee('href="/proiect/zyanya/"', false)
        ->assertDontSee('/proiect/un-software/', false);

    Sitemap::forget();
    $this->get('/sitemap.xml')->assertSee('/creare-magazin-online/');
});

it('is linked from every online shop project, and from /web', function () {
    $shop = Project::factory()->create(['type' => ProjectType::Shop]);
    $site = Project::factory()->create(['type' => ProjectType::Presentation]);

    $this->get("/proiect/{$shop->slug}/")->assertSee('href="/creare-magazin-online/"', false);
    $this->get("/proiect/{$site->slug}/")->assertDontSee('Vezi cum realizăm un magazin online');
    $this->get('/web/')->assertSee('Creare magazin online');
});
