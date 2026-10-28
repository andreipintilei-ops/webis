<?php

use App\Enums\PageType;
use App\Models\Page;
use App\Models\PostCategory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('serves the section pages', function (string $path, string $title) {
    $this->get($path)
        ->assertOk()
        ->assertSee("<title>{$title} | Webis</title>", escape: false)
        ->assertSee('data-theo-menu', escape: false);
})->with([
    ['/realizare-site-uri-tematice', 'Realizare site-uri tematice pe domenii'],
    ['/portofoliu', 'Portofoliu'],
    ['/blog', 'Blog'],
]);

it('serves published CMS pages at the site root', function () {
    Page::factory()->ofType(PageType::Contact)->create(['title' => 'Contact', 'slug' => 'contact']);

    $this->get('/contact')->assertOk()->assertSee('<title>Contact | Webis</title>', escape: false);
});

it('hides drafts, scheduled and trashed pages', function () {
    Page::factory()->draft()->create(['slug' => 'ciorna']);
    Page::factory()->scheduled()->create(['slug' => 'programata']);
    Page::factory()->create(['slug' => 'stearsa'])->delete();

    foreach (['/ciorna', '/programata', '/stearsa', '/nu-exista'] as $path) {
        $this->get($path)->assertNotFound();
    }
});

it('redirects a renamed page from its old address', function () {
    $page = Page::factory()->create(['slug' => 'creare-site']);
    $page->update(['slug' => 'creare-site-de-prezentare']);

    // Straight to the canonical address, slash and all: one hop, no chain.
    $this->get('/creare-site')->assertRedirect('/creare-site-de-prezentare/')->assertStatus(301);
});

it('sends the home page slug to the root', function () {
    Page::factory()->ofType(PageType::Home)->create(['slug' => 'acasa']);

    $this->get('/acasa')->assertRedirect('/')->assertStatus(301);
});

it('serves the industry pages under /servicii, as the old site did, and lists them at /realizare-site-uri-tematice', function () {
    Page::factory()->ofType(PageType::Industry)->create(['title' => 'Creare site stomatologie', 'slug' => 'creare-site-stomatologie']);
    Page::factory()->ofType(PageType::Industry)->draft()->create(['title' => 'Creare site ciorna', 'slug' => 'creare-site-ciorna']);

    $this->get('/servicii/creare-site-stomatologie')
        ->assertOk()
        ->assertSee('<title>Creare site stomatologie | Webis</title>', escape: false);

    $this->get('/realizare-site-uri-tematice')
        ->assertOk()
        ->assertSee('href="/servicii/creare-site-stomatologie/"', escape: false)
        ->assertSee('Stomatologie')
        ->assertDontSee('creare-site-ciorna');

    $this->get('/servicii/creare-site-ciorna')->assertNotFound();
    $this->get('/servicii/nu-exista')->assertNotFound();
});

it('names the industry index Site-uri tematice: its H1, its own canonical, the industry pages\' breadcrumbs, the footer and /web', function () {
    Page::factory()->ofType(PageType::Industry)->create(['title' => 'Creare site stomatologie', 'slug' => 'creare-site-stomatologie', 'blocks' => [
        ['id' => 'hero', 'type' => 'hero', 'v' => 1, 'data' => ['layout' => 'split', 'heading' => 'Creare site stomatologie', 'background' => 'gradient-violet']],
    ]]);

    $index = $this->get('/realizare-site-uri-tematice/')->assertOk()->getContent();
    $industry = $this->get('/servicii/creare-site-stomatologie/')->assertOk()->getContent();
    preg_match('#<nav aria-label="Breadcrumb".*?</nav>#s', $industry, $crumbs);
    preg_match('#<footer class="site-footer".*?</footer>#s', $index, $footer);
    preg_match('#<h3 class="site-footer__group-label">Web și e-commerce</h3>.*?</ul>#s', $footer[0], $web);
    preg_match('#<h3 class="site-footer__group-label[^"]*">Webis</h3>.*?</ul>#s', $footer[0], $webis);

    expect($index)
        ->toContain('<title>Realizare site-uri tematice pe domenii | Webis</title>')
        ->toContain('<meta name="description" content="Site-uri de prezentare și magazine online gândite pentru domeniul tău: clinici, restaurante, construcții, primării și multe altele.">')
        ->toContain('<link rel="canonical" href="https://www.webis.ro/realizare-site-uri-tematice/">')
        ->and(trim(preg_replace('/\s+/', ' ', strip_tags(preg_replace('#.*?(<h1.*?</h1>).*#s', '$1', $index)))))->toBe('Site-uri tematice.')
        // Shown above the hero's title, the same trail as the JSON-LD's.
        ->and($crumbs[0])
        ->toContain('<a href="/"')
        ->toContain('<a href="/realizare-site-uri-tematice/"')
        ->toContain('<span aria-current="page">Creare site stomatologie</span>')
        ->and(trim(preg_replace('/\s+/', ' ', strip_tags($crumbs[0]))))->toBe('Acasă › Site-uri tematice › Creare site stomatologie')
        // In "Web și e-commerce", not under Webis.
        ->and($web[0])->toContain('href="/realizare-site-uri-tematice/"')
        ->and($webis[0])->not->toContain('realizare-site-uri-tematice')
        ->and($this->get('/web/')->getContent())->toContain('Vezi site-uri tematice pe domenii');
});

it('gives each page one address: an industry page at the root, or another page under /servicii, goes to its own', function () {
    Page::factory()->ofType(PageType::Industry)->create(['slug' => 'creare-site-stomatologie']);
    Page::factory()->ofType(PageType::Contact)->create(['slug' => 'contact']);

    $this->get('/creare-site-stomatologie')->assertRedirect('/servicii/creare-site-stomatologie/')->assertStatus(301);
    $this->get('/servicii/contact')->assertRedirect('/contact/')->assertStatus(301);
    // The index's old addresses, each straight to it: one hop.
    $this->get('/servicii')->assertRedirect('/realizare-site-uri-tematice/')->assertStatus(301);
    $this->get('/servicii/')->assertRedirect('/realizare-site-uri-tematice/')->assertStatus(301);
    $this->get('/solutii')->assertRedirect('/realizare-site-uri-tematice/')->assertStatus(301);
});

it('sends a renamed industry page to its address under /servicii', function () {
    $page = Page::factory()->ofType(PageType::Industry)->create(['slug' => 'creare-site-dentist']);
    $page->update(['slug' => 'creare-site-stomatologie']);

    $this->get('/servicii/creare-site-dentist')->assertRedirect('/servicii/creare-site-stomatologie/')->assertStatus(301);
    $this->get('/creare-site-dentist')->assertRedirect('/servicii/creare-site-stomatologie/')->assertStatus(301);
});

it('never lets the catch-all shadow application routes', function () {
    $this->get('/login')->assertOk()->assertSee('data-page=', escape: false);
    $this->get('/admin')->assertRedirect(route('login'));

    // Not slug-shaped: left to the framework (404), never treated as a page.
    $this->get('/Contact')->assertNotFound();
    $this->get('/pagina_veche.php')->assertNotFound();
});

it('lists the articles at /blog: a hero, then nine cards to a page, the footer\'s W', function () {
    // Eleven articles: nine, then two on page 2.
    Page::factory()->ofType(PageType::Article)->count(10)
        ->sequence(fn (Sequence $sequence) => ['published_at' => now()->subDays($sequence->index + 2)])
        ->create();
    Page::factory()->ofType(PageType::Article)->create([
        'title' => 'Cum poți avea un magazin online de succes',
        'slug' => 'cum-poti-avea-un-magazin-online-de-succes',
        'excerpt' => 'Ce contează ca un magazin online să aibă succes.',
        'published_at' => now()->subDay(),
    ]);

    $html = $this->get('/blog')->assertOk()->getContent();
    preg_match('#<a href="([^"]+)" class="post-card">#', $html, $first);

    expect($html)
        ->toContain('<title>Blog | Webis</title>')
        // The section after it rises over its foot, as on the home page;
        // no article across it, so no taller hero.
        ->toContain('class="page-hero has-hero-planes page-hero--sheet"')
        // The planes, inline so they can float.
        ->toContain('<g class="plane plane--green">')
        ->not->toContain('DUMMY DATA')
        ->not->toContain('class="blog-lead"')
        // The latest is the first card, at the article's own root address.
        ->and($first[1])->toBe('/cum-poti-avea-un-magazin-online-de-succes/')
        ->and(substr_count($html, 'class="post-card"'))->toBe(9)
        ->and($html)->toContain('aria-current="page">1</span>')
        ->toContain('href="'.route('posts.index').'?pagina=2"')
        ->toContain('class="site-footer__w" data-finale-w');
});

it('pages through the articles', function () {
    // Five pages of nine.
    Page::factory()->ofType(PageType::Article)->count(45)
        ->sequence(fn (Sequence $sequence) => ['published_at' => now()->subDays($sequence->index + 1)])
        ->create();

    $html = $this->get('/blog?pagina=5')->assertOk()->getContent();

    expect($html)
        ->toContain('<title>Blog, pagina 5 | Webis</title>')
        ->and(substr_count($html, 'class="post-card"'))->toBe(9)
        ->and($html)->toContain('rel="prev"')
        ->not->toContain('rel="next"');

    $this->get('/blog?pagina=6')->assertNotFound();
});

it('answers an unknown address with the site\'s own not-found page', function () {
    $this->get('/pagina-care-nu-exista')->assertNotFound()
        ->assertSee('Pagina nu mai e aici.')
        ->assertSee('Poate a fost mutată sau adresa are o greșeală.')
        // The button's label, rolling letter by letter.
        ->assertSeeText('Înapoi la prima pagină')
        ->assertSee('Vezi articolele');
});

it('gives the blog its own meta description', function () {
    $this->get('/blog')->assertOk()->assertSee(
        '<meta name="description" content="Articole despre software la comandă, produse SaaS, site-uri și magazine online, scrise de echipa Webis din Iași.">',
        escape: false,
    );
});

it('has no /despre: the page was dropped, and an old CMS page of that name does not take its place', function () {
    Page::factory()->create(['title' => 'Despre', 'slug' => 'despre']);

    $this->get('/despre')->assertNotFound();
});

it('lists the blog\'s categories beside the articles, each filtering them at its own address', function () {
    $seo = PostCategory::factory()->create(['name' => 'SEO', 'slug' => 'seo']);
    $design = PostCategory::factory()->create(['name' => 'Design', 'slug' => 'design']);
    Page::factory()->ofType(PageType::Article)->count(3)->create(['post_category_id' => $seo->id]);
    Page::factory()->ofType(PageType::Article)->count(2)->create(['post_category_id' => $design->id]);

    $all = $this->get('/blog')->assertOk()->getContent();
    preg_match('#<aside class="blog-categories".*?</aside>#s', $all, $aside);
    preg_match_all('#<span>([^<]+)</span><span class="blog-categories__count">(\d+)</span>#', $aside[0], $counts);

    expect($aside[0])
        ->toMatch('#href="[^"]*/blog/\#articole" class="blog-categories__link"\s*aria-current="page"#')
        ->toContain('/blog/categorie/seo/#articole')
        ->not->toContain('?categorie=')
        // Every article, then the categories, most first.
        ->and(array_combine($counts[1], $counts[2]))->toBe(['Toate articolele' => '5', 'SEO' => '3', 'Design' => '2'])
        ->and(substr_count($all, 'class="post-card"'))->toBe(5);

    $html = $this->get('/blog/categorie/seo')->assertOk()->getContent();
    preg_match_all('#<span class="post-card__category">([^<]+)</span>#', $html, $categories);

    expect($categories[1])->toBe(['SEO', 'SEO', 'SEO'])
        ->and($html)->toMatch('#/blog/categorie/seo/\#articole" class="blog-categories__link"\s*aria-current="page"#');
});

it('serves /web: its own title, one H1, the sections in order, the offers to their pages', function () {
    $html = $this->get('/web')->assertOk()->getContent();
    preg_match_all('#<h2 id="web-[a-z]+-title"[^>]*>([^<]+)#', $html, $titles);

    expect($html)->toContain('<title>Site-uri, magazine online și identitate vizuală | Webis</title>')
        ->toContain('<meta name="description" content="Site-uri de prezentare și magazine online pe brandul tău, cu SEO de la început. Dacă nu ai încă un brand, creăm și identitatea vizuală.">')
        ->and(substr_count($html, '<h1'))->toBe(1)
        // No projects here: no projects section between the panel and the
        // visual identity, then the steps.
        ->and(array_map('trim', $titles[1]))->toBe(['Un site care', 'Logo, culori și fonturi,', 'Cum lucrăm', 'După lansare.', 'Întrebări frecvente.'])
        // What the visual identity includes, four cells in one panel.
        ->and($html)->toContain('class="fpanel fpanel--four"')
        ->and(substr_count($html, 'class="fpanel__cell"'))->toBe(4)
        // The offers across the hero's foot, each to its page; the benefits as cards.
        ->and($html)->toContain('href="/creare-site-de-prezentare/" class="web-hero-offer"')
        ->toContain('href="/creare-magazin-online/" class="web-hero-offer"')
        ->and(substr_count($html, 'class="bcard"'))->toBe(6)
        ->and(substr_count($html, 'class="ncols__item"'))->toBe(5)
        ->and(substr_count($html, 'class="web-faq__item"'))->toBe(6);
});
