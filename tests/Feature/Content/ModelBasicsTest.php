<?php

use App\Models\Page;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Redirect;
use App\Settings\CompanySettings;
use App\Settings\LeadSettings;
use App\Support\Seo\SeoData;
use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('reads a missing seo column as empty overrides, never null', function () {
    $page = Page::factory()->create(['seo' => null]);

    expect($page->fresh()?->seo)->toBeInstanceOf(SeoData::class)
        ->and($page->fresh()?->seo->title)->toBeNull()
        ->and($page->fresh()?->seo->noindex)->toBeFalse();
});

it('round-trips seo overrides and drops blank strings', function () {
    $page = Page::factory()->create(['seo' => [
        'title' => 'Creare site Iași',
        'description' => '   ',
        'noindex' => true,
        'og_image_asset_id' => '12',
    ]]);

    $seo = $page->fresh()?->seo;

    expect($seo?->title)->toBe('Creare site Iași')
        ->and($seo?->description)->toBeNull()
        ->and($seo?->noindex)->toBeTrue()
        ->and($seo?->ogImageAssetId)->toBe(12);
});

it('links projects to categories and to the pages that feature them', function () {
    $project = Project::factory()->create();
    $category = ProjectCategory::factory()->create();
    $page = Page::factory()->create();

    $project->categories()->attach($category);
    $page->projects()->attach($project, ['sort_order' => 2]);

    expect($category->projects()->sole()->is($project))->toBeTrue()
        ->and($project->pages()->sole()->is($page))->toBeTrue();
});

it('rejects polymorphic writes for models missing from the morph map', function () {
    $unmapped = new class extends Model
    {
        protected $table = 'pages';
    };

    expect(fn () => $unmapped->getMorphClass())->toThrow(ClassMorphViolationException::class);
});

it('normalises redirect sources to one canonical path', function (string $input) {
    $redirect = Redirect::factory()->create(['source_path' => $input]);

    expect($redirect->source_path)->toBe('/proiect/magazin-online');
})->with([
    'plain' => '/proiect/magazin-online',
    'trailing slash' => '/proiect/magazin-online/',
    'full URL with query' => 'https://www.webis.ro/Proiect/Magazin-Online/?utm_source=x',
    'no leading slash' => 'proiect/magazin-online',
]);

it('loads the starting settings', function () {
    expect(app(CompanySettings::class)->legal_name)->toBe('Webis SRL')
        ->and(app(CompanySettings::class)->locality)->toBe('Iași')
        ->and(app(LeadSettings::class)->notification_emails)->toBe([]);
});
