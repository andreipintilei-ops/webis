<?php

use App\Enums\PageType;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    $this->folder = storage_path('framework/testing/covers-'.uniqid());
    File::ensureDirectoryExists($this->folder);
});

afterEach(function () {
    File::deleteDirectory($this->folder);
});

/**
 * A 1536×1024 PNG: an opaque violet one, or one whose edges are fully
 * transparent around an opaque middle.
 */
function coverPng(string $path, bool $transparent = false): string
{
    $image = imagecreatetruecolor(1536, 1024);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    imagefill($image, 0, 0, $transparent ? imagecolorallocatealpha($image, 0, 0, 0, 127) : imagecolorallocate($image, 119, 90, 252));
    imagefilledrectangle($image, 568, 312, 968, 712, imagecolorallocate($image, 255, 255, 255));
    imagepng($image, $path);

    return $path;
}

function article(string $slug): Page
{
    return Page::factory()->ofType(PageType::Article)->create(['slug' => $slug, 'title' => 'Articol '.$slug]);
}

it('matches files to articles by slug: exactly, with a -1/-v2 suffix, any case and extension; not one with no article', function () {
    $exact = article('ghid-seo');
    $suffixed = article('magazin-online');
    $upper = article('site-de-prezentare');
    coverPng($this->folder.'/ghid-seo.png');
    coverPng($this->folder.'/magazin-online-v2.png');
    // A JPEG, named in capitals.
    imagejpeg(imagecreatefrompng(coverPng($this->folder.'/tmp.png')), $this->folder.'/SITE-DE-PREZENTARE.jpeg');
    File::delete($this->folder.'/tmp.png');
    coverPng($this->folder.'/nu-exista.png');
    File::put($this->folder.'/alt.csv', "slug,alt\nghid-seo,\"Un ghid, pe scurt\"\n");

    $this->artisan('blog:import-covers', ['folder' => $this->folder])
        ->expectsOutputToContain('Imported 3, skipped 0.')
        ->expectsOutputToContain('Files matching no article: 1')
        ->expectsOutputToContain('nu-exista.png')
        // Two of them have no alt.csv line.
        ->expectsOutputToContain('Articles with a cover but no alt text: 2')
        ->assertSuccessful();

    expect($exact->fresh()->hasMedia(Page::COVER))->toBeTrue()
        ->and($exact->fresh()->cover_alt)->toBe('Un ghid, pe scurt')
        ->and($suffixed->fresh()->hasMedia(Page::COVER))->toBeTrue()
        ->and($upper->fresh()->hasMedia(Page::COVER))->toBeTrue();
});

it('writes nothing on a dry run, and lists what it would do', function () {
    $page = article('ghid-seo');
    article('fara-coperta');
    coverPng($this->folder.'/ghid-seo.png');

    $this->artisan('blog:import-covers', ['folder' => $this->folder, '--dry-run' => true])
        ->expectsOutputToContain('Would import 1, skipped 0.')
        ->expectsOutputToContain('Articles still without a cover: 1')
        ->expectsOutputToContain('fara-coperta')
        ->assertSuccessful();

    expect($page->fresh()->hasMedia(Page::COVER))->toBeFalse()
        ->and(Storage::disk('public')->allFiles())->toBe([])
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

it('keeps a cover an article already has, unless --force', function () {
    $page = article('ghid-seo');
    coverPng($this->folder.'/ghid-seo.png');
    $this->artisan('blog:import-covers', ['folder' => $this->folder])->assertSuccessful();
    $first = $page->fresh()->getFirstMedia(Page::COVER)->id;

    $this->artisan('blog:import-covers', ['folder' => $this->folder])
        ->expectsOutputToContain('Imported 0, skipped 1.')
        ->assertSuccessful();
    expect($page->fresh()->getFirstMedia(Page::COVER)->id)->toBe($first);

    $this->artisan('blog:import-covers', ['folder' => $this->folder, '--force' => true])
        ->expectsOutputToContain('Imported 1, skipped 0.')
        ->assertSuccessful();
    expect($page->fresh()->getMedia(Page::COVER))->toHaveCount(1)
        ->and($page->fresh()->getFirstMedia(Page::COVER)->id)->not->toBe($first);
});

it('keeps the original privately and makes the WebP sizes — the middle 16:9, or a cut-out whole — and a 1200×630 JPEG to share', function () {
    $solid = article('opaca');
    $clear = article('transparenta');
    coverPng($this->folder.'/opaca.png');
    coverPng($this->folder.'/transparenta.png', transparent: true);
    $this->artisan('blog:import-covers', ['folder' => $this->folder])->assertSuccessful();

    $media = $solid->fresh()->getFirstMedia(Page::COVER);
    $cutout = $clear->fresh()->getFirstMedia(Page::COVER);

    expect($media->disk)->toBe('local')
        ->and($media->conversions_disk)->toBe('public')
        ->and(file_exists($media->getPath()))->toBeTrue()
        ->and($media->getCustomProperty('transparent'))->toBeFalse()
        ->and($cutout->getCustomProperty('transparent'))->toBeTrue();

    foreach ([1536 => [864, 1024], 1200 => [675, 800], 768 => [432, 512]] as $width => [$cropped, $whole]) {
        expect(getimagesize($media->getPath('cover-'.$width)))->toMatchArray([0 => $width, 1 => $cropped, 'mime' => 'image/webp']);

        $file = $cutout->getPath('cover-'.$width);
        expect(getimagesize($file))->toMatchArray([0 => $width, 1 => $whole, 'mime' => 'image/webp']);
        // The transparency kept: its corner shows through.
        expect((imagecolorat(imagecreatefromwebp($file), 2, 2) >> 24) & 0x7F)->toBeGreaterThan(100);
    }

    expect(getimagesize($media->getPath('og')))->toMatchArray([0 => 1200, 1 => 630, 'mime' => 'image/jpeg'])
        ->and(getimagesize($cutout->getPath('og')))->toMatchArray([0 => 1200, 1 => 630, 'mime' => 'image/jpeg']);
});

it('fits a cut-out whole in its frame, at its own proportions', function () {
    article('transparenta');
    coverPng($this->folder.'/transparenta.png', transparent: true);
    $this->artisan('blog:import-covers', ['folder' => $this->folder])->assertSuccessful();

    expect($this->get('/transparenta/')->assertOk()->getContent())
        ->toMatch('#<figure class="article-cover is-cutout">\s*<img [^>]*width="1536" height="1024"#');
});

it('flattens a transparent cover\'s share image onto the hero\'s indigo, and leaves an opaque one\'s colour', function () {
    $clear = article('transparenta');
    $solid = article('opaca');
    coverPng($this->folder.'/transparenta.png', transparent: true);
    coverPng($this->folder.'/opaca.png');
    $this->artisan('blog:import-covers', ['folder' => $this->folder])->assertSuccessful();

    $corner = function (Page $page): array {
        $rgb = imagecolorat(imagecreatefromjpeg($page->fresh()->getFirstMedia(Page::COVER)->getPath('og')), 600, 2);

        return [($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF];
    };

    [$r, $g, $b] = $corner($clear);
    // Not black (what a JPEG makes of nothing): the indigo, blue the most.
    expect($b)->toBeGreaterThan(80)->and($b)->toBeGreaterThan($r)->and($b)->toBeGreaterThan($g);

    [$r, $g, $b] = $corner($solid);
    expect($r)->toBeGreaterThan(100)->and($g)->toBeGreaterThan(70)->and($b)->toBeGreaterThan(220);
});

it('shows the cover on the article, the listing and the home page, with its alt text, size and loading', function () {
    $page = article('ghid-seo');
    coverPng($this->folder.'/ghid-seo.png');
    File::put($this->folder.'/alt.csv', "ghid-seo,Un telefon cu un calendar\n");
    $this->artisan('blog:import-covers', ['folder' => $this->folder])->assertSuccessful();

    $article = $this->get('/ghid-seo/')->assertOk()->getContent();
    $blog = $this->get('/blog/')->assertOk()->getContent();
    $home = $this->get('/')->assertOk()->getContent();
    preg_match('#<figure class="article-cover">\s*<img ([^>]+)>#', $article, $hero);
    preg_match('#<span class="post-card__cover">\s*<img ([^>]+)>#', $blog, $card);
    preg_match('#"@type":"BlogPosting".*?"image":"([^"]+)"#', $article, $ld);

    expect($hero[1])
        ->toContain('alt="Un telefon cu un calendar"')
        ->toContain('width="1536" height="864"')
        ->toContain('fetchpriority="high"')
        ->not->toContain('loading="lazy"')
        ->toMatch('#srcset="[^"]*cover-768\.webp 768w, [^"]*cover-1200\.webp 1200w, [^"]*cover-1536\.webp 1536w"#')
        ->and($card[1])->toContain('loading="lazy"')->toContain('alt="Un telefon cu un calendar"')
        ->and($home)->toContain('class="post-card__cover"')
        ->and($article)->toMatch('#<meta property="og:image" content="[^"]+-og\.jpg">#')
        ->and(stripslashes($ld[1]))->toEndWith('-og.jpg')->toStartWith('http');
});

it('renders an article without a cover as before: no frame, no empty box', function () {
    article('fara-coperta');

    $this->get('/fara-coperta/')->assertOk()->assertDontSee('article-cover', escape: false)->assertDontSee('page-hero--cover', escape: false);
    $this->get('/blog/')->assertOk()->assertDontSee('post-card__cover', escape: false);
    $this->get('/')->assertOk()->assertDontSee('post-card__cover', escape: false);
});

it('uploads a cover from the article\'s editor, and will not save an article with a cover but no alt text', function () {
    $this->actingAs(User::factory()->create());
    $page = article('ghid-seo');

    $this->post(route('admin.pages.cover.store', $page->id), [
        'cover' => UploadedFile::fake()->createWithContent('copertă.png', file_get_contents(coverPng($this->folder.'/upload.png'))),
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($page->fresh()->hasMedia(Page::COVER))->toBeTrue();

    $payload = fn (?string $alt) => [
        'type' => 'article', 'title' => $page->title, 'slug' => $page->slug, 'blocks' => [],
        'status' => 'published', 'published_at' => now()->format('Y-m-d\TH:i'), 'seo' => [], 'cover_alt' => $alt,
    ];

    $this->put(route('admin.pages.update', $page->id), $payload(null))->assertSessionHasErrors('cover_alt');
    $this->put(route('admin.pages.update', $page->id), $payload('Un telefon cu un calendar'))->assertSessionHasNoErrors();

    expect($page->fresh()->cover_alt)->toBe('Un telefon cu un calendar');

    // Too small for the sizes it is shown at.
    $this->post(route('admin.pages.cover.store', $page->id), [
        'cover' => UploadedFile::fake()->image('mica.png', 800, 600),
    ])->assertSessionHasErrors('cover');

    $this->delete(route('admin.pages.cover.destroy', $page->id))->assertRedirect();
    expect($page->fresh()->hasMedia(Page::COVER))->toBeFalse();
});
