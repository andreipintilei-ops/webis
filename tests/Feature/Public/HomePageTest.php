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

it('opens the menu panel, behind its curtain, from the round header button and the top bar\'s link', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('class="site-burger__button" data-menu-toggle="theodore"')
        ->toMatch('#class="site-link" data-menu-toggle="theodore" aria-controls="meniu"#')
        ->toContain('id="meniu" class="theo-menu" data-theo-menu')
        ->toContain('data-theo-overlay')
        // The dimmed page beside it closes it.
        ->toContain('data-theo-backdrop')
        // The round button is the toggle, so the menu has no ✕ of its own.
        ->not->toContain('data-theo-close')
        // The other panel is a kept variant, not on the site.
        ->not->toContain('id="site-panel"');
});

it('lists in the menu the pages (Contact last), the three services and how to reach us', function () {
    app(\App\Settings\CompanySettings::class)->fill(['email' => 'contact@webis.ro', 'phone' => '0770 700 607'])->save();

    $html = $this->get('/')->assertOk()->getContent();
    preg_match('#<div id="meniu".*?<svg class="theo-overlay"#s', $html, $menu);
    preg_match_all('#<a href="([^"]+)" class="theo-menu__link#', $menu[0], $pages);
    preg_match_all('#<a href="([^"]+)" class="theo-menu__service#', $menu[0], $services);

    expect($pages[1])->toBe(['/', '/portofoliu', '/despre', '/blog', '/contact'])
        ->and($services[1])->toBe(['/software-la-comanda', '/produse', '/web'])
        ->and($menu[0])->toContain('href="mailto:contact@webis.ro"')
        ->toContain('href="tel:')
        // The current page is marked.
        ->toMatch('#href="/" class="theo-menu__link is-active" data-theo-item\s+aria-current="page"#');
});

it('shows the top bar sections, with the Servicii dropdown and no Produse', function () {
    preg_match('#<header class="site-nav.*?</header>#s', $this->get('/')->assertOk()->getContent(), $bar);

    expect($bar[0])
        // The dropdown opens under a chevron.
        ->toMatch('#<li class="site-nav__dropdown hidden md:block">\s*<a href="/solutii" class="site-link"\s*>\s*<span class="roll">.*?</span>\s*<svg class="site-nav__chevron"#s')
        // Its label, rolling letter by letter (components/ui/roll).
        ->and(strip_tags($bar[0]))->toContain('Servicii')
        ->and($bar[0])
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

    preg_match('/<a href="\/contact" class="pill btn-light">.*?<\/a>/s', $light, $pill);

    // The label, rolling letter by letter (components/ui/roll).
    expect(strip_tags($pill[0]))->toContain('Hai să discutăm')
        ->and($light)->not->toContain('class="site-link" >Contact');

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
