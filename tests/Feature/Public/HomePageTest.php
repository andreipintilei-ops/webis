<?php

use App\Enums\PageType;
use App\Models\Page;
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
        ->toMatch('/<a href="\/contact" class="pill pill--sm btn-light">.*?<span data-text="Hai să discutăm">Hai să discutăm<\/span>/s')
        ->not->toContain('class="site-link" >Contact');

    Page::factory()->create(['slug' => 'contact', 'blocks' => []]);

    expect($this->get('/contact')->assertOk()->getContent())->toContain('class="pill pill--sm btn-light" aria-current="page"');
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
