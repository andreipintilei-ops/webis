<?php

use App\Support\Legacy\Import\ImportMediaResolver;
use App\Support\Legacy\Import\ImportUrlResolver;
use App\Support\Legacy\Import\MediaMap;
use App\Support\Legacy\Import\ReviewedMap;

it('resolves old upload URLs and attachment ids to real assets through the media map', function () {
    $site = 'https://www.webis.ro/wp-content/uploads/2024/07/';
    $map = new MediaMap([$site.'zyanya.jpg' => 7, $site.'zyanya-300x200.jpg' => 7]);
    $resolver = new ImportMediaResolver($map, [['wp_id' => 50, 'url' => $site.'zyanya.jpg'], ['wp_id' => 51, 'url' => $site.'lipsa.jpg']]);

    expect($resolver->assetIdForUrl($site.'zyanya-300x200.jpg'))->toBe(7)
        // Another host, http, case: still the same file.
        ->and($resolver->assetIdForUrl('http://webis.ro/wp-content/uploads/2024/07/Zyanya.jpg'))->toBe(7)
        // An Elementor crop WordPress never recorded names its original.
        ->and($resolver->assetIdForUrl($site.'zyanya-600x400.jpg'))->toBe(7)
        ->and($resolver->assetIdForUrl('https://example.com/zyanya.jpg'))->toBeNull()
        ->and($resolver->assetIdForAttachment(50))->toBe(7)
        ->and($resolver->assetIdForAttachment(51))->toBeNull()
        ->and($resolver->assetIdForAttachment(99))->toBeNull();
});

it('resolves links through the reviewed map to final targets, in the slash form', function () {
    $resolver = ImportUrlResolver::fromMap(ReviewedMap::read(base_path('tests/Fixtures/legacy/url-map-reviewed.csv')));

    expect($resolver->resolve('https://www.webis.ro/gazduire/'))->toBe('/blog/categorie/web-development/')
        ->and($resolver->resolve('/12705-2'))->toBe('/care-sunt-tipurile-de-gazduire/')
        ->and($resolver->resolve('https://webis.ro/ghid-seo/#capitol'))->toBe('/ghid-seo/#capitol')
        ->and($resolver->resolve('/proiect/zyanya-2/'))->toBe('/proiect/zyanya/')
        ->and($resolver->resolve('/?p=12'))->toBe('/care-sunt-tipurile-de-gazduire/')
        // A post's own row, not its old addresses' rows.
        ->and($resolver->resolve('/?p=20'))->toBe('/proiect/zyanya/')
        ->and($resolver->resolve('/uncategorized/'))->toBeNull()
        ->and($resolver->resolve('/nu-exista/'))->toBeNull();
});
