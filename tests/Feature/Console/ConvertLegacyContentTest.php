<?php

use App\Support\Legacy\UrlMap;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/*
| A miniature WordPress (posts and postmeta, `wpts_` prefix) in the test
| database — never `webis_legacy` — holding one post of each converted type,
| built from the real fixtures.
*/

beforeEach(function () {
    config(['database.connections.legacy' => [...config('database.connections.mysql'), 'prefix' => 'wpts_']]);
    DB::purge('legacy');

    $schema = Schema::connection('legacy');
    $schema->dropIfExists('posts');
    $schema->dropIfExists('postmeta');

    $schema->create('posts', function (Blueprint $table) {
        $table->id('ID');
        $table->string('post_name')->default('');
        $table->text('post_title');
        $table->string('post_status');
        $table->longText('post_content');
        $table->string('post_type');
    });
    $schema->create('postmeta', function (Blueprint $table) {
        $table->id('meta_id');
        $table->bigInteger('post_id');
        $table->string('meta_key');
        $table->longText('meta_value')->nullable();
    });

    $db = DB::connection('legacy');
    $fixtures = base_path('tests/Fixtures/legacy');
    $post = fn (int $id, string $type, string $slug, string $title, string $content = '', string $status = 'publish') => $db->table('posts')->insert(['ID' => $id, 'post_type' => $type, 'post_name' => $slug, 'post_title' => $title, 'post_content' => $content, 'post_status' => $status]);

    $post(14004, 'post', 'cum-sa-ai-un-site-web-performant-pentru-cabinetul-tau-de-avocatura', 'Cum să ai un site web performant');
    $post(13, 'page', 'creare-magazin-online', 'Creare magazin online');
    $post(16530, 'proiect', 'tekgren-logo', 'Tekgren logo', 'Logo-ul Tekgren a fost creat pentru a reflecta inovatia.');
    $post(99, 'page', 'ciorna', 'Ciornă', 'Nepublicat', 'draft');
    $db->table('postmeta')->insert([
        ['post_id' => 14004, 'meta_key' => '_elementor_data', 'meta_value' => File::get("{$fixtures}/elementor-article-2023.json")],
        ['post_id' => 13, 'meta_key' => '_elementor_data', 'meta_value' => File::get("{$fixtures}/elementor-page-magazin-online.json")],
    ]);

    $domenii = json_decode(File::get("{$fixtures}/pods-domenii-agentie-turism.json"), true);
    $post(8787, 'domenii', 'creare-site-agentie-turism-tour-operator', $domenii['title']);

    foreach ($domenii['fields'] as $key => $values) {
        foreach ((array) $values as $value) {
            $db->table('postmeta')->insert(['post_id' => 8787, 'meta_key' => $key, 'meta_value' => $value]);
        }
    }

    $this->source = storage_path('framework/testing/legacy-convert/source');
    $this->output = storage_path('framework/testing/legacy-convert/output');
    File::deleteDirectory(dirname($this->source));
    File::ensureDirectoryExists($this->source);

    $map = new UrlMap;
    $map->add(['old_url' => 'https://www.webis.ro/portofoliu/', 'decision' => 'skip', 'target_path' => '/portofoliu/']);
    $handle = fopen("{$this->source}/url-map.csv", 'w');
    $map->writeCsv($handle);
    fclose($handle);
    File::put("{$this->source}/legacy-ids.json", '{}');
    File::put("{$this->source}/media-source.json", json_encode([['wp_id' => 14333, 'file' => '2024/05/turism.jpg', 'in_archive' => true, 'derivatives' => []]]));
});

afterEach(function () {
    File::deleteDirectory(storage_path('framework/testing/legacy-convert'));
    Schema::connection('legacy')->dropIfExists('posts');
    Schema::connection('legacy')->dropIfExists('postmeta');
});

it('previews one post\'s blocks and report', function () {
    $this->artisan('legacy:convert', ['wp_id' => 13, '--source' => $this->source])
        ->expectsOutputToContain('#13 page /creare-magazin-online/ — via elementor')
        ->expectsOutputToContain('hero')
        ->expectsOutputToContain("dropped form (the footer's call instead): 1")
        ->expectsOutputToContain('dropped heading (eyebrow): 3')
        ->assertSuccessful();
});

it('writes a report of every live post without writing to any database', function () {
    $before = DB::connection('legacy')->table('postmeta')->count();

    $this->artisan('legacy:convert', ['--all' => true, '--source' => $this->source, '--output' => $this->output, '--archive' => 'none'])
        ->expectsOutputToContain('Converted 4 posts.')
        ->assertSuccessful();

    $report = json_decode(File::get("{$this->output}/conversion-report.json"), true);
    $posts = collect($report['posts'])->keyBy('slug');

    expect($report['totals']['posts'])->toBe(4)
        ->and(array_keys($report['totals']['by_type']))->toEqualCanonicalizing(['article', 'page', 'industry', 'project'])
        ->and($posts['creare-magazin-online'])->toMatchArray([
            'type' => 'page',
            'converter' => 'elementor',
            'blocks' => ['hero', 'features', 'stats', 'faq'],
        ])
        ->and($posts['creare-site-agentie-turism-tour-operator']['converter'])->toBe('pods')
        ->and($posts['creare-site-agentie-turism-tour-operator']['unresolved_count'])->toBe(5)
        ->and($posts['tekgren-logo']['blocks'])->toBe(['rich_text'])
        ->and($report['totals']['under_150_words'])->toBe([[
            'wp_id' => 16530,
            'type' => 'project',
            'slug' => 'tekgren-logo',
            'words' => 9,
            'source_words' => 9,
            'reason' => 'The source itself is short (9 words)',
        ]])
        ->and($report['totals']['dropped'])->toHaveKey('heading (eyebrow)')
        ->and($report['totals']['posts_with_invalid_blocks'])->toBe([])
        ->and(DB::connection('legacy')->table('postmeta')->count())->toBe($before);
});

it('needs a post id or --all, and the inventory\'s files', function () {
    $this->artisan('legacy:convert')->assertFailed();
    $this->artisan('legacy:convert', ['wp_id' => 13, '--source' => storage_path('framework/testing/nowhere')])->assertFailed();
});
