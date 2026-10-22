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
    ['/solutii', 'Soluții'],
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

    $this->get('/creare-site')->assertRedirect('/creare-site-de-prezentare')->assertStatus(301);
});

it('sends the home page slug to the root', function () {
    Page::factory()->ofType(PageType::Home)->create(['slug' => 'acasa']);

    $this->get('/acasa')->assertRedirect('/')->assertStatus(301);
});

it('never lets the catch-all shadow application routes', function () {
    $this->get('/login')->assertOk()->assertSee('data-page=', escape: false);
    $this->get('/admin')->assertRedirect(route('login'));

    // Not slug-shaped: left to the framework (404), never treated as a page.
    $this->get('/Contact')->assertNotFound();
    $this->get('/pagina_veche.php')->assertNotFound();
});

it('lists the articles at /blog: a hero, the latest one featured, then nine cards to a page', function () {
    // Eleven articles: the latest leads, then nine, then one on page 2.
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
    preg_match('#<a href="([^"]+)" class="blog-lead">.*?</a>#s', $html, $lead);

    expect($html)
        ->toContain('<title>Blog | Webis</title>')
        ->toContain('class="page-hero has-hero-planes page-hero--flush page-hero--led"')
        // The planes, inline so they can float.
        ->toContain('<g class="plane plane--green">')
        ->not->toContain('DUMMY DATA')
        ->and(substr_count($html, 'class="blog-lead"'))->toBe(1)
        // The lead goes to the article's own root address.
        ->and($lead[1])->toBe('/cum-poti-avea-un-magazin-online-de-succes/')
        ->and($lead[0])->toContain('Cum poți avea un magazin online de succes')
        ->toContain('Ce contează ca un magazin online să aibă succes.')
        ->and(substr_count($html, 'class="post-card"'))->toBe(9)
        ->and($html)->toContain('aria-current="page">1</span>')
        ->toContain('href="'.route('posts.index').'?pagina=2"');
});

it('pages through the rest of the articles, without the featured one', function () {
    // 45 cards after the featured one: five pages of nine.
    Page::factory()->ofType(PageType::Article)->count(46)
        ->sequence(fn (Sequence $sequence) => ['published_at' => now()->subDays($sequence->index + 1)])
        ->create();

    $html = $this->get('/blog?pagina=5')->assertOk()->getContent();

    expect($html)
        ->toContain('<title>Blog, pagina 5 | Webis</title>')
        ->not->toContain('class="blog-lead"')
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
        // The featured article leads the unfiltered list only.
        ->and($all)->toContain('class="blog-lead"');

    $html = $this->get('/blog/categorie/seo')->assertOk()->getContent();
    preg_match_all('#<span class="post-card__category">([^<]+)</span>#', $html, $categories);

    expect($categories[1])->toBe(['SEO', 'SEO', 'SEO'])
        ->and($html)->not->toContain('class="blog-lead"')
        ->toMatch('#/blog/categorie/seo/\#articole" class="blog-categories__link"\s*aria-current="page"#');
});

it('serves /web: its own title, one H1, the sections in order, the offers to their pages', function () {
    $html = $this->get('/web')->assertOk()->getContent();
    preg_match_all('#<h2 id="web-[a-z]+-title"[^>]*>([^<]+)</h2>#', $html, $titles);

    expect($html)->toContain('<title>Site-uri și magazine online | Webis</title>')
        ->and(substr_count($html, '<h1'))->toBe(1)
        ->and($titles[1])->toBe(['Ce ți se potrivește.', 'Ce primești în orice proiect.', 'Cum lucrăm.', 'Proiecte.', 'După lansare.', 'Întrebări frecvente.'])
        ->and($html)->toContain('href="/creare-site-de-prezentare/" class="post-card web-offer"')
        ->toContain('href="/magazin-online/" class="post-card web-offer web-offer--dark"')
        ->and(substr_count($html, 'class="web-faq__item"'))->toBe(5);
});
