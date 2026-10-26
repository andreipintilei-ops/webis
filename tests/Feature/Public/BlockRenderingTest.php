<?php

use App\Enums\PageType;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Page;
use App\Models\Project;
use App\Settings\CompanySettings;
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
        // A link typed as "/contact", in its canonical form.
        ->toContain('href="/contact/"')
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
        'slug' => 'pagina-test',
        'blocks' => [
            ['id' => 'a', 'type' => 'hero', 'v' => 1, 'data' => ['layout' => 'split', 'heading' => 'Despre']],
            ['id' => 'b', 'type' => 'hero', 'v' => 1, 'data' => ['layout' => 'split', 'heading' => 'Al doilea', 'image_asset_id' => $image->id]],
        ],
    ]);

    expect($this->get('/pagina-test')->assertOk()->getContent())
        ->toContain('loading="lazy"')
        ->not->toContain('fetchpriority="high"');
});

it('skips block types that have no template yet', function () {
    homeWithHero(['layout' => 'split', 'heading' => 'Acasă']);
    $page = Page::sole();
    $page->update(['blocks' => [...$page->blocks, ['id' => 't', 'type' => 'testimonials', 'v' => 1, 'data' => ['source' => 'manual']]]]);

    $this->get('/')->assertOk();
});

it('renders nothing for an FAQ without questions', function () {
    homeWithHero(['layout' => 'split', 'heading' => 'Acasă']);
    $page = Page::sole();
    $page->update(['blocks' => [...$page->blocks, ['id' => 'f', 'type' => 'faq', 'v' => 1, 'data' => ['items' => []]]]]);

    expect($this->get('/')->assertOk()->getContent())->not->toContain('web-faq__item');
});

it('keeps a centred hero centred and dark on white without an image', function () {
    homeWithHero(['layout' => 'centered', 'eyebrow' => 'Pentru toți', 'heading' => 'Titlu centrat']);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('justify-center px-6 text-center')
        ->toContain('text-neutral-600')
        ->toContain('Titlu centrat')
        ->not->toContain('site-nav--dark')
        // No image in the hero (the reviews further down have their logos).
        ->and(strstr($html, '<article id="software-la-comanda"', true))->not->toContain('<img');
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

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('<canvas data-soffit')
        ->toContain('data-palette="blue"')
        // The round menu button turns white over it.
        ->toContain('data-nav-tone="dark"')
        ->toContain('bg-[var(--site-menu)]')
        ->toContain('site-nav site-nav--dark')
        ->toContain('class="pill btn-dark"')
        ->not->toContain('rel="preload" as="image"')
        // No image in the hero (the reviews further down have their logos).
        ->and(strstr($html, '<article id="software-la-comanda"', true))->not->toContain('<img');
});

it('renders the violet gradient, in the old site\'s purples, on its indigo base', function () {
    homeWithHero(['layout' => 'centered', 'background' => 'gradient-violet', 'heading' => 'Titlu']);

    expect($this->get('/')->assertOk()->getContent())
        ->toContain('<canvas data-soffit data-palette="violet"')
        ->toContain('bg-[#1f2868]')
        ->not->toContain('bg-[var(--site-menu)]')
        ->toContain('site-nav site-nav--dark');
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
    Client::factory()->create(['name' => 'Doar în grilă', 'show_in_logos' => true, 'show_in_marquee' => false, 'logo_asset_id' => Asset::factory()->withImage()->create()->id]);

    homeWithHero(['layout' => 'centered', 'background' => 'gradient', 'logos' => true, 'heading' => 'Titlu']);

    preg_match('/<section id="hero-1".*?<\/section>/s', $this->get('/')->assertOk()->getContent(), $hero);

    expect($hero[0])
        ->toContain('logo-marquee logo-marquee--dark')
        // Announced once; the repeats that make the loop are hidden.
        ->and(substr_count($hero[0], 'alt="Universitatea X"'))->toBe(1)
        ->and(substr_count($hero[0], 'aria-hidden="true"'))->toBeGreaterThan(1)
        ->and($hero[0])->not->toContain('Ascuns')
        ->not->toContain('Fără logo')
        ->not->toContain('Doar în grilă');
});

it('leaves the marquee out when the hero does not ask for it', function () {
    Client::factory()->create(['show_in_logos' => true, 'logo_asset_id' => Asset::factory()->withImage()->create()->id]);

    homeWithHero(['layout' => 'centered', 'heading' => 'Titlu']);

    expect($this->get('/')->assertOk()->getContent())->not->toContain('logo-marquee');
});

it('gives no hero a scroll parallax; a dark opening one has the next section rising over it', function () {
    Page::factory()->create([
        'slug' => 'pagina-test',
        'blocks' => [
            ['id' => 'a', 'type' => 'hero', 'v' => 1, 'data' => ['layout' => 'split', 'background' => 'gradient-violet', 'heading' => 'Primul']],
            ['id' => 'b', 'type' => 'hero', 'v' => 1, 'data' => ['layout' => 'centered', 'heading' => 'Al doilea']],
        ],
    ]);

    $html = $this->get('/pagina-test')->assertOk()->getContent();

    expect($html)->not->toContain('data-speed')
        // Only the opening one, dark: css/site/hero-parallax.css rounds what follows.
        ->and(substr_count($html, 'hero-sheet'))->toBe(1);
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
        ->toMatch('/<div class="overflow-hidden fixed inset-0 z-0 bg-\[var\(--site-menu\)\]"\s+data-palette="blue"\s+data-intro-backdrop\s*>\s*<div class="absolute inset-0" data-intro-media>\s*<canvas data-soffit/')
        ->and(strpos($html, 'data-intro-backdrop'))->toBeLessThan(strpos($html, '<div id="smooth-wrapper"'));

    // A plain hero on white has no backdrop to grow, so no intro.
    Page::query()->forceDelete();
    homeWithHero(['layout' => 'centered', 'heading' => 'Titlu']);

    expect($this->get('/')->getContent())->not->toMatch('/\sdata-intro\s*>/')->not->toContain('data-intro-backdrop');
});

it('keeps the intro to the home page: the same hero elsewhere is a plain one', function () {
    Page::factory()->create(['slug' => 'mentenanta', 'blocks' => [
        ['id' => 'h', 'type' => 'hero', 'v' => 1, 'data' => ['layout' => 'centered', 'background' => 'gradient-violet', 'heading' => 'Mentenanță']],
    ]]);

    $html = $this->get('/mentenanta')->assertOk()->getContent();
    preg_match('/<section id="h".*?<\/section>/s', $html, $hero);

    expect($html)->not->toContain('data-intro-backdrop')
        ->and($hero[0])->not->toMatch('/\sdata-intro[\s>]/')
        // Its gradient inside it, not fixed behind the page.
        ->toContain('data-soffit')
        ->toContain('hero-sheet');
});

it('shows the Google rating above the client logos, linked to the reviews, and nothing without one', function () {
    $company = app(CompanySettings::class);
    $company->google_rating = 4.7;
    $company->google_review_count = 38;
    $company->google_reviews_url = 'https://search.google.com/local/reviews?placeid=abc';
    $company->save();

    homeWithHero(['layout' => 'centered', 'background' => 'gradient', 'logos' => true, 'heading' => 'Titlu']);

    expect($this->get('/')->assertOk()->getContent())
        // The hero's is left-aligned on a phone, right-aligned from 768px.
        ->toContain('class="google-rating google-rating--dark google-rating--hero"')
        ->toContain('href="https://search.google.com/local/reviews?placeid=abc" target="_blank" rel="noopener"')
        ->toContain('Evaluare Google: 4,7 din 5, din 38 de recenzii')
        ->toContain('Google Reviews · 38 de recenzii')
        ->toContain('style="--fill: 94%"');

    $company->google_rating = null;
    $company->save();

    expect($this->get('/')->getContent())->not->toContain('google-rating');
});

it('sets a split hero on the gradient, in its own section, white, when one is chosen', function () {
    homeWithHero(['layout' => 'split', 'background' => 'gradient-violet', 'heading' => 'Titlu']);

    $html = $this->get('/')->assertOk()->getContent();
    // Before the footer (which always has its own gradient).
    $page = strstr($html, '<footer', true);

    expect($page)->toContain('<canvas data-soffit data-palette="violet"')
        // Not the centred hero's fixed backdrop and intro.
        ->not->toContain('data-intro-backdrop')
        ->and($html)->toContain('site-nav--dark');
});

it('leaves a split hero without a gradient on the page\'s white', function () {
    homeWithHero(['layout' => 'split', 'background' => 'none', 'heading' => 'Titlu']);

    $html = $this->get('/')->assertOk()->getContent();

    expect(strstr($html, '<footer', true))->not->toContain('data-soffit')
        ->and($html)->not->toContain('site-nav--dark');
});

it('renders the primary button as a pill and the secondary as an arrow link', function () {
    homeWithHero([
        'layout' => 'centered',
        'heading' => 'Titlu',
        'primary_cta' => ['label' => 'Discutați proiectul', 'url' => '/contact'],
        'secondary_cta' => ['label' => 'Vedeți ce am construit', 'url' => '/portofoliu'],
    ]);

    $html = $this->get('/')->assertOk()->getContent();
    preg_match_all('/<a href="\/contact\/" class="pill btn-light">.*?<\/a>/s', $html, $pills);

    // The label, rolling letter by letter (components/ui/roll); the top
    // bar's button comes first.
    expect(strip_tags(implode('', $pills[0])))->toContain('Discutați proiectul')
        ->and($html)
        ->toMatch('/<a href="\/portofoliu\/" class="arrow-link btn-light">/')
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
    Page::factory()->create([
        'slug' => 'avantaje',
        'blocks' => [['id' => 'f', 'type' => 'features', 'v' => 1, 'data' => [
            'heading' => 'Ce dezvoltăm',
            'columns' => 3,
            'items' => [
                ['title' => 'Software la comandă', 'text' => 'Sisteme construite.', 'note' => 'Când procesul vostru nu seamănă cu al nimănui.', 'link' => ['label' => 'Află mai multe', 'url' => '/solutii']],
                ['title' => 'Fără link', 'text' => 'Doar text.'],
            ],
        ]]],
    ]);

    preg_match('/<section id="f".*?<\/section>/s', $this->get('/avantaje')->assertOk()->getContent(), $section);

    expect($section[0])
        ->toMatch('/<h2[^>]*>Ce dezvoltăm<\/h2>/')
        ->toContain('md:grid-cols-3')
        ->toContain('<h3')
        ->toContain('Când procesul vostru nu seamănă cu al nimănui.')
        ->toMatch('/<a href="\/servicii\/" class="arrow-link btn-light">.*?Află mai multe/s')
        ->and(substr_count($section[0], 'class="arrow-link btn-'))->toBe(1);
});

it('renders the services as illustrated cards when the block asks for them, rising over the hero', function () {
    Page::factory()->create([
        'slug' => 'pagina-test',
        'blocks' => [['id' => 'f', 'type' => 'features', 'v' => 1, 'data' => [
            'layout' => 'cards',
            'columns' => 3,
            'items' => [['title' => 'Software la comandă']],
        ]]],
    ]);

    $html = $this->get('/pagina-test')->assertOk()->getContent();
    preg_match('/<section id="f".*?<\/section>/s', $html, $section);

    expect($section[0])
        ->toContain('class="svc-cards"')
        // The title, a chip between its words (hidden from screen readers);
        // on phones the chip comes under the title instead.
        ->toMatch('/<h2[^>]*>Serviciile noastre, <span class="title-pill max-md:hidden" aria-hidden="true" data-title-pill>.*?<\/span><\/span>\s*<br> de la site la sistemul de gestiune.<\/h2>\s*<span class="title-pill svc-cards__pill-below md:hidden"/s')
        ->and(substr_count($section[0], '<article class="svc-card">'))->toBe(3)
        ->and($section[0])->toContain('Aplicații construite pe felul în care lucrezi: gestiune, aprobări, rapoarte și integrări.')
        // The illustrations: one inline SVG each, nothing loaded.
        ->and(substr_count($section[0], '<svg class="il"'))->toBe(3)
        // The SaaS one: a product in the cloud, no product named.
        ->and($section[0])->toContain('M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z')
        ->not->toContain('>SmileSoft</text>')
        ->not->toContain('<img')
        ->not->toContain('md:grid-cols-3')
        ->and($html)->toContain('data-hero-leave="overlap"')
        // The projects follow, as rows that open (the first open).
        ->toContain('<section id="proiecte-detalii" class="project-accordion"')
        ->and(substr_count($html, 'data-accordion-item'))->toBe(3)
        ->and(preg_match_all('/<details[^>]*\sopen\s/', $html))->toBe(1)
        ->and(strip_tags($html))->toContain('Toți clienții')
        // …and, to compare, as a walkthrough.
        ->and($html)->toContain('<section id="proiecte-pasi" class="project-steps"')
        ->and(substr_count($html, 'data-step-row'))->toBe(3)
        // Then our products, as full-screen cards stacking, SmileSoft first.
        ->and(substr_count($html, 'data-product-card'))->toBe(3)
        ->and(strpos($html, '>SmileSoft</h3>'))->toBeLessThan(strpos($html, 'product-card--2'));
});

it('keeps the columns without the cards layout, the hero leaving as everywhere', function () {
    Page::factory()->create([
        'slug' => 'avantaje',
        'blocks' => [['id' => 'f', 'type' => 'features', 'v' => 1, 'data' => ['columns' => 3, 'items' => [['title' => 'Card']]]]],
    ]);

    expect($this->get('/avantaje')->assertOk()->getContent())
        ->toContain('md:grid-cols-3')
        ->not->toContain('svc-cards')
        ->not->toContain('project-accordion')
        // The next section slides over the hero's backdrop on every page.
        ->toContain('data-hero-leave="overlap"');
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

// ---- The page builder's content blocks -------------------------------------

/**
 * A page of the given blocks at /pagina-test, rendered; with `$id`, just
 * that block's section.
 *
 * @param  list<array<string, mixed>>  $blocks
 */
function renderBlocks(array $blocks, ?string $id = null): string
{
    Page::factory()->create(['slug' => 'pagina-test', 'blocks' => $blocks]);

    $html = test()->get('/pagina-test')->assertOk()->getContent();

    if ($id === null) {
        return $html;
    }

    preg_match('/<section id="'.preg_quote($id, '/').'".*?<\/section>/s', $html, $section);

    return $section[0] ?? '';
}

/**
 * @param  array<string, mixed>  $data
 * @return array<string, mixed>
 */
function contentBlock(string $id, string $type, array $data): array
{
    return ['id' => $id, 'type' => $type, 'v' => 1, 'data' => $data];
}

/**
 * @param  list<array<string, mixed>>  $content
 * @return array<string, mixed>
 */
function tiptap(array $content): array
{
    return ['type' => 'doc', 'content' => $content];
}

/**
 * @return array<string, mixed>
 */
function tiptapText(string $text): array
{
    return ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]];
}

it('renders a rich-text block from its saved HTML, its images filled in', function () {
    $image = Asset::factory()->withImage(1600, 900)->create(['alt' => 'Echipa la lucru']);

    $section = renderBlocks([contentBlock('rt', 'rich_text', ['content' => tiptap([
        ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Cum lucrăm']]],
        ['type' => 'paragraph', 'content' => [
            ['type' => 'text', 'text' => 'Un site '],
            ['type' => 'text', 'text' => 'rapid', 'marks' => [['type' => 'bold']]],
            ['type' => 'text', 'text' => ' și <sigur>.'],
        ]],
        ['type' => 'assetImage', 'attrs' => ['assetId' => $image->id, 'caption' => 'Biroul din Iași']],
    ])])], 'rt');

    expect($section)
        ->toContain('class="chapter chapter--light block-rich-text block-rich-text--centred"')
        ->toContain('class="rich-text block-rich-text__body"')
        ->toMatch('/<h2 id="[^"]+">Cum lucrăm<\/h2>/')
        ->toContain('<p>Un site <strong>rapid</strong> și &lt;sigur&gt;.</p>')
        // The placeholder became the image, its caption kept.
        ->toMatch('/<figure class="rt-image">\s*<img\s+src="[^"]+"[^>]*alt="Echipa la lucru"/')
        ->toContain('width="1600" height="900"')
        ->toContain('<figcaption>Biroul din Iași</figcaption>')
        ->not->toContain('data-asset-id');
});

/**
 * A rich-text document with these h2s, a paragraph under each.
 *
 * @param  list<string>  $headings
 * @return array<string, mixed>
 */
function tiptapSections(array $headings): array
{
    $content = [];

    foreach ($headings as $heading) {
        $content[] = ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => $heading]]];
        $content[] = ['type' => 'heading', 'attrs' => ['level' => 3], 'content' => [['type' => 'text', 'text' => "Detalii {$heading}"]]];
        $content[] = tiptapText("Despre {$heading}.");
    }

    return tiptap($content);
}

it('gives rich text with three h2s or more a contents rail linking to its headings', function () {
    $section = renderBlocks([contentBlock('rt', 'rich_text', [
        'content' => tiptapSections(['De ce noi', 'Ce primești', 'Cât costă & cât durează', 'După lansare']),
    ])], 'rt');

    preg_match_all('/<h2 id="([^"]+)">/', $section, $ids);
    preg_match_all('/<a href="#([^"]+)" class="block-rich-text__link" data-toc-link>([^<]+)<\/a>/', $section, $links);

    expect($section)
        ->toContain('block-rich-text--rail')
        ->not->toContain('block-rich-text--centred')
        ->toContain('data-toc')
        ->toMatch('/<nav class="block-rich-text__rail" aria-labelledby="rt-cuprins">/')
        ->toMatch('/<p id="rt-cuprins" class="section-head__number block-rich-text__label">Cuprins<\/p>/')
        ->and($ids[1])->toBe(['de-ce-noi', 'ce-primesti', 'cat-costa-cat-dureaza', 'dupa-lansare'])
        // One link per h2 (none for the h3s), each to its heading.
        ->and($links[1])->toBe($ids[1])
        ->and($links[2])->toBe(['De ce noi', 'Ce primești', 'Cât costă &amp; cât durează', 'După lansare']);
});

it('leaves out the rail with fewer than three h2s and centres the text', function () {
    $section = renderBlocks([contentBlock('rt', 'rich_text', [
        'content' => tiptapSections(['De ce noi', 'Ce primești']),
    ])], 'rt');

    expect($section)
        ->toContain('class="chapter chapter--light block-rich-text block-rich-text--centred"')
        ->not->toContain('block-rich-text--rail')
        ->not->toContain('Cuprins')
        ->not->toContain('data-toc');
});

it('lists in each rail only its own block\'s headings', function () {
    $html = renderBlocks([
        contentBlock('a', 'rich_text', ['content' => tiptapSections(['Analiză', 'Design', 'Dezvoltare'])]),
        contentBlock('b', 'rich_text', ['content' => tiptapSections(['Lansare', 'Instruire', 'Mentenanță', 'SEO'])]),
    ]);

    $links = function (string $id) use ($html): array {
        preg_match('/<section id="'.$id.'".*?<\/section>/s', $html, $section);
        preg_match_all('/data-toc-link>([^<]+)<\/a>/', $section[0], $links);

        return $links[1];
    };

    expect($links('a'))->toBe(['Analiză', 'Design', 'Dezvoltare'])
        ->and($links('b'))->toBe(['Lansare', 'Instruire', 'Mentenanță', 'SEO']);
});

it('renders nothing for an empty rich-text block', function () {
    expect(renderBlocks([contentBlock('rt', 'rich_text', ['content' => tiptap([])])]))
        ->not->toContain('block-rich-text');
});

it('gives the FAQ\'s own intro, when it has one, as the line under its title', function () {
    $section = renderBlocks([contentBlock('faq', 'faq', [
        'intro' => 'Cele mai frecvente intrebari legate de SEO',
        'items' => [['question' => 'Ce este SEO?', 'answer' => 'Optimizare.']],
    ])], 'faq');

    expect($section)
        ->toContain('<p class="web-split__line">Cele mai frecvente intrebari legate de SEO</p>')
        ->not->toContain('Nu găsești răspunsul?');
});

it('renders the FAQ as /web does: the title held on the left, rows that open on the right, answers escaped', function () {
    $section = renderBlocks([contentBlock('faq', 'faq', [
        'heading' => 'Întrebări frecvente',
        'items' => [
            ['question' => 'Cât durează un site?', 'answer' => 'Între 3 și 6 săptămâni.'],
            ['question' => 'Pot scrie <b>HTML</b>?', 'answer' => "Nu: <script>alert(1)</script>\n\nRăspunsurile sunt text."],
        ],
    ])], 'faq');

    expect($section)
        ->toContain('aria-labelledby="faq-title"')
        ->toContain('<div class="web-split" data-split>')
        ->toMatch('/<h2 id="faq-title" class="chapter__title">Întrebări frecvente<\/h2>/')
        // No intro: the new pages' own line, and the way to write to us.
        ->toContain('<p class="web-split__line">Nu găsești răspunsul? Scrie-ne și îți răspundem în aceeași zi lucrătoare.</p>')
        ->toContain('href="/contact/"')
        ->toContain('class="web-faq__list web-split__body" data-split-body')
        ->and(substr_count($section, '<details class="web-faq__item" data-accordion-item'))->toBe(2)
        ->and($section)
        ->toContain('<span>Cât durează un site?</span>')
        ->toContain('<p>Între 3 și 6 săptămâni.</p>')
        ->toContain('Pot scrie &lt;b&gt;HTML&lt;/b&gt;?')
        ->toContain('<p>Nu: &lt;script&gt;alert(1)&lt;/script&gt;</p>')
        ->toContain('<p>Răspunsurile sunt text.</p>')
        ->not->toContain('<script>alert(1)');
});

it('heads the blocks in the text face, centred, the display face kept to the hero', function () {
    $html = renderBlocks([
        contentBlock('h', 'hero', ['layout' => 'split', 'heading' => 'Titlu']),
        contentBlock('a', 'faq', ['heading' => 'Întrebări', 'items' => [['question' => 'Q?', 'answer' => 'A.']]]),
        contentBlock('f', 'features', ['heading' => 'Ce primești', 'intro' => 'Pe scurt.', 'items' => [['title' => 'Viteză']]]),
        contentBlock('c', 'stats', ['heading' => 'În cifre', 'items' => [['value' => '250+', 'label' => 'site-uri']]]),
    ]);

    preg_match_all('/<h2 id="[^"]+" class="chead__title">([^<]+)<\/h2>/', $html, $titles);

    // The FAQ heads its own split section (the title on the left), as /web.
    expect($titles[1])->toBe(['Ce primești', 'În cifre'])
        ->and($html)->toContain('<h2 id="a-title" class="chapter__title">Întrebări</h2>')
        ->and($html)->toContain('<p class="chead__intro">Pe scurt.</p>')
        // No numbers over the headings any more.
        ->not->toMatch('/class="section-head__number" aria-hidden="true">\d+</');
});

it('sizes the hero title to its length: smaller beside the image, a step down when long', function () {
    $sizeOf = function (string $layout, string $heading): string {
        Page::query()->forceDelete();
        preg_match('/<h1 class="[^"]*(text-\[clamp\([^\]]+\)\])/', renderBlocks([contentBlock('h', 'hero', ['layout' => $layout, 'heading' => $heading])]), $match);

        return $match[1] ?? '';
    };

    expect($sizeOf('split', 'Creare site mobila'))->toBe('text-[clamp(2.5rem,4.6vw,4.5rem)]')
        ->and($sizeOf('split', 'Creare site hotel / pensiune'))->toBe('text-[clamp(2.25rem,3.8vw,3.75rem)]')
        ->and($sizeOf('split', 'Creare site primarie, consiliu local, institutii publice'))->toBe('text-[clamp(2rem,3.2vw,3.25rem)]')
        ->and($sizeOf('centered', "Web design, software\nși mai puțin haos."))->toBe('text-[clamp(2.75rem,7vw,6.5rem)]')
        ->and($sizeOf('centered', 'Oferim servicii de mentenanta a site-ului WordPress'))->toBe('text-[clamp(2.5rem,5vw,4.75rem)]');
});

it('renders pricing tiers as cards, the highlighted one on the indigo, a tier with a button a link', function () {
    $section = renderBlocks([contentBlock('p', 'pricing_tiers', [
        'heading' => 'Pachete',
        'intro' => 'Prețuri orientative.',
        'tiers' => [
            ['name' => 'Start', 'price' => 'de la 2.500 lei', 'period' => 'o singură dată', 'description' => 'Pentru început.', 'features' => ['5 pagini', 'SEO de bază'], 'highlighted' => true, 'cta' => ['label' => 'Alege Start', 'url' => '/contact']],
            ['name' => 'Pro', 'price' => 'la cerere', 'features' => ['Magazin online']],
        ],
        'note' => 'Prețurile nu includ TVA.',
    ])], 'p');

    expect($section)
        ->toContain('<p class="chead__intro">Prețuri orientative.</p>')
        ->toContain('block-pricing__list--2')
        ->toMatch('/<a\s+href="\/contact\/"\s+class="post-card block-tier block-tier--featured">/')
        ->toContain('<h3 class="block-tier__name">Start</h3>')
        ->toContain('<span class="post-card__category">Recomandat</span>')
        ->toContain('<span class="block-tier__amount">de la 2.500 lei</span>')
        ->toContain('<span class="block-tier__period">o singură dată</span>')
        ->toContain('<span>SEO de bază</span>')
        ->toContain('<span class="post-card__foot">Alege Start</span>')
        // Without a button: no link, no notch, no arrow.
        ->toMatch('/<div\s+class="post-card block-tier block-tier--plain">/')
        ->and(substr_count($section, 'post-card__button'))->toBe(1)
        ->and($section)->toContain('<p class="block-pricing__note">Prețurile nu includ TVA.</p>');
});

it('renders stats on the indigo, the number with its label', function () {
    $section = renderBlocks([contentBlock('s', 'stats', ['items' => [
        ['value' => '250+', 'label' => 'site-uri livrate', 'note' => 'din 2016'],
        ['value' => '98%', 'label' => 'clienți mulțumiți'],
    ]])], 's');

    expect($section)
        ->toContain('class="chapter chapter--dark block-stats"')
        ->toContain('data-nav-tone="dark"')
        // No heading asked for: none drawn.
        ->not->toContain('section-head')
        ->toContain('<dt class="block-stats__label">site-uri livrate</dt>')
        ->toContain('<dd class="block-stats__value" data-count-up>250+</dd>')
        ->toContain('<dd class="block-stats__note">din 2016</dd>')
        ->toContain('<dd class="block-stats__value" data-count-up>98%</dd>');
});

it('fills a share\'s hairline that far, and only a share\'s', function () {
    $section = renderBlocks([contentBlock('s', 'stats', ['items' => [
        ['value' => '78%', 'label' => 'WordPress'],
        ['value' => '2,5 %', 'label' => 'Drupal'],
        ['value' => '250+', 'label' => 'site-uri livrate'],
    ]])], 's');

    expect($section)
        ->toMatch('/class="block-stats__item block-stats__item--share"\s+style="--share: 78%"/')
        ->toMatch('/class="block-stats__item block-stats__item--share"\s+style="--share: 2.5%"/')
        ->and(substr_count($section, '--share:'))->toBe(2);
});

it('renders a stats block\'s passage under the figures, in the same section', function () {
    $section = renderBlocks([contentBlock('s', 'stats', [
        'items' => [['value' => '78%', 'label' => 'WordPress']],
        'body' => tiptap([tiptapText('Cele mai multe site-uri infectate folosesc WordPress.')]),
    ])], 's');

    expect($section)
        ->toMatch('/<\/dl>\s*<div class="rich-text block-stats__body"/')
        ->toContain('<p>Cele mai multe site-uri infectate folosesc WordPress.</p>');
});

it('renders a stats block without a passage as the figures alone', function () {
    expect(renderBlocks([contentBlock('s', 'stats', ['items' => [['value' => '250+', 'label' => 'site-uri']]])], 's'))
        ->not->toContain('block-stats__body');
});

it('sets a light stats block on the lavender, without the dark nav tone', function () {
    $section = renderBlocks([contentBlock('s', 'stats', [
        'heading' => 'În cifre',
        'items' => [['value' => '78%', 'label' => 'WordPress']],
        'tone' => 'light',
    ])], 's');

    expect($section)
        ->toContain('class="chapter chapter--light block-stats"')
        ->not->toContain('data-nav-tone="dark"')
        ->not->toContain('section-head--dark');
});

it('renders one image per gallery item, captions under them, missing assets left out', function () {
    $images = Asset::factory()->withImage()->count(3)->create();
    $gone = Asset::factory()->create();

    $section = renderBlocks([contentBlock('g', 'gallery', [
        'heading' => 'Galerie',
        'columns' => 4,
        'images' => [
            ['asset_id' => $images[0]->id, 'caption' => 'Recepția'],
            ['asset_id' => $images[1]->id],
            ['asset_id' => $images[2]->id, 'caption' => 'Sala de ședințe'],
            ['asset_id' => $gone->id, 'caption' => 'Fără fișier'],
        ],
    ])], 'g');

    expect(substr_count($section, '<img'))->toBe(3)
        ->and(substr_count($section, '<figure class="block-gallery__item">'))->toBe(3)
        ->and($section)
        ->toContain('block-gallery__grid--4')
        ->toContain('<figcaption class="block-gallery__caption">Recepția</figcaption>')
        ->toContain('Sala de ședințe')
        ->toContain('loading="lazy"')
        ->not->toContain('Fără fișier');
});

it('renders image and text: the image on the side asked for, the passage as rich text, the button an arrow link', function () {
    $image = Asset::factory()->withImage()->create(['alt' => 'Un laptop']);

    $section = renderBlocks([contentBlock('it', 'image_text', [
        'heading' => 'Design propriu',
        'content' => tiptap([tiptapText('Fiecare site are concept grafic făcut pentru el.')]),
        'image_asset_id' => $image->id,
        'image_position' => 'left',
        'cta' => ['label' => 'Vezi proiectele', 'url' => '/portofoliu'],
    ])], 'it');

    expect($section)
        ->toContain('block-image-text--left')
        ->toContain('alt="Un laptop"')
        ->toMatch('/<h2 id="it-title" class="block-image-text__title">Design propriu<\/h2>/')
        ->toContain('<div class="rich-text block-image-text__body">')
        ->toContain('<p>Fiecare site are concept grafic făcut pentru el.</p>')
        ->toMatch('/<a href="\/portofoliu\/" class="arrow-link btn-light">.*?Vezi proiectele/s');
});

it('renders a CTA banner with the white pill and an arrow link, in the variant asked for', function () {
    $section = renderBlocks([contentBlock('c', 'cta_banner', [
        'heading' => 'Hai să discutăm',
        'text' => 'Răspundem în aceeași zi.',
        'primary_cta' => ['label' => 'Sună-ne', 'url' => 'tel:+40700000000'],
        'secondary_cta' => ['label' => 'Vezi portofoliul', 'url' => '/portofoliu'],
        'variant' => 'accent',
    ])], 'c');

    expect($section)
        ->toContain('class="block-cta__panel block-cta__panel--accent"')
        ->toMatch('/<h2 id="c-title" class="block-cta__title">Hai să discutăm<\/h2>/')
        ->toContain('<p class="block-cta__line">Răspundem în aceeași zi.</p>')
        ->toContain('<a href="tel:+40700000000" class="pill btn-dark">')
        ->toMatch('/<a href="\/portofoliu\/" class="arrow-link btn-dark">.*?Vezi portofoliul/s');
});

it('renders the quote form as the site\'s contact form, posting to cerere-oferta', function () {
    $html = renderBlocks([contentBlock('q', 'quote_form', [
        'heading' => 'Cere o ofertă',
        'intro' => 'Spune-ne ce ai nevoie.',
        'show_contact_details' => false,
    ])]);

    preg_match('/<section id="contact".*?<\/section>/s', $html, $section);

    expect(route('contact.store'))->toEndWith('/cerere-oferta')
        ->and($section[0])
        ->toContain('class="contact block-quote-form"')
        ->toContain('data-block="q"')
        ->toContain('<form method="POST" action="'.route('contact.store').'"')
        ->toContain('data-contact-form')
        ->toMatch('/<h2 id="contact-title" class="contact__title">Cere o ofertă<\/h2>/')
        ->toContain('<p class="contact__intro">Spune-ne ce ai nevoie.</p>')
        ->not->toContain('contact__details')
        ->and(substr_count($html, 'data-contact-form'))->toBe(1);
});

it('lets the quote form stand in for the footer\'s contact call', function () {
    $html = renderBlocks([
        contentBlock('h', 'hero', ['layout' => 'split', 'heading' => 'Titlu']),
        contentBlock('q', 'quote_form', ['show_contact_details' => true]),
    ]);

    expect($html)
        ->toContain('data-contact-form')
        ->toContain('<footer class="site-footer')
        ->not->toContain('site-footer__cta');
});

it('keeps the footer\'s contact call on pages without a quote form', function () {
    expect(renderBlocks([contentBlock('h', 'hero', ['layout' => 'split', 'heading' => 'Titlu'])]))
        ->toContain('site-footer__cta')
        ->toContain('Ai un proiect în minte?')
        ->not->toContain('data-contact-form');

    // The home page too.
    homeWithHero(['layout' => 'split', 'heading' => 'Acasă']);

    expect($this->get('/')->assertOk()->getContent())->toContain('site-footer__cta');
});

it('drops the footer\'s contact call on the home page when it has a quote form', function () {
    homeWithHero(['layout' => 'split', 'heading' => 'Acasă']);
    $page = Page::sole();
    $page->update(['blocks' => [...$page->blocks, contentBlock('q', 'quote_form', ['show_contact_details' => true])]]);

    expect($this->get('/')->assertOk()->getContent())
        ->toContain('data-contact-form')
        ->not->toContain('site-footer__cta');
});

it('keeps a project page\'s own footer call', function () {
    Project::factory()->create(['slug' => 'zyanya']);

    expect($this->get(route('projects.show', 'zyanya'))->assertOk()->getContent())
        ->toContain('site-footer__cta')
        ->toContain('Ai un proiect asemănător?');
});

it('keeps the contact details beside the quote form by default', function () {
    $company = app(CompanySettings::class);
    $company->email = 'office@webis.ro';
    $company->save();

    expect(renderBlocks([contentBlock('q', 'quote_form', ['show_contact_details' => true])]))
        ->toContain('class="contact__details"')
        ->toContain('mailto:office@webis.ro');
});

it('renders a video as a lite embed: a poster and a link, no player until it is played', function () {
    $section = renderBlocks([contentBlock('v', 'video', [
        'title' => 'Prezentare Webis',
        'url' => 'https://youtu.be/dQw4w9WgXcQ',
        'caption' => 'Două minute despre cum lucrăm.',
    ])], 'v');

    expect($section)
        ->not->toContain('<iframe')
        ->toContain('data-lite-video="dQw4w9WgXcQ"')
        ->toContain('data-title="Prezentare Webis"')
        ->toContain('src="https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg"')
        ->toContain('<a href="https://www.youtube.com/watch?v=dQw4w9WgXcQ" class="block-video__play" data-lite-video-play aria-label="Redă videoclipul: Prezentare Webis">')
        ->toContain('<span class="block-video__title">Prezentare Webis</span>')
        ->toContain('Două minute despre cum lucrăm.');
});

it('uses the video\'s own poster when it has one', function () {
    $poster = Asset::factory()->withImage(1920, 1080)->create();

    expect(renderBlocks([contentBlock('v', 'video', [
        'title' => 'Prezentare',
        'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'poster_asset_id' => $poster->id,
    ])], 'v'))
        ->toContain('width="1920" height="1080"')
        ->toContain('class="block-video__poster"')
        ->not->toContain('i.ytimg.com');
});
