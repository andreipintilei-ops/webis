<?php

use App\Support\Legacy\LegacyMediaIndex;
use App\Support\Legacy\LegacyUrlIndex;
use App\Support\Legacy\UrlMap;
use Illuminate\Support\Facades\File;

it('resolves old links through the reviewed URL map to their trailing-slash target', function () {
    $directory = storage_path('framework/testing/legacy-url-index');
    File::ensureDirectoryExists($directory);

    $map = new UrlMap;
    $map->add(['old_url' => 'https://www.webis.ro/contact/', 'decision' => 'skip', 'target_path' => '/contact/']);
    $map->add(['old_url' => 'https://www.webis.ro/creare-site-iasi/', 'decision' => 'redirect', 'target_path' => '/']);
    $map->add(['old_url' => 'https://www.webis.ro/wazambacasinoaustralia-org/', 'decision' => 'gone']);
    $map->add(['old_url' => 'https://www.webis.ro/carte/x/', 'decision' => '']);

    $handle = fopen("{$directory}/url-map.csv", 'w');
    $map->writeCsv($handle);
    fclose($handle);
    File::put("{$directory}/legacy-ids.json", json_encode(['21' => '/contact/']));

    $index = LegacyUrlIndex::fromDirectory($directory);

    expect($index->resolve('https://www.webis.ro/contact'))->toBe('/contact/')
        ->and($index->resolve('http://webis.ro/Contact/#formular'))->toBe('/contact/#formular')
        ->and($index->resolve('/creare-site-iasi'))->toBe('/')
        ->and($index->resolve('/?p=21'))->toBe('/contact/')
        ->and($index->resolve('/wazambacasinoaustralia-org/'))->toBeNull()
        ->and($index->resolve('/carte/x/'))->toBeNull()
        ->and($index->resolve('/nu-exista/'))->toBeNull();

    File::deleteDirectory($directory);
});

it('tells internal links from external ones', function (string $url, bool $internal) {
    expect(LegacyUrlIndex::isInternal($url))->toBe($internal);
})->with([
    ['/contact/', true],
    ['https://www.webis.ro/x/', true],
    ['http://webis.ro/x', true],
    ['//cdn.example.com/x', false],
    ['https://pmcwebis.wpcomstaging.com/dlep/', false],
    ['mailto:info@webis.ro', false],
]);

it('resolves an image by any of its sizes, only when its original is in the archive', function () {
    $index = LegacyMediaIndex::fromMediaSource([
        ['wp_id' => 7, 'file' => '2024/03/ServiciuWeb_1080x.jpg', 'in_archive' => true, 'derivatives' => [
            ['file' => '2024/03/ServiciuWeb_1080x-300x300.jpg'],
        ]],
        ['wp_id' => 8, 'file' => '2024/03/lipsa.png', 'in_archive' => false, 'derivatives' => []],
    ]);

    expect($index->assetIdForUrl('https://www.webis.ro/wp-content/uploads/2024/03/ServiciuWeb_1080x.jpg'))->toBe(7)
        ->and($index->assetIdForUrl('https://webis.ro/wp-content/uploads/2024/03/ServiciuWeb_1080x-300x300.jpg'))->toBe(7)
        ->and($index->assetIdForUrl('/wp-content/uploads/2024/03/ServiciuWeb_1080x-640x480.jpg'))->toBe(7)
        ->and($index->assetIdForUrl('https://www.webis.ro/wp-content/uploads/2024/03/lipsa.png'))->toBeNull()
        ->and($index->assetIdForUrl('https://example.com/x.jpg'))->toBeNull()
        ->and($index->assetIdForAttachment(7))->toBe(7)
        ->and($index->assetIdForAttachment(8))->toBeNull()
        ->and($index->assetIdForAttachment(9))->toBeNull();
});
