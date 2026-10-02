<?php

use App\Models\Asset;
use App\Models\Page;
use App\Models\User;
use App\Settings\BrandSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());
});

it('saves the logos and generates favicons from the chosen mark', function () {
    [$logo, $negative] = Asset::factory()->withImage(462, 81)->count(2)->create();
    $mark = Asset::factory()->withImage(300, 162)->create();

    $this->put(route('admin.settings.brand'), [
        'logo_asset_id' => $logo->id,
        'logo_negative_asset_id' => $negative->id,
        'favicon_asset_id' => $mark->id,
    ])->assertSessionHasNoErrors();

    $favicons = app(BrandSettings::class)->favicons;

    expect(array_keys($favicons))->toBe(['icon32', 'icon96', 'apple']);

    foreach (['icon32' => 32, 'icon96' => 96, 'apple' => 180] as $name => $size) {
        Storage::disk('public')->assertExists($favicons[$name]);
        expect(getimagesizefromstring((string) Storage::disk('public')->get($favicons[$name]))[0])->toBe($size);
    }
});

it('clears the favicons when none is chosen', function () {
    $mark = Asset::factory()->withImage(200, 200)->create();

    $this->put(route('admin.settings.brand'), ['favicon_asset_id' => $mark->id]);
    $this->put(route('admin.settings.brand'), ['favicon_asset_id' => null])->assertSessionHasNoErrors();

    expect(app(BrandSettings::class)->favicons)->toBe([])
        ->and(Storage::disk('public')->allFiles('brand'))->toBe([]);
});

it('protects brand images from deletion', function () {
    $logo = Asset::factory()->create();

    $this->put(route('admin.settings.brand'), ['logo_asset_id' => $logo->id]);

    $this->deleteJson(route('admin.media.destroy', $logo))->assertUnprocessable();
});

it('shows the logos and the favicons on the public site', function () {
    [$logo, $negative] = Asset::factory()->withImage(462, 81)->count(2)->create();
    $mark = Asset::factory()->withImage(200, 200)->create();

    $this->put(route('admin.settings.brand'), [
        'logo_asset_id' => $logo->id,
        'logo_negative_asset_id' => $negative->id,
        'favicon_asset_id' => $mark->id,
    ]);

    expect($this->get('/')->assertOk()->getContent())
        ->toContain('src="'.$logo->file()?->getUrl().'"')
        ->toContain('width="462" height="81"')
        ->toContain('rel="apple-touch-icon"')
        ->toContain('sizes="96x96"');

    // The negative logo is for dark first screens: a page opening on an image hero.
    $hero = Asset::factory()->withImage()->create();
    Page::factory()->create([
        'slug' => 'despre',
        'blocks' => [['id' => 'h', 'type' => 'hero', 'v' => 1, 'data' => ['layout' => 'centered', 'heading' => 'Despre', 'image_asset_id' => $hero->id]]],
    ]);

    expect($this->get('/despre')->assertOk()->getContent())
        ->toContain('src="'.$negative->file()?->getUrl().'"');
});

it('falls back to the site name without a logo', function () {
    $this->get('/')->assertOk()->assertSee('site-nav__logo', escape: false);
});

it('shares the square app icon with the admin', function () {
    $icon = Asset::factory()->withImage(193, 193)->create();

    $this->put(route('admin.settings.brand'), ['icon_asset_id' => $icon->id])->assertSessionHasNoErrors();

    $this->get(route('admin.dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('appIcon', $icon->file()?->getUrl()));
});
