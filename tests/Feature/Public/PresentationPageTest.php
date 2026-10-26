<?php

use App\Enums\PageType;
use App\Enums\ProjectType;
use App\Models\Page;
use App\Models\Project;
use App\Support\Seo\Sitemap;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
| /creare-site-de-prezentare/: the presentation sites page, in code
| (public/presentation), at the old site's address.
*/

it('is the page for presentation sites, at its old address', function () {
    // The CMS page of that name no longer shows there.
    Page::factory()->ofType(PageType::Page)->create(['slug' => 'creare-site-de-prezentare', 'title' => 'Pagina veche']);

    $response = $this->get('/creare-site-de-prezentare/')->assertOk();

    $response->assertSee('<title>Creare site de prezentare pentru firma ta | Webis</title>', false)
        ->assertSee('Realizare site de prezentare pe brandul tău', false)
        ->assertSee('Cât costă un site de prezentare.')
        ->assertSee('Vrei un site nou pentru firma ta?')
        ->assertSee('href="/creare-magazin-online/"', false)
        ->assertDontSee('Pagina veche');

    expect($response->getContent())->toMatch('~<h1[^>]*>\s*<span class="block">Creare site de</span>\s*<span class="block">prezentare\.</span>~');
});

it('sits under Web și e-commerce in its breadcrumbs, without FAQ markup', function () {
    $html = $this->get('/creare-site-de-prezentare/')->getContent();

    preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $json);
    $graph = collect(json_decode($json[1], true)['@graph']);
    $crumbs = $graph->firstWhere('@type', 'BreadcrumbList')['itemListElement'];

    expect(array_column($crumbs, 'name'))->toBe(['Acasă', 'Web și e-commerce', 'Creare site de prezentare'])
        ->and($graph->pluck('@type')->flatten())->not->toContain('FAQPage');
});

it('keeps to the copy rules, and leaves "creare site Iași" to the home page', function () {
    $text = mb_strtolower(strip_tags($this->get('/creare-site-de-prezentare/')->getContent()));

    foreach (['trebuie', 'organizați', 'de-a gata', 'wordpress', 'avansul înapoi', 'creare site iași', 'se încarcă repede.'] as $phrase) {
        expect($text)->not->toContain($phrase);
    }
});

it('shows presentation sites among its projects, and is in the sitemap', function () {
    Project::factory()->create(['slug' => 'dentalux-prime', 'title' => 'Dentalux Prime', 'type' => ProjectType::Presentation]);
    Project::factory()->create(['slug' => 'un-magazin', 'title' => 'Un magazin', 'type' => ProjectType::Shop]);

    $this->get('/creare-site-de-prezentare/')
        ->assertSee('href="/proiect/dentalux-prime/"', false)
        ->assertDontSee('/proiect/un-magazin/', false);

    Sitemap::forget();
    $this->get('/sitemap.xml')->assertSee('/creare-site-de-prezentare/');
});

it('is linked from every presentation site project, and from /web', function () {
    $site = Project::factory()->create(['type' => ProjectType::Presentation]);
    $software = Project::factory()->create(['type' => ProjectType::Software]);

    $this->get("/proiect/{$site->slug}/")->assertSee('href="/creare-site-de-prezentare/"', false);
    $this->get("/proiect/{$software->slug}/")->assertDontSee('Vezi cum realizăm un site de prezentare');
    $this->get('/web/')->assertSee('Creare site de prezentare');
});
