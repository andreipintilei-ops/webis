<?php

use App\Enums\PageType;
use App\Http\Controllers\Public\ArticleController;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Redirect;
use App\Models\User;
use App\Routing\UrlGenerator;
use App\Support\PublicUrls;
use App\Support\TrailingSlash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

/**
 * An absolute URL on the site, as route() and a Location header give it.
 */
function siteUrl(string $path): string
{
    return rtrim(config('app.url'), '/').$path;
}

/*
| The trailing slash is canonical (/blog/, /proiect/x/): the URL generator
| emits it for public pages, CanonicalTrailingSlash 301s a slash-less request
| for one. The test client asks for the canonical form by itself (TestCase);
| `asWritten()` sends a path exactly as written, slash or not.
*/

it('sends a request exactly as written, slash and all', function () {
    Route::middleware('web')->get('sonda/{rest?}', fn (Request $request) => $request->getPathInfo())->where('rest', '.*');

    $this->asWritten()->get('/sonda/a/')->assertOk()->assertContent('/sonda/a/');
    $this->asWritten()->get('/sonda/a')->assertOk()->assertContent('/sonda/a');
});

it('asks for a public page in its canonical form by default', function () {
    $this->get('/blog')->assertOk();

    expect(app('request')->getPathInfo())->toBe('/blog/');

    // Not a page: as Laravel's client always had it.
    $this->get('/up')->assertOk();

    expect(app('request')->getPathInfo())->toBe('/up');
});

it('301s a slash-less request for a live page to its slash form, once, query kept', function () {
    Page::factory()->create(['slug' => 'despre-noi']);
    Project::factory()->create(['slug' => 'duo-stone']);
    $category = PostCategory::factory()->create(['slug' => 'seo']);
    Page::factory()->ofType(PageType::Article)->create(['slug' => 'ce-este-seo', 'post_category_id' => $category->id]);

    foreach (['/blog', '/portofoliu', '/despre-noi', '/ce-este-seo', '/proiect/duo-stone', '/blog/categorie/seo', '/web'] as $path) {
        $this->asWritten()->get($path)->assertStatus(301)->assertHeader('Location', siteUrl($path.'/'));
        $this->asWritten()->get($path.'/')->assertOk();
    }

    $this->asWritten()->get('/portofoliu?tip=magazin&utm_source=google')
        ->assertStatus(301)
        ->assertHeader('Location', siteUrl('/portofoliu/?tip=magazin&utm_source=google'));
    $this->asWritten()->get('/portofoliu/?tip=magazin&utm_source=google')->assertOk();

    $this->asWritten()->call('HEAD', '/blog')->assertStatus(301)->assertHeader('Location', siteUrl('/blog/'));
});

it('leaves the home page alone', function () {
    $this->asWritten()->get('/')->assertOk();
});

it('never touches a 404: an old address reaches its target in one hop, with or without the slash', function () {
    Page::factory()->create(['slug' => 'pagina-noua']);
    Redirect::factory()->create(['source_path' => '/pagina-veche', 'target' => '/pagina-noua/']);

    foreach (['/pagina-veche', '/pagina-veche/'] as $path) {
        $this->asWritten()->get($path)->assertStatus(301)->assertHeader('Location', siteUrl('/pagina-noua/'));
    }

    $this->asWritten()->get('/pagina-noua/')->assertOk();

    $this->asWritten()->get('/nu-exista')->assertNotFound();
    $this->asWritten()->get('/nu-exista/')->assertNotFound();
});

it('sends a renamed page or project to its new slash address in one hop', function () {
    $page = Page::factory()->create(['slug' => 'nume-vechi']);
    $page->update(['slug' => 'nume-nou']);
    $project = Project::factory()->create(['slug' => 'proiect-vechi']);
    $project->update(['slug' => 'proiect-nou']);

    $this->asWritten()->get('/nume-vechi')->assertStatus(301)->assertHeader('Location', siteUrl('/nume-nou/'));
    $this->asWritten()->get('/proiect/proiect-vechi/')->assertStatus(301)->assertHeader('Location', siteUrl('/proiect/proiect-nou/'));
});

it('sends the old archives straight to the portfolio\'s slash address', function () {
    // Route::redirect() answers with a relative Location.
    $this->asWritten()->get('/proiect/')->assertStatus(301)->assertHeader('Location', '/portofoliu/');
    $this->asWritten()->get('/clienti')->assertStatus(301)->assertHeader('Location', '/portofoliu/');
});

it('never 301s a POST: the contact form still validates and goes back', function () {
    $this->asWritten()->from('/contact/')->post('/cerere-oferta', [])
        ->assertStatus(302)
        ->assertRedirect('/contact/')
        ->assertSessionHasErrors('name');

    $this->asWritten()->from('/contact/')->post('/cerere-oferta/', [])->assertStatus(302);
});

it('leaves files and everything that is not a public page alone', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    foreach (['/robots.txt', '/sitemap.xml', '/favicon.ico', '/fisier.pdf', '/build/manifest.json', '/storage/poza.jpg'] as $path) {
        expect($this->asWritten()->get($path)->getStatusCode())->not->toBe(301, $path);
    }

    $this->asWritten()->get('/up')->assertOk();
    $this->asWritten()->get('/login')->assertOk();
    $this->asWritten()->get('/forgot-password')->assertOk();
    $this->asWritten()->get('/admin')->assertRedirect('/login');
    $this->asWritten()->actingAs($admin)->get('/admin')->assertOk();
    $this->asWritten()->actingAs($admin)->get('/admin/pagini')->assertOk();
    $this->asWritten()->actingAs($user)->get('/settings/profile')->assertOk();
});

it('leaves JSON and Inertia requests alone', function () {
    $this->asWritten()->getJson('/blog')->assertOk();

    expect($this->asWritten()->withHeader('X-Inertia', 'true')->get('/blog')->getStatusCode())->not->toBe(301);
});

it('emits the slash for public pages from route(), url() and action()', function () {
    // One generator everywhere: the helpers, the facade, the redirector.
    expect(app('url'))->toBeInstanceOf(UrlGenerator::class)
        ->and(URL::getFacadeRoot())->toBe(app('url'))
        ->and(app('redirect')->getUrlGenerator())->toBe(app('url'))
        ->and(redirect()->route('posts.index')->getTargetUrl())->toBe(siteUrl('/blog/'));

    expect(route('posts.index', absolute: false))->toBe('/blog/')
        ->and(route('posts.index'))->toBe(siteUrl('/blog/'))
        ->and(route('pages.show', 'x', false))->toBe('/x/')
        ->and(route('projects.show', 'x', false))->toBe('/proiect/x/')
        ->and(route('projects.index', absolute: false))->toBe('/portofoliu/')
        ->and(route('posts.category', 'seo', false))->toBe('/blog/categorie/seo/')
        ->and(route('services.web', absolute: false))->toBe('/web/')
        ->and(url('/blog'))->toBe(siteUrl('/blog/'))
        ->and(url('/blog/'))->toBe(siteUrl('/blog/'))
        ->and(url('/contact'))->toBe(siteUrl('/contact/'))
        ->and(url('/proiect/x'))->toBe(siteUrl('/proiect/x/'))
        ->and(action([ArticleController::class, 'index'], absolute: false))->toBe('/blog/');
});

it('puts the slash before a query string or a fragment', function () {
    expect(route('projects.index', ['tip' => 'magazin'], false))->toBe('/portofoliu/?tip=magazin')
        ->and(route('posts.category', ['slug' => 'seo', 'pagina' => 2], false))->toBe('/blog/categorie/seo/?pagina=2')
        ->and(url('/blog?pagina=2'))->toBe(siteUrl('/blog/?pagina=2'))
        ->and(url('/blog#articole'))->toBe(siteUrl('/blog/#articole'));
});

it('leaves admin, account, settings and non-GET routes, files, the root and other sites alone', function () {
    expect(route('admin.pages.index', absolute: false))->toBe('/admin/pagini')
        ->and(route('admin.dashboard', absolute: false))->toBe('/admin')
        ->and(route('login', absolute: false))->toBe('/login')
        ->and(route('password.reset', 'token', false))->toBe('/reset-password/token')
        ->and(route('profile.edit', absolute: false))->toBe('/settings/profile')
        ->and(url('/admin/pagini'))->toBe(siteUrl('/admin/pagini'))
        // A form's action: a POST route, its own address at a reserved slug.
        ->and(route('contact.store', absolute: false))->toBe('/cerere-oferta')
        ->and(url('/cerere-oferta'))->toBe(siteUrl('/cerere-oferta'))
        ->and(url('/robots.txt'))->toBe(siteUrl('/robots.txt'))
        ->and(url('/sitemap.xml'))->toBe(siteUrl('/sitemap.xml'))
        ->and(url('/build/assets/app.css'))->toBe(siteUrl('/build/assets/app.css'))
        ->and(url('/'))->toBe(rtrim(siteUrl('/'), '/'))
        ->and(route('home'))->toBe(url('/'))
        ->and(url('https://example.com/pagina'))->toBe('https://example.com/pagina');
});

it('keeps signed URLs exactly as signed, so they still validate', function () {
    $signed = URL::signedRoute('pages.show', ['slug' => 'x']);
    $temporary = URL::temporarySignedRoute('pages.show', now()->addHour(), ['slug' => 'x']);
    $relative = URL::signedRoute('pages.show', ['slug' => 'x'], absolute: false);

    expect(parse_url($signed, PHP_URL_PATH))->toBe('/x')
        ->and(URL::hasValidSignature(Request::create($signed)))->toBeTrue()
        ->and(URL::hasValidSignature(Request::create($temporary)))->toBeTrue()
        ->and(URL::hasValidRelativeSignature(Request::create($relative)))->toBeTrue()
        // Signing leaves the generator as it was, even when it throws.
        ->and(route('pages.show', 'x', false))->toBe('/x/');

    expect(fn () => URL::signedRoute('pages.show', ['slug' => 'x', 'signature' => 'y']))->toThrow(InvalidArgumentException::class)
        ->and(route('pages.show', 'x', false))->toBe('/x/');
});

it('serves a signed link to a private file', function () {
    Storage::disk('local')->put('wp2-semnat.txt', 'conținut');

    try {
        $url = Storage::disk('local')->temporaryUrl('wp2-semnat.txt', now()->addMinutes(5));

        $this->asWritten()->get($url)->assertOk();
        $this->asWritten()->get($url.'x')->assertForbidden();
    } finally {
        Storage::disk('local')->delete('wp2-semnat.txt');
    }
});

it('gives every model its address with the trailing slash', function () {
    expect(PublicUrls::for(Page::factory()->ofType(PageType::Home)->make(['slug' => 'acasa'])))->toBe('/')
        ->and(PublicUrls::for(Page::factory()->make(['slug' => 'despre-noi'])))->toBe('/despre-noi/')
        ->and(PublicUrls::for(Page::factory()->ofType(PageType::Article)->make(['slug' => 'ce-este-seo'])))->toBe('/ce-este-seo/')
        ->and(PublicUrls::for(Page::factory()->ofType(PageType::Contact)->make(['slug' => 'contact'])))->toBe('/contact/')
        ->and(PublicUrls::for(Project::factory()->make(['slug' => 'duo-stone'])))->toBe('/proiect/duo-stone/')
        ->and(PublicUrls::for(Post::factory()->make(['slug' => 'o-postare'])))->toBe('/blog/o-postare/')
        ->and(PublicUrls::for(ProjectCategory::factory()->make(['slug' => 'magazine'])))->toBe('/portofoliu/categorie/magazine/')
        ->and(PublicUrls::for(PostCategory::factory()->make(['slug' => 'seo'])))->toBe('/blog/categorie/seo/');
});

it('adds the slash ahead of a query or fragment, and never to the root or a file', function () {
    expect(TrailingSlash::append('/blog'))->toBe('/blog/')
        ->and(TrailingSlash::append('/blog/'))->toBe('/blog/')
        ->and(TrailingSlash::append('/blog?pagina=2#articole'))->toBe('/blog/?pagina=2#articole')
        ->and(TrailingSlash::append('https://www.webis.ro/proiect/x'))->toBe('https://www.webis.ro/proiect/x/')
        ->and(TrailingSlash::append('https://www.webis.ro'))->toBe('https://www.webis.ro')
        ->and(TrailingSlash::append('/'))->toBe('/')
        ->and(TrailingSlash::append('/sitemap.xml'))->toBe('/sitemap.xml')
        ->and(TrailingSlash::append('/wp-content/uploads/poza.jpg?v=2'))->toBe('/wp-content/uploads/poza.jpg?v=2');
});

it('gives a link written as a path its canonical form, still as a path', function () {
    expect(TrailingSlash::href('/portofoliu'))->toBe('/portofoliu/')
        ->and(TrailingSlash::href('/portofoliu?tip=magazin'))->toBe('/portofoliu/?tip=magazin')
        ->and(TrailingSlash::href('/confidentialitate'))->toBe('/confidentialitate/')
        ->and(TrailingSlash::href('/'))->toBe('/')
        ->and(TrailingSlash::href('/cerere-oferta'))->toBe('/cerere-oferta')
        ->and(TrailingSlash::href('/admin'))->toBe('/admin')
        ->and(TrailingSlash::href('#contact'))->toBe('#contact')
        ->and(TrailingSlash::href('mailto:contact@webis.ro'))->toBe('mailto:contact@webis.ro')
        ->and(TrailingSlash::href('https://smilesoft.ro'))->toBe('https://smilesoft.ro');
});

it('links one of the app\'s own permanent redirects straight to where it leads', function () {
    expect(TrailingSlash::href('/clienti'))->toBe('/portofoliu/')
        ->and(TrailingSlash::href('/clienti?tip=logo'))->toBe('/portofoliu/?tip=logo')
        ->and(TrailingSlash::href('/solutii'))->toBe('/servicii/')
        ->and(TrailingSlash::href('/proiect'))->toBe('/portofoliu/')
        // Not a redirect: a project under /proiect/.
        ->and(TrailingSlash::href('/proiect/duo-stone'))->toBe('/proiect/duo-stone/');
});
