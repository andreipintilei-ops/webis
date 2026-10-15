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

it('lays out the full-screen menu: the services with what they are, the other pages, one way to Contact', function () {
    $html = $this->get('/')->assertOk()->getContent();
    preg_match('#<div id="meniu" class="theo-menu".*?</svg>#s', $html, $menu);
    preg_match('#<nav class="theo-menu__nav".*?</nav>#s', $menu[0], $nav);
    preg_match_all('#<span class="theo-menu__label">([^<]+)</span>#', $nav[0], $labels);
    preg_match_all('#<a href="([^"]+)" class="theo-menu__page"#', $nav[0], $pages);

    // The three services, each with its line; no numbers.
    expect($labels[1])->toBe(['Software la comandă', 'Produse SaaS', 'Web și e-commerce'])
        ->and($nav[0])->not->toContain('theo-menu__index')
        ->toContain('<span class="theo-menu__blurb">Produse gata de folosit, pe abonament lunar.</span>')
        ->and(substr_count($nav[0], 'class="theo-menu__blurb"'))->toBe(3)
        // Under them, the other pages — Contact not among them.
        ->and($pages[1])->toBe(['/portofoliu', '/despre', '/blog'])
        // Contact once: the button.
        ->and(substr_count($menu[0], 'href="/contact"'))->toBe(1)
        ->and($menu[0])->toMatch('#<a href="/contact" class="pill btn-dark[^"]*" data-theo-link(="data-theo-link")?>.*?Hai să discutăm#s')
        // Contact: only what is filled in (here, the town — no e-mail or phone).
        ->toContain('<p class="theo-menu__row theo-menu__row--muted">Iași</p>')
        ->not->toContain('mailto:')
        // The grouped links moved to the footer; the legal ones stayed.
        ->and($menu[0])->not->toContain('theo-menu__heading')
        ->toMatch('#<a href="/accesibilitate" class="theo-menu__detail" data-theo-link>Accesibilitate</a>#');
});

it('shows the top bar sections, with the Servicii dropdown and no Produse', function () {
    preg_match('#<header class="site-nav.*?</header>#s', $this->get('/')->assertOk()->getContent(), $bar);

    expect($bar[0])
        // The dropdown opens under a plus, not a caret.
        ->toMatch('#<li class="site-nav__dropdown hidden md:block">\s*<a href="/solutii" class="site-link"\s*>\s*<span class="site-link__mask"><span class="site-link__text">Servicii</span></span>\s*<svg class="site-nav__plus"#')
        ->not->toContain('site-nav__caret')
        ->toContain('<div class="site-dropdown">')
        // Its three services.
        ->toContain('<a href="/software-la-comanda" class="site-dropdown__link">Software la comandă</a>')
        ->toContain('<a href="/produse" class="site-dropdown__link">Produse SaaS</a>')
        ->toContain('<a href="/web" class="site-dropdown__link">Web și e-commerce</a>')
        // Then the sections, Blog among them; Produse is only in the dropdown.
        ->toContain('href="/portofoliu"')
        ->toContain('href="/despre"')
        ->toContain('href="/blog"')
        ->and(substr_count($bar[0], 'site-link__text">Produse<'))->toBe(0);
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

it('keeps the home page to its blocks, then the stage, the reviews and the articles', function () {
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
        // Then the three services as one stage, in order, each with its h2.
        ->and(substr_count($html, 'data-stage-chapter'))->toBe(3)
        ->and($html)->toMatch('/<article id="software-la-comanda"[^>]*data-side="right"/')
        ->toMatch('/<article id="produse-saas"[^>]*data-side="left"/')
        ->toMatch('/<article id="web-si-e-commerce"[^>]*data-side="right"/')
        ->and(substr_count($html, '<h2 id="chapter-'))->toBe(3)
        // 01 lists four capabilities, 02 three products, 03 three columns.
        ->and(substr_count($html, 'class="stage-item"'))->toBe(4)
        ->and(substr_count($html, 'class="stage-product"'))->toBe(3)
        ->and(substr_count($html, 'class="stage-item stage-item--link"'))->toBe(3)
        ->and($html)->toMatch('#<a href="https://smilesoft.ro"[^>]*target="_blank"[^>]*rel="noopener"#')
        ->toMatch('#<a href="https://bugetare.ro"[^>]*target="_blank"[^>]*rel="noopener"#')
        ->toContain('href="/creare-magazin-online"')
        ->toContain('href="/mentenanta"')
        // Then the closing panel the plates gather into.
        ->toContain('data-stage-finale')
        ->toContain('Site, platformă sau aplicație?')
        // The reviews: their own section, after the stage.
        ->and(substr_count($html, 'class="chapter-review"'))->toBe(3)
        ->and($html)->toContain('aria-roledescription="carusel"')
        ->toContain('aria-label="1 din 3" data-review>')
        ->and(strpos($html, '<section id="recenzii" class="site-reviews'))->toBeGreaterThan(strpos($html, 'id="web-si-e-commerce"'))
        // No SEO anywhere visitors can see.
        ->and($html)->not->toMatch('/>[^<]*SEO/')
        // The earlier sections, and the chapters this replaced, are gone.
        ->and($html)->not->toContain('selected-projects')
        ->not->toContain('own-products-title')
        ->not->toContain('how-we-work-title')
        ->not->toContain('data-contact-form')
        ->not->toContain('class="chapter-cell"')
        ->not->toContain('data-explorer>');
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
        ->toContain('href="tel:+40770700607"')
        ->toContain('href="mailto:contact@webis.ro"')
        ->toContain('Software la comandă')
        ->toContain('Confidențialitate')
        ->toContain('© '.now()->year);
});

it('sets the tone of the sections below the hero on /poveste from one setting', function () {
    config(['site.lower_tone' => 'dark']);
    expect($this->get('/poveste')->assertOk()->getContent())->toMatch('/<html lang="ro"\s+data-lower="dark"/');

    config(['site.lower_tone' => 'light']);
    expect($this->get('/poveste')->getContent())->toMatch('/<html lang="ro"\s+data-lower="light"/');

    // Other pages are not affected.
    config(['site.lower_tone' => 'dark']);
    expect($this->get('/solutii')->assertOk()->getContent())->not->toContain('data-lower');
});

it('keeps the services story trial at /poveste, on a copy of the home page', function () {
    Page::factory()->ofType(PageType::Home)->create([
        'slug' => 'acasa',
        'blocks' => [['id' => 'h', 'type' => 'hero', 'v' => 1, 'data' => ['layout' => 'centered', 'heading' => 'Titlul de acasă']]],
    ]);

    $html = $this->get('/poveste')->assertOk()->getContent();

    expect($html)
        ->toMatch('/<html lang="ro"[^>]*data-variant="poveste"/')
        ->toContain('<title>Poveste | Webis</title>')
        ->toContain('Titlul de acasă')
        ->toContain('id="contact"')
        ->toContain('data-story');

    // The home page is its own variant, not this one.
    expect($this->get('/')->getContent())->not->toContain('data-variant="poveste"');
});

it('builds the home page around the stage, and leaves /produse to the CMS', function () {
    Page::factory()->ofType(PageType::Home)->create([
        'slug' => 'acasa',
        'title' => 'Acasă',
        'blocks' => [['id' => 'h', 'type' => 'hero', 'v' => 1, 'data' => ['layout' => 'centered', 'heading' => 'Titlul de acasă']]],
    ]);
    Page::factory()->create(['slug' => 'produse', 'title' => 'Pagina din CMS', 'blocks' => []]);

    expect($this->get('/')->assertOk()->getContent())
        ->toMatch('/<html lang="ro"[^>]*data-variant="stage"/')
        ->toContain('<title>Acasă | Webis</title>')
        ->toContain('Titlul de acasă')
        ->toContain('data-stage')
        ->toContain('data-shapeshifter')
        ->toContain('data-stage-finale')
        ->toContain('Site, platformă sau aplicație?');

    // The slug the trial used to hide now serves its own CMS page.
    expect($this->get('/produse')->assertOk()->getContent())
        ->toContain('Pagina din CMS')
        ->not->toContain('data-shapeshifter');
});
