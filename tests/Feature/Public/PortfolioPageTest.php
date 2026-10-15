<?php

use App\Enums\Product;
use App\Enums\ProjectType;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('moves the client work to the old /portofoliu address, still "Clienți" in the menu', function () {
    $this->get('/clienti')->assertStatus(301)->assertRedirect('/portofoliu');

    preg_match('#<header class="site-nav.*?</header>#s', $this->get('/blog')->getContent(), $bar);

    expect($bar[0])->toContain('href="/portofoliu"')->toContain('Clienți')
        ->and($bar[0])->not->toContain('href="/clienti"');
});

it('shows only published client projects, each linking to /proiect/{slug}', function () {
    $client = Client::factory()->create(['name' => 'Bijuterii SRL']);
    Project::factory()->create(['title' => 'Zyanya', 'slug' => 'zyanya', 'type' => ProjectType::Shop, 'client_id' => $client->id, 'url' => 'https://www.zyanya.ro/']);
    Project::factory()->draft()->create(['title' => 'Ciornă', 'type' => ProjectType::Shop]);

    $html = $this->get('/portofoliu')->assertOk()->getContent();
    preg_match('#<div class="work-list".*?</ul>#s', $html, $list);

    // A row: the client, what it was, its site.
    expect($html)->toContain('<title>Portofoliu | Webis</title>')
        ->and($list[0])
        ->toContain('href="'.route('projects.show', 'zyanya').'" class="work-list__row"')
        ->toContain('Bijuterii SRL')
        ->toContain('Magazin online')
        ->toContain('zyanya.ro')
        ->not->toContain('Ciornă')
        // No featured project: no stacking cards.
        ->and($html)->not->toContain('data-product-stack');
});

it('leads with up to three featured projects as stacking cards', function () {
    Project::factory()->featured()->create(['title' => 'Little Tokyo', 'type' => ProjectType::Shop, 'summary' => 'Site pentru un restaurant japonez.']);
    Project::factory()->create(['title' => 'Obișnuit', 'type' => ProjectType::Shop]);

    $html = $this->get('/portofoliu')->assertOk()->getContent();
    preg_match('#<section id="proiecte-selectate".*?</section>#s', $html, $featured);

    expect($featured[0])
        ->toContain('data-product-stack')
        ->toContain('Little Tokyo')
        ->toContain('Site pentru un restaurant japonez.')
        ->not->toContain('Obișnuit');
});

it('filters by type, at its own address, in place, and ignores an unknown one', function () {
    Project::factory()->create(['title' => 'Magazinul A', 'type' => ProjectType::Shop]);
    Project::factory()->create(['title' => 'Siteul B', 'type' => ProjectType::Presentation]);
    Project::factory()->create(['title' => 'Logoul C', 'type' => ProjectType::Logo]);

    $shops = $this->get('/portofoliu?tip=magazin')->assertOk()->getContent();
    // Each row as shown or hidden, by its type.
    $row = fn (string $html, string $type) => preg_match('#<li data-reveal-child data-work-type="'.$type.'"\s*(hidden)?\s*>#', $html, $match) ? isset($match[1]) : null;

    // Every row is there, the other types hidden: the script filters in place.
    expect($row($shops, 'magazin'))->toBeFalse()
        ->and($row($shops, 'prezentare'))->toBeTrue()
        ->and($row($shops, 'logo'))->toBeTrue()
        ->and($shops)->toContain('<title>Magazine online · Portofoliu | Webis</title>')
        ->toMatch('#href="[^"]*/portofoliu\?tip=magazin\#proiecte" class="portfolio-filter__link" data-work-filter="magazin" data-no-transition\s*aria-current="page"\s*>Magazine online#')
        // Every filter is there, none through the page transition.
        ->and(preg_match_all('#data-work-filter="[a-z]*" data-no-transition#', $shops))->toBe(5)
        ->and($shops)->toContain('>Toate</a>')
        ->toContain('>Software la comandă</a>')
        ->toContain('>Site-uri de prezentare</a>')
        ->toContain('>Identitate vizuală</a>');

    $all = $this->get('/portofoliu?tip=altceva')->assertOk()->getContent();

    expect($row($all, 'magazin'))->toBeFalse()
        ->and($row($all, 'prezentare'))->toBeFalse()
        ->and($row($all, 'logo'))->toBeFalse();
});

it('never shows Webis\'s own products as client work, only a line to Produse SaaS', function () {
    $html = $this->get('/portofoliu')->assertOk()->getContent();
    preg_match('#<main.*</main>#s', $html, $main);

    expect($main[0])
        ->not->toContain('SmileSoft')
        ->not->toContain('RIMS')
        ->not->toContain('Bugetare participativă')
        ->not->toContain('Confirmo')
        ->toContain('href="/produse"')
        // The closing call, in the page's own words.
        ->and($html)->toContain('Ai un proiect asemănător?');
});

it('shows the clients\' logos as "Am lucrat cu", with their names as alt text', function () {
    Storage::fake('public');
    $logo = fn () => Asset::factory()->withImage(200, 80)->create();

    Client::factory()->university()->create(['name' => 'Universitatea X', 'logo_asset_id' => $logo()->id]);
    Client::factory()->create(['name' => 'Ascuns', 'logo_asset_id' => $logo()->id, 'show_in_logos' => false]);

    expect($this->get('/portofoliu')->assertOk()->getContent())
        ->toContain('Am lucrat cu')
        ->toContain('alt="Universitatea X"')
        ->not->toContain('alt="Ascuns"');
});

it('shows a project at /proiect/{slug}: what was needed, what was built and with what', function () {
    Project::factory()->create([
        'title' => 'Duo Stone',
        'slug' => 'duo-stone',
        'type' => ProjectType::Presentation,
        'url' => 'https://duostone.ro/',
        'summary' => 'Site de prezentare pentru marmură.',
        'need' => 'Un site care să arate piatra.',
        'built' => 'Un site de prezentare cu galerie.',
        'stack' => ['Laravel', 'Tailwind CSS'],
    ]);
    Project::factory()->create(['title' => 'Alt site', 'type' => ProjectType::Presentation]);
    Project::factory()->create(['title' => 'Un magazin', 'type' => ProjectType::Shop]);

    $html = $this->get('/proiect/duo-stone')->assertOk()->getContent();

    expect($html)
        ->toContain('Duo Stone')
        ->toContain('Site de prezentare')
        ->toContain('Un site care să arate piatra.')
        ->toContain('Un site de prezentare cu galerie.')
        ->toContain('Laravel')
        ->toContain('href="https://duostone.ro/"')
        // More of the same type; not other types.
        ->toContain('Alt site')
        ->not->toContain('Un magazin');
});

it('answers 404 for a draft project, and 301 from a slug it used to have', function () {
    Project::factory()->draft()->create(['slug' => 'ciorna', 'type' => ProjectType::Logo]);
    $this->get('/proiect/ciorna')->assertNotFound();

    $project = Project::factory()->create(['slug' => 'nume-vechi', 'type' => ProjectType::Logo]);
    $project->update(['slug' => 'nume-nou']);

    $this->get('/proiect/nume-vechi')->assertStatus(301)->assertRedirect(route('projects.show', 'nume-nou'));
});

it('shows up to three related client projects of the service page\'s type, and none elsewhere', function () {
    Page::factory()->create(['title' => 'Web și e-commerce', 'slug' => 'web']);
    Page::factory()->create(['title' => 'Software la comandă', 'slug' => 'software-la-comanda']);
    Page::factory()->create(['title' => 'Despre', 'slug' => 'despre']);

    Project::factory()->create(['title' => 'Magazinul unu', 'type' => ProjectType::Shop]);
    Project::factory()->create(['title' => 'Siteul doi', 'type' => ProjectType::Presentation]);
    Project::factory()->create(['title' => 'Siteul trei', 'type' => ProjectType::Presentation]);
    Project::factory()->create(['title' => 'Siteul patru', 'type' => ProjectType::Presentation]);
    Project::factory()->create(['title' => 'Platforma cinci', 'type' => ProjectType::Software]);

    $web = $this->get('/web')->assertOk()->getContent();
    $software = $this->get('/software-la-comanda')->assertOk()->getContent();

    expect(substr_count($web, 'class="work-card"'))->toBe(3)
        ->and($web)->not->toContain('Platforma cinci')
        ->and($software)->toContain('Platforma cinci')->not->toContain('Siteul doi')
        ->and(substr_count($software, 'class="work-card"'))->toBe(1)
        ->and($this->get('/despre')->getContent())->not->toContain('class="work-card"');
});

it('fills a product page\'s "Folosit de" strip from the clients that use it, as logos only', function () {
    Storage::fake('public');
    $logo = fn () => Asset::factory()->withImage(200, 80)->create();

    Client::factory()->create(['name' => 'Universitatea X', 'logo_asset_id' => $logo()->id, 'products' => [Product::Rims]]);
    Client::factory()->create(['name' => 'Firma Y', 'logo_asset_id' => $logo()->id, 'products' => [Product::Confirmo]]);

    $strip = Blade::render('<x-site.used-by product="rims" />');

    expect($strip)->toContain('Folosit de')->toContain('alt="Universitatea X"')
        ->not->toContain('alt="Firma Y"')
        ->not->toContain('work-card')
        // Nobody uses it yet: nothing at all.
        ->and(trim(Blade::render('<x-site.used-by product="smilesoft" />')))->toBe('');
});

it('links the footer\'s "Portofoliu" group to the type filters', function () {
    expect($this->get('/blog')->getContent())
        ->toContain('href="/portofoliu?tip=magazin"')
        ->toContain('href="/portofoliu?tip=prezentare"')
        ->not->toContain('/clienti#');
});
