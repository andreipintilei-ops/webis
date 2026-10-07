<?php

use App\Enums\ContentStatus;
use App\Enums\PageType;
use App\Models\Page;
use App\Models\Post;
use App\Settings\CompanySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('serves the published home page as plain HTML', function () {
    Page::factory()->ofType(PageType::Home)->create(['title' => 'Acasă', 'slug' => 'acasa']);

    $this->get('/')
        ->assertOk()
        ->assertSee('<title>Acasă | Webis</title>', escape: false)
        ->assertDontSee('data-page=', escape: false);
});

it('keeps non-production sites out of search engines', function () {
    $this->get('/')->assertSee('<meta name="robots" content="noindex, nofollow">', escape: false);
});

it('ignores a home page that is still a draft', function () {
    Page::factory()->ofType(PageType::Home)->draft()->create(['title' => 'Ciornă', 'slug' => 'acasa']);

    $this->get('/')->assertOk()->assertDontSee('Ciornă');
});

it('opens the Theodore menu from the round header button', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('class="site-burger__button" data-menu-toggle="theodore"')
        ->toContain('id="meniu" class="theo-menu"')
        // The round button is the toggle, so the menu has no ✕ of its own.
        ->not->toContain('data-theo-close')
        ->not->toContain('id="site-panel"');
});

it('lays out the full-screen menu: sections, link groups, contact and its button, legal links', function () {
    $html = $this->get('/')->assertOk()->getContent();
    preg_match('/<div id="meniu" class="theo-menu".*?<\/svg>/s', $html, $menu);
    preg_match('/<nav class="theo-menu__nav".*?<\/nav>/s', $menu[0], $nav);
    preg_match_all('/<span class="theo-menu__label">([^<]+)<\/span>/', $nav[0], $labels);

    expect($labels[1])->toBe(['Soluții', 'Clienți', 'Produse', 'Despre'])
        ->and($nav[0])->toContain('<span class="theo-menu__index" aria-hidden="true">01</span>')
        // The call to action is the side column's main button, leaving through the curtain.
        ->and($menu[0])->toMatch('/<a href="\/contact" class="pill btn-dark[^"]*" data-theo-link(="data-theo-link")?>.*?Hai să discutăm/s')
        ->toContain('<span class="theo-menu__tiny">ce facem</span>')
        // The side column's groups, and the legal links at the foot.
        ->toContain('<p class="theo-menu__heading">Pentru cine</p>')
        ->toMatch('/<a href="\/magazin-online" class="theo-menu__detail" data-theo-link>Magazin online<\/a>/')
        ->toMatch('/<a href="\/accesibilitate" class="theo-menu__detail" data-theo-link>Accesibilitate<\/a>/')
        // Contact: only what is filled in (here, the town).
        ->toContain('<li class="theo-menu__detail">Iași</li>')
        ->not->toContain('mailto:');
});

it('shows the top bar sections, with the Soluții dropdown and no Blog', function () {
    preg_match('/<header class="site-nav.*?<\/header>/s', $this->get('/')->assertOk()->getContent(), $bar);

    expect($bar[0])
        ->toMatch('/<li class="site-nav__dropdown hidden md:block">\s*<a href="\/solutii" class="site-link"\s*>\s*<span class="site-link__mask"><span class="site-link__text">Soluții<\/span><\/span>\s*<svg class="site-nav__caret"/')
        ->toContain('<div class="site-dropdown">')
        ->toContain('href="/clienti"')
        ->toContain('href="/produse"')
        ->toContain('href="/despre"')
        ->not->toContain('href="/blog"');
});

it('shows Contact in the top bar as the main button, in the header tone', function () {
    $light = $this->get('/solutii')->assertOk()->getContent();

    expect($light)
        ->toMatch('/<a href="\/contact" class="pill btn-light">.*?<span data-text="Hai să discutăm">Hai să discutăm<\/span>/s')
        ->not->toContain('class="site-link" >Contact');

    Page::factory()->create(['slug' => 'contact', 'blocks' => []]);

    expect($this->get('/contact')->assertOk()->getContent())->toContain('class="pill btn-light" aria-current="page"');
});

it('keeps fixed controls outside the smooth-scrolled content and the top bar inside it', function () {
    $html = $this->get('/')->assertOk()->getContent();
    $wrapperAt = strpos($html, '<div id="smooth-wrapper"');

    expect($wrapperAt)->not->toBeFalse()
        ->and(strpos($html, 'class="site-burger"'))->toBeLessThan($wrapperAt)
        ->and(strpos($html, 'id="meniu" class="theo-menu"'))->toBeLessThan($wrapperAt)
        ->and(strpos($html, '<header class="site-nav'))->toBeGreaterThan($wrapperAt)
        ->and(strpos($html, '<main'))->toBeGreaterThan($wrapperAt)
        ->and(strpos($html, 'class="page-curtain"'))->toBeGreaterThan(strpos($html, '</main>'));
});

it('ships the page-transition curtain and the prefetch hints', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain("sessionStorage.getItem('webis:curtain')")
        ->toContain("nav.type === 'back_forward'")
        ->toContain('data-curtain-path')
        ->toContain('<script type="speculationrules">')
        ->toContain('"href_matches": "/admin/*"');
});

it('keeps the home page to its blocks: hero, Despre noi, the illustrated service cards', function () {
    Page::factory()->ofType(PageType::Home)->create([
        'slug' => 'acasa',
        'blocks' => [
            ['id' => 'h', 'type' => 'hero', 'v' => 1, 'data' => ['layout' => 'centered', 'background' => 'gradient-violet', 'heading' => 'Titlu']],
            ['id' => 'f', 'type' => 'features', 'v' => 1, 'data' => ['layout' => 'cards', 'columns' => 3, 'items' => [['title' => 'Software la comandă']]]],
        ],
    ]);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('class="svc-cards"')
        ->toContain('data-hero-leave="overlap"')
        // Then the services as three chapters, in order, each a section with its h2.
        ->and(preg_match_all('/<section id="[^"]+" class="chapter chapter--(light|dark|lavender)"/', $html, $chapters))->toBe(3)
        ->and($chapters[1])->toBe(['light', 'dark', 'lavender'])
        ->and(substr_count($html, '<h2 id="chapter-'))->toBe(3)
        // 01 and 02 show what they build as cells, 01 its projects as cards;
        // 03 as an explorer of its 3 types.
        ->and(substr_count($html, 'class="chapter-cell"'))->toBe(6 + 3)
        ->and(substr_count($html, 'class="project-card"'))->toBe(3)
        ->and(substr_count($html, 'data-explorer>'))->toBe(1)
        ->and(substr_count($html, 'data-explorer-row>'))->toBe(3)
        ->and(substr_count($html, 'data-explorer-panel>'))->toBe(3)
        // A type with a project says so; one without lists its modules.
        ->and(substr_count($html, '<span class="explorer-row__tag">1 proiect</span>'))->toBe(2)
        ->and(substr_count($html, 'Module tipice:'))->toBe(1)
        ->and($html)->toContain('id="explorer-web-1-tab" class="explorer-row" aria-controls="explorer-web-1-panel"')
        // SmileSoft across the row, RIMS and Bugetare participativă below it.
        ->and(substr_count($html, 'class="product-panel product-panel--small"'))->toBe(2)
        ->and($html)->toMatch('/<a href="https:\/\/smilesoft\.ro"[^>]* target="_blank" rel="noopener">/')
        ->toContain('<h4 class="product-panel__name">RIMS</h4>')
        ->toMatch('/<a href="\/contact" class="arrow-link[^"]*product-panel__link[^"]*">.*?Cere o prezentare/s')
        ->toMatch('/<a href="https:\/\/bugetare\.ro"[^>]* target="_blank" rel="noopener">/')
        ->not->toContain('confirmo.ro')
        ->toContain('href="/creare-magazin-online"')
        ->toContain('href="/mentenanta"')
        // The reviews: their own section, after the chapters.
        ->and(substr_count($html, 'class="chapter-review"'))->toBe(3)
        // As a carousel: each review a slide, its text openable; no buttons.
        ->and($html)->toContain('aria-roledescription="carusel"')
        ->toContain('aria-label="1 din 3" data-review>')
        ->toContain('aria-expanded="false" aria-controls="recenzie-1-text" hidden data-review-more><span data-review-more-label>Citește tot</span>')
        ->and(strpos($html, '<section id="recenzii" class="site-reviews'))->toBeGreaterThan(strpos($html, 'id="web-si-e-commerce"'))
        ->and(substr($html, strpos($html, 'id="web-si-e-commerce"'), strpos($html, 'id="recenzii"') - strpos($html, 'id="web-si-e-commerce"')))->not->toContain('chapter-review')
        // No SEO anywhere visitors can see.
        ->and($html)->not->toMatch('/>[^<]*\bSEO\b/')
        // The earlier sections are gone.
        ->and($html)->not->toContain('selected-projects')
        ->not->toContain('own-products-title')
        ->not->toContain('how-we-work-title')
        ->not->toContain('data-contact-form');
});

it('shows the latest articles after the reviews, examples until posts are published', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect(strpos($html, '<section id="blog" class="site-blog'))->toBeGreaterThan(strpos($html, 'id="recenzii"'))
        ->and(substr_count($html, 'class="post-card"'))->toBe(3)
        ->and($html)->toContain('<!-- DUMMY DATA: example articles')
        ->toContain('<time datetime="2026-09-22">22 septembrie 2026</time>');

    $post = Post::factory()->create([
        'title' => 'Primul articol',
        'slug' => 'primul-articol',
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    expect($this->get('/')->getContent())
        ->toContain('<a href="'.url('/blog/primul-articol').'" class="post-card">')
        ->toContain('Primul articol')
        ->not->toContain('DUMMY DATA: example articles')
        ->and(substr_count($this->get('/')->getContent(), 'class="post-card"'))->toBe(1)
        ->and($post->isPublished())->toBeTrue();
});

it('closes every page with the footer on the violet gradient: the call, the links, the details', function () {
    $company = app(CompanySettings::class);
    $company->phone = '0770 700 607';
    $company->email = 'contact@webis.ro';
    $company->save();

    $html = $this->get('/solutii')->assertOk()->getContent();

    preg_match('/<footer class="site-footer".*?<\/footer>/s', $html, $footer);

    expect($footer[0])
        ->toContain('<canvas data-soffit data-palette="violet"')
        ->toContain('href="/contact"')
        ->toContain('href="tel:0770700607"')
        ->toContain('href="mailto:contact@webis.ro"')
        ->toContain('Software la comandă')
        ->toContain('Confidențialitate')
        ->toContain('© '.now()->year);
});

it('sets the tone of the sections below the hero on /clienti from one setting', function () {
    config(['site.lower_tone' => 'dark']);
    expect($this->get('/clienti')->assertOk()->getContent())->toMatch('/<html lang="ro"\s+data-lower="dark"/');

    config(['site.lower_tone' => 'light']);
    expect($this->get('/clienti')->getContent())->toMatch('/<html lang="ro"\s+data-lower="light"/');

    // Other pages are not affected.
    config(['site.lower_tone' => 'dark']);
    expect($this->get('/solutii')->assertOk()->getContent())->not->toContain('data-lower');
});

it('serves a copy of the home page at /clienti, marked as its own design variant', function () {
    Page::factory()->ofType(PageType::Home)->create([
        'slug' => 'acasa',
        'blocks' => [['id' => 'h', 'type' => 'hero', 'v' => 1, 'data' => ['layout' => 'centered', 'heading' => 'Titlul de acasă']]],
    ]);

    $html = $this->get('/clienti')->assertOk()->getContent();

    expect($html)
        ->toMatch('/<html lang="ro"[^>]*data-variant="clienti"/')
        ->toContain('<title>Clienți | Webis</title>')
        ->toContain('Titlul de acasă')
        ->toContain('id="contact"');

    // The home page itself is not the variant.
    expect($this->get('/')->getContent())->not->toContain('data-variant');
});
