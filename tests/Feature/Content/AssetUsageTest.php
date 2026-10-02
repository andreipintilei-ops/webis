<?php

use App\Exceptions\AssetInUseException;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('records where an asset is used, per field', function () {
    $image = Asset::factory()->create();

    $page = Page::factory()->create([
        'hero_asset_id' => $image->id,
        'seo' => ['og_image_asset_id' => $image->id],
    ]);

    expect($image->usages()->orderBy('field')->pluck('field')->all())->toBe(['hero', 'seo.og_image'])
        ->and($image->usages()->first()?->usable_type)->toBe('page')
        ->and($image->usages()->first()?->usable?->is($page))->toBeTrue();
});

it('moves usages when a reference changes', function () {
    [$old, $new] = Asset::factory()->count(2)->create();
    $post = Post::factory()->create(['cover_asset_id' => $old->id]);

    $post->update(['cover_asset_id' => $new->id]);

    expect($old->usages()->count())->toBe(0)
        ->and($new->usages()->count())->toBe(1);
});

it('refuses to delete an asset that is still in use', function () {
    $logo = Asset::factory()->create();
    Client::factory()->create(['logo_asset_id' => $logo->id]);

    expect(fn () => $logo->delete())->toThrow(AssetInUseException::class);
    expect($logo->fresh())->not->toBeNull();
});

it('lets a trashed item keep protecting its images until force deleted', function () {
    $image = Asset::factory()->create();
    $page = Page::factory()->create(['hero_asset_id' => $image->id]);

    $page->delete();
    expect($image->usages()->count())->toBe(1);

    $page->forceDelete();
    expect($image->usages()->count())->toBe(0);

    $image->delete();
    expect($image->exists)->toBeFalse();
});

it('stores the uploaded file and generates WebP versions', function () {
    Storage::fake('public');

    $asset = Asset::factory()->withImage(1600, 900)->create();
    $file = $asset->file();

    expect($file)->not->toBeNull()
        ->and($file?->hasGeneratedConversion('thumb'))->toBeTrue()
        ->and($file?->hasGeneratedConversion('web'))->toBeTrue()
        ->and($file?->getPath('web'))->toEndWith('.webp');
});
