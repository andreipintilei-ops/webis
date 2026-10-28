<?php

use App\Support\Legacy\ExtraUrlSources;
use App\Support\Legacy\UrlMap;

it('keys rows by normalised path and keeps the first, recording the second', function () {
    $map = new UrlMap;

    expect($map->add(['old_url' => 'https://www.webis.ro/Contact/', 'sources' => 'yoast', 'object_type' => 'post', 'object_sub_type' => 'page', 'wp_id' => 21, 'decision' => 'skip']))->toBeTrue()
        ->and($map->add(['old_path' => '/contact', 'sources' => 'wordpress', 'object_type' => 'post', 'object_sub_type' => 'attachment', 'wp_id' => 99]))->toBeFalse();

    $row = $map->rows()['/contact'];

    expect($row)->toMatchArray(['old_path' => '/contact', 'wp_id' => '21', 'decision' => 'skip', 'sources' => 'yoast|wordpress'])
        ->and($row['notes'])->toContain('Same path as post/attachment #99')
        ->and(array_keys($row))->toBe(UrlMap::COLUMNS);
});

it('lets a WordPress object outrank Yoast\'s bare home-page row', function () {
    $map = new UrlMap;
    $map->add(['old_url' => 'https://www.webis.ro/', 'sources' => 'yoast', 'object_type' => 'home-page', 'decision' => 'skip']);
    $map->add(['old_url' => 'https://www.webis.ro/', 'sources' => 'yoast', 'object_type' => 'post', 'object_sub_type' => 'page', 'wp_id' => 1578, 'decision' => 'skip']);

    expect($map->rows()['/'])->toMatchArray(['wp_id' => '1578', 'object_sub_type' => 'page'])
        ->and($map->rows()['/']['notes'])->toContain('Same path as home-page');
});

it('merges extra sources, deduped on the normalised path', function () {
    $map = new UrlMap;
    $map->add(['old_url' => 'https://www.webis.ro/proiect/zyanya/', 'sources' => 'yoast', 'decision' => 'project', 'target_path' => '/proiect/zyanya/']);

    $skipped = $map->mergeExtra([
        'wayback' => ['http://webis.ro:80/proiect/zyanya/', 'https://www.webis.ro/pagina-stearsa/', 'https://www.webis.ro/wp-content/uploads/a.jpg'],
        'access-log' => ['/Pagina-Stearsa', '/page/3/', 'https://alt-site.example/x/', '/wp-login.php'],
    ]);

    $rows = $map->rows();

    expect(array_keys($rows))->toBe(['/proiect/zyanya', '/pagina-stearsa', '/page/3'])
        ->and($skipped)->toBe(3)
        ->and($rows['/proiect/zyanya'])->toMatchArray(['sources' => 'yoast|wayback', 'decision' => 'project', 'target_path' => '/proiect/zyanya/'])
        ->and($rows['/pagina-stearsa'])->toMatchArray(['sources' => 'wayback|access-log', 'decision' => '', 'target_path' => '', 'old_url' => 'https://www.webis.ro/pagina-stearsa/'])
        ->and($rows['/pagina-stearsa']['notes'])->toBe('Found only in wayback, access-log. Needs a manual decision')
        ->and($rows['/page/3']['notes'])->toContain('covered by the prefix row /page → /blog/');
});

it('reads extra URLs out of exports of unknown shape', function () {
    $backlinks = "\xEF\xBB\xBFReferring page URL;Target URL;Anchor\nhttps://alt.example/a;https://www.webis.ro/ce-este-seo/;seo\nhttps://alt.example/b;https://www.webis.ro/rezolutii/;x\n";
    $accessLog = "date,request,status\n2026-01-02,GET /proiect/zyanya/ HTTP/1.1,200\n2026-01-03,\"GET /tag/php/ HTTP/1.1\",404\n";
    $headless = "/contact/,12\n/blog/,4\n";
    $wayback = "# cdx\nhttp://www.webis.ro:80/\n\nhttps://webis.ro/2016/07/12/x/\n";

    expect(ExtraUrlSources::fromCsv($backlinks))->toBe(['https://www.webis.ro/ce-este-seo/', 'https://www.webis.ro/rezolutii/'])
        ->and(ExtraUrlSources::fromCsv($accessLog))->toBe(['/proiect/zyanya/', '/tag/php/'])
        ->and(ExtraUrlSources::fromCsv($headless))->toBe(['/contact/', '/blog/'])
        ->and(ExtraUrlSources::fromText($wayback))->toBe(['http://www.webis.ro:80/', 'https://webis.ro/2016/07/12/x/']);
});

it('reads only the extra source files that exist', function () {
    $directory = sys_get_temp_dir().'/legacy-extra-'.uniqid();
    mkdir($directory);
    file_put_contents("{$directory}/wayback.txt", "https://www.webis.ro/x/\n");

    try {
        expect(ExtraUrlSources::read($directory))->toBe(['wayback' => ['https://www.webis.ro/x/']]);
    } finally {
        unlink("{$directory}/wayback.txt");
        rmdir($directory);
    }
});

it('takes page paths, and the page a 404 asked for, from the analytics reports', function () {
    $report = ['data' => ['toppages' => [
        ['url' => '/proiect/zyanya/', 'sessions' => '3'],
        ['url' => '/404.html?page=/2022/02/14/api/&amp;from=https://www.google.com/', 'sessions' => '1'],
        ['url' => 'crucearosie.ro'],
    ]]];

    expect(ExtraUrlSources::fromAnalytics($report))->toBe(['/proiect/zyanya/', '/2022/02/14/api/']);
});
