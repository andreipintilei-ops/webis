<?php

use App\Enums\PageType;
use App\Models\Page;
use App\Models\User;
use App\Settings\FooterCtaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The footer's closing call on a page: whether it has the W, its title, its
 * button's link and label (the pill's label rolls letter by letter: read as
 * text, it is whole).
 *
 * @return array{w: bool, title: string, href: string, button: string}|null
 */
function footerCall(string $html): ?array
{
    if (! preg_match('#<div class="site-footer__cta([^"]*)".*?<h2 class="site-footer__title">([^<]*)</h2>.*?<div class="site-footer__reach">\s*<a href="([^"]+)"(.*?)</a>#s', $html, $m)) {
        return null;
    }

    return [
        'w' => str_contains($m[1], '--w'),
        'title' => html_entity_decode($m[2]),
        'href' => html_entity_decode($m[3]),
        'button' => trim((string) preg_replace('/\s+/', ' ', html_entity_decode(strip_tags('<a'.$m[4])))),
    ];
}

it('closes each section on its own call, beside the W, from the settings', function (string $path, string $title, string $button, string $href) {
    expect(footerCall($this->get($path)->assertOk()->getContent()))
        ->toBe(['w' => true, 'title' => $title, 'href' => $href, 'button' => $button]);
})->with([
    ['/software-la-comanda/', 'Ce ai vrea să facă aplicația ta?', 'Discută un proiect', '/contact/'],
    ['/produse/', 'Vrei să vezi un produs în acțiune?', 'Cere o prezentare', '/contact/?cerere=prezentare'],
    ['/web/', 'Site nou sau refacerea celui vechi?', 'Discută despre site', '/contact/'],
    ['/portofoliu/', 'Îți place ce ai văzut?', 'Hai să discutăm', '/contact/'],
    ['/blog/', 'Ai o întrebare care nu e aici?', 'Scrie-ne', '/contact/'],
    ['/creare-magazin-online/', 'Vrei să vinzi online?', 'Cere o ofertă', '/contact/'],
]);

it('gives an article the blog\'s call, another page the default, and the home page its text-only call; none on /contact/', function () {
    Page::factory()->ofType(PageType::Home)->create(['slug' => 'acasa']);
    Page::factory()->ofType(PageType::Contact)->create(['slug' => 'contact']);
    Page::factory()->ofType(PageType::Article)->create(['slug' => 'un-articol']);
    Page::factory()->create(['slug' => 'o-pagina']);

    expect(footerCall($this->get('/un-articol/')->getContent())['title'])->toBe('Ai o întrebare care nu e aici?')
        ->and(footerCall($this->get('/o-pagina/')->getContent()))->toMatchArray(['w' => true, 'title' => 'Ai un proiect în minte?'])
        ->and(footerCall($this->get('/')->getContent()))->toMatchArray(['w' => false, 'title' => 'Ai un proiect în minte?'])
        ->and(footerCall($this->get('/contact/')->getContent()))->toBeNull();
});

it('takes the default\'s field for any left blank', function () {
    $settings = app(FooterCtaSettings::class);
    $pages = $settings->pages;
    $pages['web'] = ['title' => 'Doar titlul meu', 'intro' => null, 'button' => '', 'url' => null];
    $settings->pages = $pages;
    $settings->save();

    expect(footerCall($this->get('/web/')->getContent()))
        ->toMatchArray(['title' => 'Doar titlul meu', 'button' => 'Hai să discutăm', 'href' => '/contact/'])
        ->and($this->get('/web/')->getContent())->toContain('Un site de prezentare, un magazin online sau software la comandă');
});

it('starts the message as a demo request from the Produse SaaS call', function () {
    Page::factory()->ofType(PageType::Contact)->create(['slug' => 'contact']);

    $this->get('/contact/?cerere=prezentare')->assertOk()
        ->assertSee('>Aș dori o prezentare pentru produsul: </textarea>', escape: false);
});

it('saves each page\'s call from Setări › Subsol, and refuses a link that is neither on the site nor https', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->put(route('admin.settings.footer-cta'), [
        'pages' => ['blog' => ['title' => 'Întrebări?', 'intro' => 'Scrie-ne.', 'button' => 'Scrie', 'url' => '/contact/']],
    ])->assertSessionHasNoErrors();

    expect(app(FooterCtaSettings::class)->pages['blog'])->toBe(['title' => 'Întrebări?', 'intro' => 'Scrie-ne.', 'button' => 'Scrie', 'url' => '/contact/'])
        ->and(app(FooterCtaSettings::class)->pages['web']['title'])->toBeNull();

    $this->put(route('admin.settings.footer-cta'), [
        'pages' => ['blog' => ['url' => 'javascript:alert(1)']],
    ])->assertSessionHasErrors('pages.blog.url');
});
