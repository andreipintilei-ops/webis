<?php

use App\Actions\Seo\HandleNotFound;
use App\Enums\PageType;
use App\Models\NotFoundLog;
use App\Models\Page;
use App\Models\Project;
use App\Models\Redirect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

/**
 * An absolute URL on the site, as a Location header carries it. (The test
 * client trims a request's trailing slash, so `get('/x/')` asks for `/x`.)
 */
function absolute(string $path): string
{
    return rtrim(config('app.url'), '/').$path;
}

it('answers an exact row with a 301 to its target', function () {
    Redirect::factory()->create(['source_path' => '/creare-site-vechi', 'target' => '/creare-site-nou']);

    $this->get('/creare-site-vechi/')
        ->assertStatus(301)
        ->assertHeader('Location', absolute('/creare-site-nou'));
});

it('answers a temporary row with a 302', function () {
    Redirect::factory()->create(['source_path' => '/promotie', 'target' => '/oferta/', 'status_code' => 302]);

    $this->get('/promotie')->assertStatus(302)->assertRedirect('/oferta/');
});

it('emits the target exactly as stored', function () {
    Redirect::factory()->create(['source_path' => '/vechi', 'target' => '/nou/']);
    Redirect::factory()->create(['source_path' => '/extern', 'target' => 'https://example.com/pagina/']);

    $this->get('/vechi')->assertHeader('Location', absolute('/nou/'));
    $this->get('/extern')->assertHeader('Location', 'https://example.com/pagina/');
});

it('carries the query string onto a target that has none', function () {
    Redirect::factory()->create(['source_path' => '/vechi', 'target' => '/nou']);
    Redirect::factory()->create(['source_path' => '/cu-query', 'target' => '/nou?sursa=x']);

    $this->get('/vechi?utm_source=google&utm_medium=cpc')
        ->assertHeader('Location', absolute('/nou?utm_source=google&utm_medium=cpc'));
    $this->get('/cu-query?utm_source=google')
        ->assertHeader('Location', absolute('/nou?sursa=x'));
});

it('matches a prefix row against everything under it', function () {
    Redirect::factory()->prefix()->create(['source_path' => '/tag', 'target' => '/blog/']);

    $this->get('/tag/seo/')->assertStatus(301)->assertHeader('Location', absolute('/blog/'));
    $this->get('/tag')->assertStatus(301);
    // A sibling that merely starts with the same letters is not under it.
    $this->get('/tagged')->assertNotFound();
});

it('prefers the longest matching prefix', function () {
    Redirect::factory()->prefix()->create(['source_path' => '/servicii-vechi', 'target' => '/portofoliu/']);
    Redirect::factory()->prefix()->create(['source_path' => '/servicii-vechi/web', 'target' => '/web/']);

    $this->get('/servicii-vechi/web/magazine')->assertRedirect('/web/');
    $this->get('/servicii-vechi/seo')->assertRedirect('/portofoliu/');
});

it('prefers an exact row over a prefix that also matches', function () {
    Redirect::factory()->prefix()->create(['source_path' => '/tag', 'target' => '/blog/']);
    Redirect::factory()->prefix()->create(['source_path' => '/tag/seo', 'target' => '/blog/categorie/marketing/']);
    Redirect::factory()->create(['source_path' => '/tag/seo/ghid', 'target' => '/ce-este-seo/']);

    $this->get('/tag/seo/ghid')->assertRedirect('/ce-este-seo/');
    $this->get('/tag/seo/altceva')->assertRedirect('/blog/categorie/marketing/');
});

it('never matches an exact row against the paths under it', function () {
    Redirect::factory()->prefix()->create(['source_path' => '/autor', 'target' => '/blog/']);
    Redirect::factory()->create(['source_path' => '/autor/andrei', 'target' => '/despre-noi/']);

    $this->get('/autor/andrei/pagina')->assertRedirect('/blog/');
});

it('answers a gone row with a 410 page and no Location', function () {
    Redirect::factory()->gone()->create(['source_path' => '/atasament-vechi']);

    $this->get('/atasament-vechi')
        ->assertStatus(410)
        ->assertViewIs('errors.410')
        ->assertHeaderMissing('Location')
        ->assertSee('Eroare 410')
        ->assertSee('Pagina a fost ștearsă definitiv.');
});

it('counts a hit once without touching updated_at', function () {
    $this->travelTo(now()->subWeek());
    $redirect = Redirect::factory()->create(['source_path' => '/vechi', 'target' => '/nou']);
    $edited = $redirect->updated_at;
    $this->travelBack();

    $this->get('/vechi')->assertStatus(301);

    $redirect->refresh();
    expect($redirect->hits)->toBe(1)
        ->and($redirect->last_hit_at)->not->toBeNull()
        ->and($redirect->last_hit_at->isToday())->toBeTrue()
        ->and($redirect->updated_at->equalTo($edited))->toBeTrue();
});

it('never fires a row whose source is a live page', function () {
    Page::factory()->create(['slug' => 'contact']);
    Redirect::factory()->create(['source_path' => '/contact', 'target' => '/altundeva']);

    $this->get('/contact')->assertOk();
    $this->get('/realizare-site-uri-tematice')->assertOk();
});

it('lets slug history win over a conflicting row', function () {
    $page = Page::factory()->create(['slug' => 'creare-site']);
    $page->update(['slug' => 'creare-site-de-prezentare']);
    Redirect::factory()->create(['source_path' => '/creare-site', 'target' => '/altundeva']);

    $this->get('/creare-site')->assertStatus(301)->assertRedirect('/creare-site-de-prezentare');
});

it('never redirects a POST', function () {
    Redirect::factory()->create(['source_path' => '/formular-vechi', 'target' => '/nou']);

    $this->post('/formular-vechi')->assertStatus(405);
    $this->post('/formular-vechi/trimite')->assertNotFound();
});

it('leaves admin 404s alone: no redirect, no log', function () {
    Redirect::factory()->prefix()->create(['source_path' => '/admin', 'target' => '/']);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/pagini/999999/editeaza')
        ->assertNotFound();

    expect(NotFoundLog::query()->count())->toBe(0);
});

it('treats a row that targets itself as a miss, not a loop', function () {
    Redirect::factory()->create(['source_path' => '/bucla', 'target' => '/bucla/']);
    Redirect::factory()->create(['source_path' => '/bucla-absoluta', 'target' => absolute('/Bucla-Absoluta')]);

    $this->get('/bucla')->assertNotFound();
    $this->get('/bucla-absoluta')->assertNotFound();
});

describe('WordPress ids', function () {
    beforeEach(function () {
        // A map of the test's own, never the repository's.
        $this->legacyStorage = sys_get_temp_dir().'/webis-legacy-ids-'.uniqid();
        config(['site.legacy_ids' => $this->legacyStorage.'/legacy-ids.json']);
    });

    afterEach(function () {
        File::deleteDirectory($this->legacyStorage);
    });

    it('sends ?p=, ?page_id= and ?attachment_id= to the mapped address', function () {
        File::ensureDirectoryExists(dirname(HandleNotFound::legacyIdsPath()));
        File::put(HandleNotFound::legacyIdsPath(), json_encode([
            '123' => '/ce-este-seo/',
            '45' => '/contact/',
            '67' => '/proiect/magazin/',
        ]));

        $this->get('/?p=123')->assertStatus(301)->assertHeader('Location', absolute('/ce-este-seo/'));
        $this->get('/?page_id=45&utm_source=newsletter')
            ->assertStatus(301)
            ->assertHeader('Location', absolute('/contact/?utm_source=newsletter'));
        $this->get('/?attachment_id=67')->assertStatus(301)->assertHeader('Location', absolute('/proiect/magazin/'));
        // An id the map doesn't know is a deleted post or spam (/?p=19525).
        $this->get('/?p=999')->assertStatus(410)->assertViewIs('errors.410')->assertHeaderMissing('Location');
        $this->get('/?page_id=19525')->assertStatus(410);
        // Not an id at all: the home page.
        $this->get('/?p=abc')->assertOk();
    });

    it('ships the map with the code, so every server has it', function () {
        $repository = base_path('database/data/legacy-ids.json');
        $ids = json_decode((string) file_get_contents($repository), true);

        expect($ids)->toBeArray()->not->toBeEmpty()
            // The article from the report: /?p=12228.
            ->and($ids['12228'] ?? null)->toBe('/ce-este-algoritmul-md5/');
    });

    it('is a no-op without the map', function () {
        expect(is_file(HandleNotFound::legacyIdsPath()))->toBeFalse();

        $this->get('/?p=123')->assertOk();
    });
});

it('answers any feed with a 410', function (string $path) {
    $this->get($path)->assertStatus(410)->assertViewIs('errors.410')->assertHeaderMissing('Location');
})->with(['/feed/', '/comments/feed/', '/ce-este-seo/feed/', '/tag/seo/feed']);

it('sends an archive page to the archive', function () {
    Redirect::factory()->prefix()->create(['source_path' => '/categorie-veche', 'target' => '/blog/categorie/seo/']);

    $this->get('/categorie-veche/page/3/')
        ->assertStatus(301)
        ->assertHeader('Location', absolute('/blog/categorie/seo/'));
    // A live archive is its own target.
    $this->get('/blog/page/2/')->assertStatus(301)->assertHeader('Location', absolute('/blog/'));
});

it('leaves a bare /page/N to the table', function () {
    $this->get('/page/2/')->assertNotFound();

    Redirect::factory()->prefix()->create(['source_path' => '/page', 'target' => '/blog/']);

    $this->get('/page/2/')->assertStatus(301)->assertHeader('Location', absolute('/blog/'));
});

it('sends an uppercase address to its lowercase page', function () {
    Page::factory()->create(['slug' => 'ce-este-seo']);
    Project::factory()->create(['slug' => 'magazin-online']);

    $this->get('/Ce-Este-SEO?utm_source=x')
        ->assertStatus(301)
        ->assertHeader('Location', absolute('/ce-este-seo/?utm_source=x'));
    $this->get('/Proiect/Magazin-Online')->assertStatus(301)->assertHeader('Location', absolute('/proiect/magazin-online/'));
    $this->get('/BLOG')->assertStatus(301)->assertHeader('Location', absolute('/blog/'));
});

it('leaves an uppercase address with no page behind it a 404', function () {
    Page::factory()->draft()->create(['slug' => 'ciorna']);

    $this->get('/Nu-Exista')->assertNotFound();
    $this->get('/Ciorna')->assertNotFound();
    $this->get('/Proiect/Nu-Exista')->assertNotFound();
});

describe('dated addresses', function () {
    it('sends a dated address the table lacks to the article with that slug', function () {
        Page::factory()->ofType(PageType::Article)->create(['slug' => 'ce-este-hosting']);

        $this->get('/2026/04/14/ce-este-hosting/?utm_source=x')
            ->assertStatus(301)
            ->assertHeader('Location', absolute('/ce-este-hosting/?utm_source=x'));
    });

    it('leaves a dated address alone unless an article is published at its slug', function () {
        Page::factory()->create(['slug' => 'contact-vechi']);
        Page::factory()->ofType(PageType::Article)->draft()->create(['slug' => 'ciorna']);

        $this->get('/2026/04/14/contact-vechi/')->assertNotFound();
        $this->get('/2026/04/14/ciorna/')->assertNotFound();
        $this->get('/2026/04/14/nu-exista/')->assertNotFound();
    });

    it('lets a row win over it: the 410 prefixes for injected spam', function () {
        Page::factory()->ofType(PageType::Article)->create(['slug' => 'ce-este-hosting']);
        Redirect::factory()->prefix()->gone()->create(['source_path' => '/2025/11']);
        Redirect::factory()->prefix()->gone()->create(['source_path' => '/2026/03/28']);

        $this->get('/2025/11/13/das-abenteuer-im-lucky-hunter-casino/')->assertStatus(410);
        $this->get('/2026/03/28/ce-este-hosting/')->assertStatus(410);
        $this->get('/2026/03/29/ce-este-hosting/')->assertStatus(301);
    });
});

it('sends the old project archive and its pages to the portfolio, in one hop', function () {
    $this->get('/proiect/page/5/')->assertStatus(301)->assertHeader('Location', absolute('/portofoliu/'));
    $this->get('/PROIECT')->assertStatus(301)->assertHeader('Location', absolute('/portofoliu/'));
    $this->get('/Clienti')->assertStatus(301)->assertHeader('Location', absolute('/portofoliu/'));
});
