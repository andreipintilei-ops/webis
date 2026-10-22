<?php

use App\Support\Legacy\UrlMap;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/*
| A miniature WordPress, built with the `wpts_` prefix in the test database
| (never `webis_legacy`), so the command runs end to end on known rows.
*/

const LEGACY_TABLES = ['options', 'yoast_indexable', 'posts', 'postmeta', 'terms', 'term_taxonomy', 'term_relationships', 'yoast_primary_term'];

beforeEach(function () {
    config(['database.connections.legacy' => [...config('database.connections.mysql'), 'prefix' => 'wpts_']]);
    DB::purge('legacy');

    $schema = Schema::connection('legacy');

    foreach (LEGACY_TABLES as $table) {
        $schema->dropIfExists($table);
    }

    $schema->create('options', function (Blueprint $table) {
        $table->id('option_id');
        $table->string('option_name');
        $table->longText('option_value');
    });
    $schema->create('yoast_indexable', function (Blueprint $table) {
        $table->id();
        $table->longText('permalink')->nullable();
        $table->bigInteger('object_id')->nullable();
        $table->string('object_type');
        $table->string('object_sub_type')->nullable();
        $table->text('title')->nullable();
        $table->text('description')->nullable();
        $table->text('breadcrumb_title')->nullable();
        $table->longText('canonical')->nullable();
        $table->string('primary_focus_keyword')->nullable();
        $table->boolean('is_robots_noindex')->nullable();
        $table->integer('incoming_link_count')->nullable();
    });
    $schema->create('posts', function (Blueprint $table) {
        $table->id('ID');
        $table->string('post_name')->default('');
        $table->text('post_title');
        $table->string('post_status');
        $table->dateTime('post_date');
        $table->longText('post_content');
        $table->string('post_type');
        $table->bigInteger('post_parent')->default(0);
        $table->string('post_mime_type')->default('');
        $table->string('guid')->default('');
    });
    $schema->create('postmeta', function (Blueprint $table) {
        $table->id('meta_id');
        $table->bigInteger('post_id');
        $table->string('meta_key');
        $table->longText('meta_value')->nullable();
    });
    $schema->create('terms', function (Blueprint $table) {
        $table->id('term_id');
        $table->string('name');
        $table->string('slug');
    });
    $schema->create('term_taxonomy', function (Blueprint $table) {
        $table->id('term_taxonomy_id');
        $table->bigInteger('term_id');
        $table->string('taxonomy');
    });
    $schema->create('term_relationships', function (Blueprint $table) {
        $table->bigInteger('object_id');
        $table->bigInteger('term_taxonomy_id');
        $table->integer('term_order')->default(0);
    });
    $schema->create('yoast_primary_term', function (Blueprint $table) {
        $table->id();
        $table->bigInteger('post_id');
        $table->bigInteger('term_id');
        $table->string('taxonomy');
    });

    $db = DB::connection('legacy');
    $site = 'https://www.webis.ro';

    $db->table('options')->insert([
        ['option_name' => 'home', 'option_value' => $site],
        ['option_name' => 'monsterinsights_report_data_overview', 'option_value' => serialize(['data' => ['toppages' => [
            ['url' => '/2025/11/13/lucky-casino/', 'sessions' => '2'],
            ['url' => '/404.html?page=/2022/02/14/api-vs-ws/&amp;from=https://www.google.com/'],
        ]]])],
    ]);

    $post = fn (int $id, string $type, string $name, string $title, string $content = '', array $extra = []): array => [
        'ID' => $id, 'post_type' => $type, 'post_name' => $name, 'post_title' => $title, 'post_status' => 'publish',
        'post_date' => '2026-04-14 12:00:00', 'post_content' => $content, 'post_parent' => 0, 'post_mime_type' => '', ...$extra,
    ];

    $db->table('posts')->insert([
        $post(10, 'post', 'api-vs-ws', 'Diferența dintre API și web service', '<p>Bună ziua, Iași!</p>'),
        $post(11, 'post', 'ghid-seo', 'Ghid SEO', '<p>SEO</p><div style="display:none"><a href="https://casino-spam.example/">casino</a></div>'),
        $post(20, 'proiect', 'zyanya', 'Zyanya', '<p><a href="https://zyanya.ro/">zyanya.ro</a></p>', ['post_date' => '2024-07-12 10:00:00']),
        $post(30, 'domenii', 'creare-site-florarie', 'Creare site florărie'),
        $post(40, 'page', 'politica-de-confidentialitate', 'Politica de confidențialitate', '<p>Copie</p>'),
        $post(41, 'page', 'contact', 'Contact', '<p>Contact</p>'),
        $post(60, 'proiect', '', 'Ciornă', '', ['post_status' => 'draft']),
        $post(70, 'carte', 'carte-1', 'Carte 1', '<p>Carte</p>'),
        $post(50, 'attachment', 'zyanya-cover', 'Zyanya cover', '', ['post_status' => 'inherit', 'post_parent' => 20, 'post_mime_type' => 'image/jpeg']),
        $post(51, 'attachment', '123', '123', '', ['post_status' => 'inherit', 'post_parent' => 10, 'post_mime_type' => 'image/png']),
    ]);

    $db->table('postmeta')->insert([
        ['post_id' => 10, 'meta_key' => '_wp_old_date', 'meta_value' => '2022-02-14'],
        ['post_id' => 10, 'meta_key' => '_wp_old_slug', 'meta_value' => 'api-vs-un-serviciu-web'],
        ['post_id' => 20, 'meta_key' => 'website_link', 'meta_value' => 'https://zyanya.ro/'],
        ['post_id' => 20, 'meta_key' => '_thumbnail_id', 'meta_value' => '50'],
        ['post_id' => 30, 'meta_key' => 'descriere_h1', 'meta_value' => '<p>Un site pentru florărie</p>'],
        ['post_id' => 40, 'meta_key' => '_elementor_data', 'meta_value' => json_encode([['elType' => 'widget', 'settings' => ['editor' => '<p>Politica</p>']]])],
        ['post_id' => 50, 'meta_key' => '_wp_attached_file', 'meta_value' => '2024/07/zyanya.jpg'],
        ['post_id' => 50, 'meta_key' => '_wp_attachment_image_alt', 'meta_value' => 'Magazin Zyanya'],
        ['post_id' => 50, 'meta_key' => '_wp_attachment_metadata', 'meta_value' => serialize(['file' => '2024/07/zyanya.jpg', 'sizes' => [
            'medium' => ['file' => 'zyanya-300x200.jpg', 'width' => 300, 'height' => 200],
            'thumbnail' => ['file' => 'zyanya-150x150.jpg', 'width' => 150, 'height' => 150],
        ]])],
        ['post_id' => 51, 'meta_key' => '_wp_attached_file', 'meta_value' => '2022/02/123.png'],
    ]);

    $db->table('terms')->insert([
        ['term_id' => 13, 'name' => 'Web development', 'slug' => 'web-development'],
        ['term_id' => 27, 'name' => 'SEO', 'slug' => 'seo'],
        ['term_id' => 1, 'name' => 'Uncategorized', 'slug' => 'uncategorized'],
        ['term_id' => 11, 'name' => 'SEO', 'slug' => 'seo'],
        ['term_id' => 15, 'name' => 'PHP', 'slug' => 'php'],
        ['term_id' => 39, 'name' => 'Magazin Online', 'slug' => 'magazin-online'],
    ]);
    $db->table('term_taxonomy')->insert([
        ['term_taxonomy_id' => 13, 'term_id' => 13, 'taxonomy' => 'category'],
        ['term_taxonomy_id' => 27, 'term_id' => 27, 'taxonomy' => 'category'],
        ['term_taxonomy_id' => 1, 'term_id' => 1, 'taxonomy' => 'category'],
        ['term_taxonomy_id' => 11, 'term_id' => 11, 'taxonomy' => 'post_tag'],
        ['term_taxonomy_id' => 15, 'term_id' => 15, 'taxonomy' => 'post_tag'],
        ['term_taxonomy_id' => 39, 'term_id' => 39, 'taxonomy' => 'categorie_proiect'],
    ]);
    $db->table('term_relationships')->insert([
        ['object_id' => 10, 'term_taxonomy_id' => 27],
        ['object_id' => 10, 'term_taxonomy_id' => 13],
        ['object_id' => 11, 'term_taxonomy_id' => 27],
        ['object_id' => 20, 'term_taxonomy_id' => 39],
    ]);
    $db->table('yoast_primary_term')->insert(['post_id' => 10, 'term_id' => 13, 'taxonomy' => 'category']);

    $index = fn (string $path, ?int $id, string $type, ?string $subType, array $extra = []): array => [
        'permalink' => str_starts_with($path, '?') ? "{$site}/{$path}" : $site.$path, 'object_id' => $id,
        'object_type' => $type, 'object_sub_type' => $subType, 'title' => null, 'description' => null,
        'is_robots_noindex' => null, ...$extra,
    ];

    $db->table('yoast_indexable')->insert([
        $index('/', null, 'home-page', null),
        $index('/api-vs-ws/', 10, 'post', 'post', ['title' => 'API vs web service %%sep%% %%sitename%%', 'description' => 'Ce diferență există?']),
        $index('/ghid-seo/', 11, 'post', 'post'),
        $index('/proiect/zyanya/', 20, 'post', 'proiect'),
        $index('/servicii/creare-site-florarie/', 30, 'post', 'domenii'),
        $index('/politica-de-confidentialitate/', 40, 'post', 'page'),
        $index('/contact/', 41, 'post', 'page'),
        $index('?post_type=proiect&p=60', 60, 'post', 'proiect'),
        $index('/carte/carte-1/', 70, 'post', 'carte'),
        $index('/web-development/', 13, 'term', 'category'),
        $index('/seo/', 27, 'term', 'category'),
        $index('/uncategorized/', 1, 'term', 'category'),
        $index('/tag/seo/', 11, 'term', 'post_tag'),
        $index('/tag/php/', 15, 'term', 'post_tag'),
        $index('/categorie-proiect/magazin-online/', 39, 'term', 'categorie_proiect'),
        $index('/proiect/', null, 'post-type-archive', 'proiect'),
    ]);

    $this->output = sys_get_temp_dir().'/legacy-inventory-'.uniqid();
    File::ensureDirectoryExists($this->output);
    File::put("{$this->output}/wayback.txt", "http://webis.ro:80/proiect/zyanya/\nhttps://www.webis.ro/pagina-stearsa/\n");

    $this->archive = "{$this->output}/public_html.zip";
    $zip = new ZipArchive;
    $zip->open($this->archive, ZipArchive::CREATE);
    $zip->addFromString('public_html/wp-content/uploads/2024/07/zyanya.jpg', 'jpg');
    $zip->addFromString('public_html/wp-content/uploads/2024/07/zyanya-300x200.jpg', 'jpg');
    $zip->close();
});

afterEach(function () {
    foreach (LEGACY_TABLES as $table) {
        Schema::connection('legacy')->dropIfExists($table);
    }

    File::deleteDirectory($this->output);
});

function readUrlMap(string $directory): array
{
    $handle = fopen("{$directory}/url-map.csv", 'r');
    $header = fgetcsv($handle, null, ',', '"', '');
    $rows = [];

    while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
        $row = array_combine($header, $row);
        $rows[$row['old_path']] = $row;
    }

    fclose($handle);

    return ['header' => $header, 'rows' => $rows];
}

it('writes the URL map with every old URL and its pre-filled decision', function () {
    $this->artisan('legacy:inventory', ['--output' => $this->output, '--archive' => $this->archive])
        ->expectsOutputToContain('Rows needing a manual decision')
        ->assertSuccessful();

    ['header' => $header, 'rows' => $rows] = readUrlMap($this->output);

    expect($header)->toBe(UrlMap::COLUMNS)
        ->and($rows['/api-vs-ws'])->toMatchArray([
            'old_url' => 'https://www.webis.ro/api-vs-ws/',
            'sources' => 'yoast',
            'object_sub_type' => 'post',
            'wp_id' => '10',
            'wp_status' => 'publish',
            'title' => 'API vs web service %%sep%% %%sitename%%',
            'meta_description' => 'Ce diferență există?',
            'published_at' => '2026-04-14 12:00:00',
            'primary_category' => 'web-development',
            'content_source' => 'classic',
            'has_elementor' => '0',
            'spam_flags' => '',
            'decision' => 'article',
            'target_path' => '/api-vs-ws/',
        ])
        ->and($rows['/api-vs-ws']['notes'])->toContain('First published 2022-02-14')
        ->and($rows['/proiect/zyanya'])->toMatchArray(['decision' => 'project', 'target_path' => '/proiect/zyanya/', 'sources' => 'yoast|wayback', 'images' => '1', 'spam_flags' => '', 'primary_category' => 'magazin-online'])
        ->and($rows['/servicii/creare-site-florarie'])->toMatchArray(['decision' => 'industry', 'target_path' => '/servicii/creare-site-florarie/', 'content_source' => 'empty'])
        ->and($rows['/servicii/creare-site-florarie']['notes'])->toContain('Pods')
        ->and($rows['/politica-de-confidentialitate'])->toMatchArray(['decision' => 'legal', 'content_source' => 'elementor', 'has_elementor' => '1'])
        ->and($rows['/contact'])->toMatchArray(['decision' => 'skip', 'target_path' => '/contact/'])
        ->and($rows['/'])->toMatchArray(['decision' => 'skip', 'target_path' => '/'])
        ->and($rows['/proiect'])->toMatchArray(['decision' => 'skip', 'target_path' => '/portofoliu/'])
        ->and($rows['/carte/carte-1']['decision'])->toBe('')
        ->and($rows['/web-development'])->toMatchArray(['decision' => 'category', 'target_path' => '/blog/categorie/web-development/', 'article_count' => '1'])
        ->and($rows['/seo'])->toMatchArray(['decision' => 'category', 'article_count' => '2'])
        ->and($rows['/uncategorized'])->toMatchArray(['decision' => 'gone', 'target_path' => ''])
        ->and($rows['/tag/seo'])->toMatchArray(['decision' => 'redirect', 'target_path' => '/blog/categorie/seo/'])
        ->and($rows['/tag/php'])->toMatchArray(['decision' => '', 'target_path' => ''])
        ->and($rows['/categorie-proiect/magazin-online'])->toMatchArray(['decision' => 'redirect', 'target_path' => '/portofoliu/'])
        ->and($rows)->not->toHaveKey('/?post_type=proiect&p=60');

    // The shapes Yoast doesn't list, and the posts' old addresses.
    expect($rows['/feed']['decision'])->toBe('gone')
        ->and($rows['/comments/feed']['decision'])->toBe('gone')
        ->and($rows['/page'])->toMatchArray(['decision' => 'redirect', 'target_path' => '/blog/'])
        ->and($rows['/author']['notes'])->toStartWith('Prefix row')
        ->and($rows['/proiect/zyanya/zyanya-cover'])->toMatchArray(['object_sub_type' => 'attachment', 'wp_id' => '50', 'decision' => 'gone'])
        ->and($rows['/api-vs-ws/attachment/123']['decision'])->toBe('gone')
        ->and($rows['/2022/02/14/api-vs-ws'])->toMatchArray(['sources' => 'wp-old-url|analytics', 'decision' => 'redirect', 'target_path' => '/api-vs-ws/'])
        ->and($rows['/api-vs-un-serviciu-web'])->toMatchArray(['decision' => 'redirect', 'target_path' => '/api-vs-ws/']);

    // Found outside WordPress: no decision, only a note.
    expect($rows['/pagina-stearsa'])->toMatchArray(['sources' => 'wayback', 'decision' => '', 'target_path' => ''])
        ->and($rows['/pagina-stearsa']['notes'])->toContain('Found only in wayback')
        ->and($rows['/2025/11/13/lucky-casino'])->toMatchArray(['sources' => 'analytics', 'decision' => '', 'spam_flags' => 'url:casino']);

    // The spam scan marks the hidden casino link on its row.
    expect($rows['/ghid-seo']['spam_flags'])
        ->toContain('hidden_css:display:none', 'outbound_link:casino-spam.example', 'spam_word:casino');
});

it('writes the id map, the media source and the spam report', function () {
    $this->artisan('legacy:inventory', ['--output' => $this->output, '--archive' => $this->archive])->assertSuccessful();

    $ids = json_decode(File::get("{$this->output}/legacy-ids.json"), true);

    expect($ids)->toBe([
        '10' => '/api-vs-ws/',
        '11' => '/ghid-seo/',
        '20' => '/proiect/zyanya/',
        '30' => '/servicii/creare-site-florarie/',
        '40' => '/politica-de-confidentialitate/',
        '41' => '/contact/',
    ]);

    $media = collect(json_decode(File::get("{$this->output}/media-source.json"), true))->keyBy('wp_id');

    expect($media)->toHaveCount(2)
        ->and($media[50])->toMatchArray([
            'url' => 'https://www.webis.ro/wp-content/uploads/2024/07/zyanya.jpg',
            'file' => '2024/07/zyanya.jpg',
            'alt' => 'Magazin Zyanya',
            'title' => 'Zyanya cover',
            'post_parent' => 20,
            'in_archive' => true,
            'derivatives_in_archive' => 1,
        ])
        ->and(array_column($media[50]['derivatives'], 'url'))->toBe([
            'https://www.webis.ro/wp-content/uploads/2024/07/zyanya-300x200.jpg',
            'https://www.webis.ro/wp-content/uploads/2024/07/zyanya-150x150.jpg',
        ])
        ->and(array_column($media[50]['derivatives'], 'in_archive'))->toBe([true, false])
        ->and($media[51])->toMatchArray(['in_archive' => false, 'derivatives_in_archive' => 0]);

    $report = json_decode(File::get("{$this->output}/spam-report.json"), true);
    $domains = collect($report['outbound_domains'])->keyBy('domain');

    expect(collect($report['hits'])->where('wp_id', 11)->pluck('type')->unique()->sort()->values()->all())
        ->toBe(['hidden_css', 'outbound_link', 'spam_word'])
        ->and(collect($report['hits'])->firstWhere('type', 'outbound_link'))->toMatchArray(['slug' => 'ghid-seo', 'field' => 'post_content'])
        ->and($domains['casino-spam.example'])->toMatchArray(['links' => 1, 'allowed' => false])
        ->and($domains['zyanya.ro'])->toMatchArray(['links' => 1, 'allowed' => true]);
});

it('leaves the archive columns empty when there is no archive', function () {
    $this->artisan('legacy:inventory', ['--output' => $this->output, '--archive' => "{$this->output}/none.zip"])
        ->expectsOutputToContain('Uploads archive: not present')
        ->assertSuccessful();

    $media = json_decode(File::get("{$this->output}/media-source.json"), true);

    expect($media[0]['in_archive'])->toBeNull()
        ->and($media[0]['derivatives_in_archive'])->toBeNull();
});
