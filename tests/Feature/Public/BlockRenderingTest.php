<?php

use App\Enums\PageType;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(fn () => Storage::fake('public'));

/**
 * @param  array<string, mixed>  $data
 */
function homeWithHero(array $data): Page
{
    return Page::factory()->ofType(PageType::Home)->create([
        'slug' => 'acasa',
        'blocks' => [['id' => 'hero-1', 'type' => 'hero', 'v' => 1, 'data' => $data]],
    ]);
}

it('renders a hero over its image as the page h1, with a dark header', function () {
    $image = Asset::factory()->withImage(1600, 900)->create(['alt' => 'Spații digitale colorate']);

    homeWithHero([
        'layout' => 'centered',
        'eyebrow' => 'Pentru universități, instituții publice și companii.',
        'heading' => 'Software care înțelege organizația',
        'image_asset_id' => $image->id,
        'primary_cta' => ['label' => 'Cere o ofertă', 'url' => '/contact'],
    ]);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('Pentru universități, instituții publice și companii.')
        ->toContain('<h1')
        ->toContain('Software care înțelege organizația')
        ->toContain('alt="Spații digitale colorate"')
        ->toContain('width="1600" height="900"')
        ->toContain('fetchpriority="high"')
        // Preloaded from <head>, before the body starts.
        ->toMatch('/<link rel="preload" as="image"[^>]+>.*<\/head>/s')
        ->toContain('href="/contact"')
        ->toContain('site-nav site-nav--dark')
        ->and(substr_count($html, '<h1'))->toBe(1);
});

it('keeps the light header when the page does not open on an image', function () {
    homeWithHero(['layout' => 'split', 'heading' => 'Titlu']);

    expect($this->get('/')->assertOk()->getContent())
        ->toContain('Titlu')
        ->not->toContain('site-nav--dark');
});

it('lazy-loads images that are not the first block', function () {
    $image = Asset::factory()->withImage()->create();

    Page::factory()->create([
        'slug' => 'despre',
        'blocks' => [
            ['id' => 'a', 'type' => 'hero', 'v' => 1, 'data' => ['layout' => 'split', 'heading' => 'Despre']],
            ['id' => 'b', 'type' => 'hero', 'v' => 1, 'data' => ['layout' => 'split', 'heading' => 'Al doilea', 'image_asset_id' => $image->id]],
        ],
    ]);

    expect($this->get('/despre')->assertOk()->getContent())
        ->toContain('loading="lazy"')
        ->not->toContain('fetchpriority="high"');
});

it('skips block types that have no template yet', function () {
    homeWithHero(['layout' => 'split', 'heading' => 'Acasă']);
    $page = Page::sole();
    $page->update(['blocks' => [...$page->blocks, ['id' => 'f', 'type' => 'faq', 'v' => 1, 'data' => ['items' => []]]]]);

    $this->get('/')->assertOk();
});

it('keeps a centred hero centred and dark on white without an image', function () {
    homeWithHero(['layout' => 'centered', 'eyebrow' => 'Pentru toți', 'heading' => 'Titlu centrat']);

    expect($this->get('/')->assertOk()->getContent())
        ->toContain('justify-center px-6 text-center')
        ->toContain('text-neutral-600')
        ->toContain('Titlu centrat')
        ->not->toContain('site-nav--dark')
        ->not->toContain('<img');
});

it('renders the animated gradient behind a centred hero, in place of its image, with a dark header', function () {
    $image = Asset::factory()->withImage()->create();

    homeWithHero([
        'layout' => 'centered',
        'background' => 'gradient',
        'heading' => 'Titlu',
        'image_asset_id' => $image->id,
        'primary_cta' => ['label' => 'Discutați proiectul', 'url' => '/contact'],
    ]);

    expect($this->get('/')->assertOk()->getContent())
        ->toContain('<canvas data-soffit')
        // The round menu button turns white over it.
        ->toContain('data-nav-tone="dark"')
        ->toContain('bg-[#062334]')
        ->toContain('site-nav site-nav--dark')
        ->toContain('class="pill btn-dark"')
        ->not->toContain('<img')
        ->not->toContain('rel="preload" as="image"');
});

it('aligns a full-screen hero left, in the content column', function () {
    homeWithHero([
        'layout' => 'centered',
        'align' => 'left',
        'heading' => 'Titlu',
        'primary_cta' => ['label' => 'Hai să discutăm', 'url' => '/contact'],
    ]);

    preg_match('/<section id="hero-1".*?<\/section>/s', $this->get('/')->assertOk()->getContent(), $hero);

    expect($hero[0])
        ->toContain('px-[var(--site-gap)] text-left')
        ->not->toContain('text-center')
        ->toContain('max-w-[var(--content-max)]')
        ->not->toContain('justify-center');
});

it('runs the client-logo marquee along the bottom of the hero', function () {
    $shown = Client::factory()->create(['name' => 'Universitatea X', 'show_in_logos' => true, 'logo_asset_id' => Asset::factory()->withImage(200, 80)->create()->id]);
    Client::factory()->create(['name' => 'Ascuns', 'show_in_logos' => false, 'logo_asset_id' => Asset::factory()->withImage()->create()->id]);
    Client::factory()->create(['name' => 'Fără logo', 'show_in_logos' => true, 'logo_asset_id' => null]);

    homeWithHero(['layout' => 'centered', 'background' => 'gradient', 'logos' => true, 'heading' => 'Titlu']);

    preg_match('/<section id="hero-1".*?<\/section>/s', $this->get('/')->assertOk()->getContent(), $hero);

    expect($hero[0])
        ->toContain('logo-marquee logo-marquee--dark')
        // Announced once; the repeats that make the loop are hidden.
        ->and(substr_count($hero[0], 'alt="Universitatea X"'))->toBe(1)
        ->and(substr_count($hero[0], 'aria-hidden="true"'))->toBeGreaterThan(1)
        ->and($hero[0])->not->toContain('Ascuns')
        ->not->toContain('Fără logo');
});

it('leaves the marquee out when the hero does not ask for it', function () {
    Client::factory()->create(['show_in_logos' => true, 'logo_asset_id' => Asset::factory()->withImage()->create()->id]);

    homeWithHero(['layout' => 'centered', 'heading' => 'Titlu']);

    expect($this->get('/')->assertOk()->getContent())->not->toContain('logo-marquee');
});

it('gives only an opening full-screen hero the scroll parallax', function () {
    Page::factory()->create([
        'slug' => 'despre',
        'blocks' => [
            ['id' => 'a', 'type' => 'hero', 'v' => 1, 'data' => ['layout' => 'centered', 'heading' => 'Primul']],
            ['id' => 'b', 'type' => 'hero', 'v' => 1, 'data' => ['layout' => 'centered', 'heading' => 'Al doilea']],
        ],
    ]);

    expect(preg_match_all('/data-hero-parallax\s+data-speed="clamp\(0\.5\)"/', $this->get('/despre')->assertOk()->getContent()))->toBe(1);
});

it('keeps the line breaks typed into the hero title, escaped', function () {
    homeWithHero(['layout' => 'centered', 'heading' => "Construim software care\nînțelege <b>și</b> simplifică"]);

    preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $this->get('/')->assertOk()->getContent(), $h1);

    expect($h1[0])->toContain('max-w-none')
        ->and(preg_replace('/\s+/', ' ', trim($h1[1])))
        ->toBe('<span class="hero-line" data-intro-line><span class="hero-line__inner">Construim software care</span></span> <span class="hero-line" data-intro-line><span class="hero-line__inner">înțelege &lt;b&gt;și&lt;/b&gt; simplifică</span></span>');
});

it('marks the opening dark hero for the intro, and only that one', function () {
    homeWithHero(['layout' => 'centered', 'background' => 'gradient', 'logos' => false, 'heading' => 'Titlu', 'eyebrow' => 'Pentru toți', 'primary_cta' => ['label' => 'Hai', 'url' => '/contact']]);

    $html = $this->get('/')->assertOk()->getContent();
    preg_match('/<section id="hero-1".*?<\/section>/s', $html, $hero);

    expect($hero[0])
        ->toMatch('/\sdata-intro\s+data-backdrop-area\s*>/')
        // Its background is fixed behind the page, not inside the section.
        ->not->toContain('data-soffit')
        ->and(substr_count($hero[0], 'data-intro-fade'))->toBe(2);

    expect($html)
        ->toMatch('/<div class="overflow-hidden fixed inset-0 z-0 bg-\[#062334\]"\s+data-intro-backdrop\s*>\s*<div class="absolute inset-0" data-intro-media>\s*<canvas data-soffit/')
        ->and(strpos($html, 'data-intro-backdrop'))->toBeLessThan(strpos($html, '<div id="smooth-wrapper"'));

    // A plain hero on white has no backdrop to grow, so no intro.
    Page::query()->forceDelete();
    homeWithHero(['layout' => 'centered', 'heading' => 'Titlu']);

    expect($this->get('/')->getContent())->not->toMatch('/\sdata-intro\s*>/')->not->toContain('data-intro-backdrop');
});

it('ignores the gradient on a split hero', function () {
    homeWithHero(['layout' => 'split', 'background' => 'gradient', 'heading' => 'Titlu']);

    expect($this->get('/')->assertOk()->getContent())
        ->not->toContain('data-soffit')
        ->not->toContain('site-nav--dark');
});

it('renders the primary button as a pill and the secondary as an arrow link', function () {
    homeWithHero([
        'layout' => 'centered',
        'heading' => 'Titlu',
        'primary_cta' => ['label' => 'Discutați proiectul', 'url' => '/contact'],
        'secondary_cta' => ['label' => 'Vedeți ce am construit', 'url' => '/portofoliu'],
    ]);

    expect($this->get('/')->assertOk()->getContent())
        ->toMatch('/<a href="\/contact" class="pill btn-light">.*?<span data-text="Discutați proiectul">Discutați proiectul<\/span>/s')
        ->toMatch('/<a href="\/portofoliu" class="arrow-link btn-light">/')
        ->toContain('arrow-link__icon');
});

it('keeps the secondary button an arrow link when it is the only one', function () {
    homeWithHero(['layout' => 'centered', 'heading' => 'Titlu', 'secondary_cta' => ['label' => 'Portofoliu', 'url' => '/portofoliu']]);

    preg_match('/<section id="hero-1".*?<\/section>/s', $this->get('/')->assertOk()->getContent(), $hero);

    expect($hero[0])
        ->toContain('class="arrow-link btn-light"')
        ->not->toContain('class="pill');
});

it('renders feature cards with their note and a link at the foot', function () {
    Page::factory()->ofType(PageType::Home)->create([
        'slug' => 'acasa',
        'blocks' => [['id' => 'f', 'type' => 'features', 'v' => 1, 'data' => [
            'heading' => 'Ce dezvoltăm',
            'columns' => 3,
            'items' => [
                ['title' => 'Software la comandă', 'text' => 'Sisteme construite.', 'note' => 'Când procesul vostru nu seamănă cu al nimănui.', 'link' => ['label' => 'Află mai multe', 'url' => '/solutii']],
                ['title' => 'Fără link', 'text' => 'Doar text.'],
            ],
        ]]],
    ]);

    preg_match('/<section id="f".*?<\/section>/s', $this->get('/')->assertOk()->getContent(), $section);

    expect($section[0])
        ->toMatch('/<h2[^>]*>Ce dezvoltăm<\/h2>/')
        ->toContain('md:grid-cols-3')
        ->toContain('<h3')
        ->toContain('Când procesul vostru nu seamănă cu al nimănui.')
        ->toMatch('/<a href="\/solutii" class="arrow-link btn-light">.*?Află mai multe/s')
        ->and(substr_count($section[0], 'class="arrow-link btn-'))->toBe(1);
});

it('renders a statement: label left, words split for the reveal, paragraph and link', function () {
    Page::factory()->ofType(PageType::Home)->create([
        'slug' => 'acasa',
        'blocks' => [['id' => 's', 'type' => 'statement', 'v' => 1, 'data' => [
            'eyebrow' => 'Despre noi',
            'statement' => "Din 2016 dezvoltăm software,\npentru <organizații>.",
            'text' => 'Am început cu site-uri.',
            'link' => ['label' => 'Despre Webis', 'url' => '/despre'],
        ]]],
    ]);

    preg_match('/<section id="s".*?<\/section>/s', $this->get('/')->assertOk()->getContent(), $section);

    expect($section[0])
        ->toContain('data-statement')
        ->toMatch('/<p class="statement__eyebrow" data-statement-pin>.*?Despre noi/s')
        ->toContain('<span class="statement__word">dezvoltăm</span>')
        // Escaped, and the typed line break kept from tablet width up.
        ->toContain('<span class="statement__word">&lt;organizații&gt;.</span>')
        ->toContain('<br class="hidden lg:inline">')
        ->toContain('max-w-[65ch]')
        ->toMatch('/<a href="\/despre" class="arrow-link btn-light">.*?Despre Webis/s')
        ->and(substr_count($section[0], 'class="statement__word"'))->toBe(6);
});

it('sets a statement on the hero background only straight after a dark hero', function () {
    $statement = fn (string $id) => ['id' => $id, 'type' => 'statement', 'v' => 1, 'data' => ['statement' => 'Text', 'background' => 'hero']];
    $section = function (string $html, string $id): string {
        preg_match('/<section id="'.$id.'".*?>/s', $html, $open);

        return $open[0];
    };

    // Dark hero, then the statement: on the backdrop, white, and a window onto it.
    Page::factory()->ofType(PageType::Home)->create(['slug' => 'acasa', 'blocks' => [
        ['id' => 'h', 'type' => 'hero', 'v' => 1, 'data' => ['layout' => 'centered', 'background' => 'gradient', 'heading' => 'Titlu']],
        $statement('a'),
        ['id' => 'f', 'type' => 'features', 'v' => 1, 'data' => ['columns' => 3, 'items' => [['title' => 'Card']]]],
        // Not straight after the hero any more: white.
        $statement('b'),
    ]]);

    $html = $this->get('/')->assertOk()->getContent();

    expect($section($html, 'a'))
        ->toContain('statement--dark')
        ->toMatch('/data-on-backdrop\s+data-backdrop-area\s+data-nav-tone="dark"/')
        ->and($section($html, 'b'))->not->toContain('statement--dark')->not->toContain('data-on-backdrop');

    // After a light hero there is no background to sit on.
    Page::query()->forceDelete();
    Page::factory()->ofType(PageType::Home)->create(['slug' => 'acasa', 'blocks' => [
        ['id' => 'h', 'type' => 'hero', 'v' => 1, 'data' => ['layout' => 'centered', 'heading' => 'Titlu']],
        $statement('a'),
    ]]);

    expect($section($this->get('/')->getContent(), 'a'))->not->toContain('statement--dark');
});
