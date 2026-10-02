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
    ['/clienti', 'Clienți'],
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
