<?php

use App\Enums\PageType;
use App\Models\Page;
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

it('lists the articles at /blog: a hero, the featured one, then nine cards to a page', function () {
    $html = $this->get('/blog')->assertOk()->getContent();

    expect($html)
        ->toContain('<title>Blog | Webis</title>')
        ->toContain('class="page-hero has-hero-planes"')
        // The planes, inline so they can float.
        ->toContain('<g class="plane plane--green">')
        ->toContain('DUMMY DATA')
        ->toContain('Cum poți avea un magazin online de succes')
        ->and(substr_count($html, 'post-featured__title'))->toBe(1)
        ->and(substr_count($html, 'class="post-card"'))->toBe(9)
        ->and($html)->toContain('aria-current="page">1</span>')
        ->toContain('href="'.route('posts.index').'?pagina=2"');
});

it('pages through the rest of the articles, without the featured one', function () {
    // 45 cards after the featured one: five pages of nine.
    $html = $this->get('/blog?pagina=5')->assertOk()->getContent();

    expect($html)
        ->toContain('<title>Blog, pagina 5 | Webis</title>')
        ->not->toContain('post-featured__title')
        ->and(substr_count($html, 'class="post-card"'))->toBe(9)
        ->and($html)->toContain('rel="prev"')
        ->not->toContain('rel="next"');

    $this->get('/blog?pagina=6')->assertNotFound();
});

it('answers an unknown address with the site\'s own not-found page', function () {
    $this->get('/pagina-care-nu-exista')->assertNotFound()
        ->assertSee('Pagina nu mai e aici.')
        ->assertSee('Poate a fost mutată sau adresa are o greșeală.')
        ->assertSee('Înapoi la prima pagină')
        ->assertSee('Vezi articolele');
});

it('gives the blog its own meta description', function () {
    $this->get('/blog')->assertOk()->assertSee(
        '<meta name="description" content="Articole despre software la comandă, produse SaaS, site-uri și magazine online, scrise de echipa Webis din Iași.">',
        escape: false,
    );
});
