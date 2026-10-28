<?php

use App\Models\Asset;
use App\Models\Client;
use App\Models\User;
use App\Settings\SeoSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());
});

function svgUpload(string $svg, string $name = 'logo.svg'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $svg)->mimeType('image/svg+xml');
}

it('lists assets on the media page', function () {
    Asset::factory()->withImage()->count(2)->create();

    $this->get(route('admin.media.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/media/Index')
            ->has('assets.data', 2)
            ->has('assets.data.0', fn (Assert $asset) => $asset
                ->hasAll(['id', 'title', 'alt', 'url', 'thumb_url', 'width', 'height', 'usages_count'])
                ->etc()));
});

it('uploads an image, titles it from the file name and records its size', function () {
    $response = $this->postJson(route('admin.media.store'), [
        'file' => UploadedFile::fake()->image('Birou Webis_2026.jpg', 1600, 900),
        'alt' => 'Echipa Webis la birou',
    ]);

    $response->assertCreated()
        ->assertJsonPath('asset.title', 'Birou Webis 2026')
        ->assertJsonPath('asset.alt', 'Echipa Webis la birou')
        ->assertJsonPath('asset.width', 1600)
        ->assertJsonPath('asset.height', 900)
        ->assertJsonPath('asset.file_name', 'birou-webis-2026.jpg');

    $asset = Asset::sole();
    expect($asset->file())->not->toBeNull()
        ->and($asset->uploaded_by)->toBe(auth()->id());
});

it('strips scripts and event handlers from uploaded SVGs', function () {
    $this->postJson(route('admin.media.store'), [
        'file' => svgUpload('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 40" onload="alert(1)"><script>alert(2)</script><rect width="120" height="40"/></svg>'),
    ])->assertCreated()
        ->assertJsonPath('asset.width', 120)
        ->assertJsonPath('asset.height', 40);

    $stored = (string) file_get_contents((string) Asset::sole()->file()?->getPath());

    expect($stored)->toContain('<rect')
        ->not->toContain('<script')
        ->not->toContain('onload');
});

it('refuses files that are not images', function () {
    $this->postJson(route('admin.media.store'), [
        'file' => UploadedFile::fake()->create('oferta.pdf', 100, 'application/pdf'),
    ])->assertUnprocessable()->assertJsonValidationErrors('file');

    expect(Asset::count())->toBe(0);
});

it('edits alt text, title and caption', function () {
    $asset = Asset::factory()->withImage()->create();

    $this->patchJson(route('admin.media.update', $asset), [
        'title' => 'Hero',
        'alt' => 'Laptop cu un magazin online deschis',
        'caption' => null,
    ])->assertOk()->assertJsonPath('asset.alt', 'Laptop cu un magazin online deschis');

    expect($asset->fresh()?->alt)->toBe('Laptop cu un magazin online deschis');
});

it('replaces the file but keeps the asset everything points to', function () {
    $asset = Asset::factory()->withImage(800, 600)->create();
    $client = Client::factory()->create(['logo_asset_id' => $asset->id]);

    $this->postJson(route('admin.media.replace', $asset), [
        'file' => UploadedFile::fake()->image('nou.png', 400, 400),
    ])->assertOk()
        ->assertJsonPath('asset.id', $asset->id)
        ->assertJsonPath('asset.width', 400)
        ->assertJsonPath('asset.file_name', 'nou.png');

    expect($asset->fresh()?->getMedia(Asset::COLLECTION))->toHaveCount(1)
        ->and($client->fresh()?->logo_asset_id)->toBe($asset->id);
});

it('refuses to delete an image that is in use, and says why', function () {
    $asset = Asset::factory()->create();
    Client::factory()->create(['logo_asset_id' => $asset->id]);

    $this->deleteJson(route('admin.media.destroy', $asset))
        ->assertUnprocessable()
        ->assertJsonPath('message', fn (string $message) => str_contains($message, 'folosită'));

    expect($asset->fresh())->not->toBeNull();
});

it('treats the images chosen in the SEO settings as in use', function () {
    $asset = Asset::factory()->create();

    $settings = app(SeoSettings::class);
    $settings->logo_asset_id = $asset->id;
    $settings->save();

    $this->deleteJson(route('admin.media.destroy', $asset))->assertUnprocessable();

    $this->getJson(route('admin.media.show', $asset))
        ->assertOk()
        ->assertJsonPath('usages.0.title', 'Setări SEO: logo');
});

it('deletes an unused image together with its files', function () {
    $asset = Asset::factory()->withImage()->create();
    $path = (string) $asset->file()?->getPath();

    $this->deleteJson(route('admin.media.destroy', $asset))->assertNoContent();

    expect(Asset::count())->toBe(0)->and(file_exists($path))->toBeFalse();
});

it('searches for the picker by title, alt or file name', function () {
    Asset::factory()->create(['title' => 'Logo Primăria Iași']);
    Asset::factory()->create(['title' => 'Altceva', 'alt' => 'Echipa']);

    $this->getJson(route('admin.media.browse', ['q' => 'Primăria']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Logo Primăria Iași');
});

it('filters to images nothing uses yet', function () {
    $used = Asset::factory()->create();
    Asset::factory()->create();
    Client::factory()->create(['logo_asset_id' => $used->id]);

    $this->getJson(route('admin.media.browse', ['unused' => 1]))
        ->assertJsonCount(1, 'data');
});
