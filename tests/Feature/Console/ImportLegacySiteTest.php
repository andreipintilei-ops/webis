<?php

use App\Enums\ContentStatus;
use App\Enums\PageType;
use App\Enums\ProjectType;
use App\Enums\RedirectCode;
use App\Enums\RedirectMatchType;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Page;
use App\Models\PostCategory;
use App\Models\Project;
use App\Models\Redirect;
use App\Models\SlugHistory;
use App\Models\User;
use App\Support\Legacy\Import\BodyConverter;
use App\Support\Legacy\Import\ConversionContext;
use App\Support\Legacy\Import\ConvertedBody;
use App\Support\Legacy\Import\LegacyPost;
use App\Support\Legacy\Import\UnavailableBodyConverter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/*
| A miniature old site: `wpts_` rows in the test database (never
| `webis_legacy`), a tiny uploads zip and the committed reviewed-map fixture
| (tests/Fixtures/legacy/url-map-reviewed.csv), so legacy:import runs end to
| end on known rows.
*/

uses(RefreshDatabase::class);

/** A body converter standing in for the real ones (WP5b). */
class LegacyImportFakeConverter implements BodyConverter
{
    /** @var list<array<string, mixed>>|null */
    public ?array $blocks = null;

    public function convert(LegacyPost $post, string $kind, ConversionContext $context): ConvertedBody
    {
        return new ConvertedBody($this->blocks ?? [[
            'type' => 'rich_text',
            'data' => ['content' => ['type' => 'doc', 'content' => [
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => "Corpul articolului {$post->slug}."]]],
            ]]],
        ]], wordCount: 300);
    }

    public function available(): bool
    {
        return true;
    }
}

function legacyImportSchema(): void
{
    config(['database.connections.legacy' => [...config('database.connections.mysql'), 'prefix' => 'wpts_']]);
    DB::purge('legacy');

    $schema = Schema::connection('legacy');

    foreach (['options', 'yoast_indexable', 'posts', 'postmeta', 'terms', 'term_taxonomy', 'term_relationships', 'yoast_primary_term'] as $table) {
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
    });
    $schema->create('posts', function (Blueprint $table) {
        $table->id('ID');
        $table->string('post_name')->default('');
        $table->text('post_title');
        $table->text('post_excerpt');
        $table->string('post_status');
        $table->dateTime('post_date');
        $table->longText('post_content');
        $table->string('post_type');
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
        $table->longText('description');
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

    $db->table('options')->insert([
        ['option_name' => 'blogname', 'option_value' => 'Webis Iași'],
        ['option_name' => 'wpseo_titles', 'option_value' => serialize(['separator' => 'sc-dash'])],
    ]);
    $db->table('yoast_indexable')->insert([
        ['object_id' => 40, 'object_type' => 'post', 'object_sub_type' => 'page', 'title' => '%%title%% %%sep%% %%sitename%% %%page%%'],
        ['object_id' => 11, 'object_type' => 'post', 'object_sub_type' => 'post', 'title' => null],
    ]);

    $post = fn (int $id, string $type, string $name, string $title, string $content = '', string $date = '2026-04-14 12:27:44'): array => [
        'ID' => $id, 'post_type' => $type, 'post_name' => $name, 'post_title' => $title, 'post_excerpt' => '',
        'post_status' => 'publish', 'post_date' => $date, 'post_content' => $content,
    ];

    $db->table('posts')->insert([
        $post(11, 'post', 'ghid-seo', 'Ghid SEO', '<p>Despre SEO.</p>'),
        $post(12, 'post', '12705-2', 'Ce tip de găzduire poți alege?', '<p>Găzduire.</p>'),
        $post(20, 'proiect', 'zyanya', 'Zyanya', '<p>Am creat website-ul pentru magazinul online de bijuterii Zyanya. Designul este minimalist și curat. A treia propoziție nu intră.</p>', '2024-07-12 10:00:25'),
        $post(21, 'proiect', 'zyanya-logo', 'Zyanya Logo', 'Logo-ul Zyanya reflectă eleganța bijuteriilor.', '2024-07-11 00:00:45'),
        $post(22, 'proiect', 'little-tokyo', 'Little Tokyo', 'Website pentru restaurantul Little Tokyo din Iași.', '2023-02-01 10:00:09'),
        $post(30, 'domenii', 'creare-site-florarie', 'Creare site florărie'),
        $post(40, 'page', 'creare-magazin-online', 'Creare magazin online', '<p>Magazine online.</p>'),
    ]);

    $article = '<h2>De ce contează SEO</h2><p>'.str_repeat('Un site bine optimizat aduce clienți noi în fiecare lună. ', 20).'</p>'
        .'<p>Vezi <a href="https://www.webis.ro/gazduire/">găzduirea</a> și <a href="/12705-2/">tipurile de găzduire</a>.</p>'
        .'<p><img src="https://www.webis.ro/wp-content/uploads/2024/07/zyanya-300x200.jpg" alt="Zyanya"></p>'
        .'<p><img src="https://www.webis.ro/wp-content/uploads/2016/01/vechi.jpg" alt="Lipsă"></p>';

    $db->table('postmeta')->insert([
        ['post_id' => 11, 'meta_key' => '_thumbnail_id', 'meta_value' => '50'],
        ['post_id' => 11, 'meta_key' => '_elementor_data', 'meta_value' => json_encode([[
            'id' => 'a1', 'elType' => 'section', 'settings' => [], 'elements' => [[
                'id' => 'b1', 'elType' => 'column', 'settings' => [], 'elements' => [[
                    'id' => 'c1', 'elType' => 'widget', 'widgetType' => 'text-editor', 'settings' => ['editor' => $article], 'elements' => [],
                ]],
            ]],
        ]])],
        ['post_id' => 30, 'meta_key' => 'descriere_h1', 'meta_value' => '<p>Un site pentru florăria ta, gata în câteva săptămâni.</p>'],
        ['post_id' => 20, 'meta_key' => 'website_link', 'meta_value' => 'https://zyanya.ro/'],
        ['post_id' => 20, 'meta_key' => 'text_link', 'meta_value' => 'www.zyanya.ro'],
        ['post_id' => 20, 'meta_key' => '_thumbnail_id', 'meta_value' => '50'],
        ['post_id' => 20, 'meta_key' => 'cercetare_si_planificare', 'meta_value' => '<p>Clientul avea nevoie de un magazin.</p>'],
        ['post_id' => 20, 'meta_key' => 'dezvoltare_i_implementare', 'meta_value' => 'Am construit magazinul.'],
        ['post_id' => 20, 'meta_key' => 'domeniu_de_activitate', 'meta_value' => 'Magazin de bijuterii'],
        ['post_id' => 21, 'meta_key' => 'website_link', 'meta_value' => 'https://www.zyanya.ro/'],
        ['post_id' => 21, 'meta_key' => '_thumbnail_id', 'meta_value' => '52'],
        ['post_id' => 21, 'meta_key' => 'galerie_logo', 'meta_value' => '52'],
        ['post_id' => 21, 'meta_key' => 'galerie_logo', 'meta_value' => '53'],
        ['post_id' => 21, 'meta_key' => '_pods_galerie_logo', 'meta_value' => serialize([53, 52])],
        ['post_id' => 22, 'meta_key' => 'website_link', 'meta_value' => ''],
        ['post_id' => 22, 'meta_key' => 'text_link', 'meta_value' => 'www.littletokyo.ro'],
        ['post_id' => 22, 'meta_key' => '_thumbnail_id', 'meta_value' => '54'],
    ]);

    $db->table('terms')->insert([
        ['term_id' => 13, 'name' => 'Web development', 'slug' => 'web-development'],
        ['term_id' => 27, 'name' => 'SEO', 'slug' => 'seo'],
        ['term_id' => 39, 'name' => 'Magazin Online', 'slug' => 'magazin-online'],
        ['term_id' => 40, 'name' => 'Logo Design', 'slug' => 'logo-design'],
        ['term_id' => 41, 'name' => 'Site de prezentare', 'slug' => 'site-de-prezentare'],
    ]);
    $db->table('term_taxonomy')->insert([
        ['term_taxonomy_id' => 13, 'term_id' => 13, 'taxonomy' => 'category', 'description' => 'Articole despre programare'],
        ['term_taxonomy_id' => 27, 'term_id' => 27, 'taxonomy' => 'category', 'description' => ''],
        ['term_taxonomy_id' => 39, 'term_id' => 39, 'taxonomy' => 'categorie_proiect', 'description' => ''],
        ['term_taxonomy_id' => 40, 'term_id' => 40, 'taxonomy' => 'categorie_proiect', 'description' => ''],
        ['term_taxonomy_id' => 41, 'term_id' => 41, 'taxonomy' => 'categorie_proiect', 'description' => ''],
    ]);
    $db->table('term_relationships')->insert([
        ['object_id' => 20, 'term_taxonomy_id' => 39],
        ['object_id' => 21, 'term_taxonomy_id' => 40],
        ['object_id' => 22, 'term_taxonomy_id' => 41],
    ]);
}

/** A tiny real image, so AttachUpload can read its size. */
function legacyImportImage(string $type, int $width, int $height): string
{
    $image = imagecreatetruecolor($width, $height);
    imagefill($image, 0, 0, (int) imagecolorallocate($image, 31, 40, 104));
    ob_start();
    $type === 'png' ? imagepng($image) : imagejpeg($image);

    return (string) ob_get_clean();
}

beforeEach(function () {
    legacyImportSchema();
    Storage::fake('public');
    // Conversions wait on the database queue instead of running inline.
    config(['queue.default' => 'database']);

    $this->output = storage_path('framework/testing/legacy-import-'.Str::random(8));
    File::ensureDirectoryExists($this->output);
    $this->archive = "{$this->output}/webis-bk.zip";
    $this->mediaSource = "{$this->output}/media-source.json";

    $zip = new ZipArchive;
    $zip->open($this->archive, ZipArchive::CREATE);
    $zip->addFromString('public_html/wp-config.php', "<?php // never read\n");
    $zip->addFromString('public_html/wp-content/uploads/2024/07/zyanya.jpg', legacyImportImage('jpg', 600, 400));
    $zip->addFromString('public_html/wp-content/uploads/2024/07/zyanya-300x200.jpg', legacyImportImage('jpg', 30, 20));
    $zip->addFromString('public_html/wp-content/uploads/2024/07/logo.png', legacyImportImage('png', 40, 40));
    $zip->addFromString('public_html/wp-content/uploads/2024/07/logo-alb.png', legacyImportImage('png', 40, 40));
    $zip->addFromString('public_html/wp-content/uploads/2024/07/video.mp4', 'not really a video');
    $zip->close();

    $site = 'https://www.webis.ro/wp-content/uploads/';
    $attachment = fn (int $id, string $file, string $mime, array $extra = []): array => [
        'wp_id' => $id, 'url' => $site.$file, 'file' => $file, 'mime_type' => $mime, 'alt' => '', 'title' => '',
        'post_parent' => 0, 'in_archive' => true, 'derivatives_in_archive' => 0, 'derivatives' => [], ...$extra,
    ];

    File::put($this->mediaSource, json_encode([
        $attachment(50, '2024/07/zyanya.jpg', 'image/jpeg', ['alt' => 'Magazin Zyanya', 'title' => 'zyanya.ro', 'derivatives' => [
            ['size' => 'medium', 'file' => '2024/07/zyanya-300x200.jpg', 'url' => $site.'2024/07/zyanya-300x200.jpg', 'width' => 300, 'height' => 200, 'in_archive' => true],
            ['size' => 'thumbnail', 'file' => '2024/07/zyanya-150x150.jpg', 'url' => $site.'2024/07/zyanya-150x150.jpg', 'width' => 150, 'height' => 150, 'in_archive' => false],
        ]]),
        $attachment(52, '2024/07/logo.png', 'image/png', ['title' => 'Logo Zyanya']),
        $attachment(53, '2024/07/logo-alb.png', 'image/png'),
        $attachment(54, '2024/07/lipsa.jpg', 'image/jpeg', ['in_archive' => false]),
        $attachment(55, '2024/04/arhiva.zip', 'application/zip', ['in_archive' => false]),
        $attachment(56, '2024/07/video.mp4', 'video/mp4'),
    ]));
});

afterEach(function () {
    File::deleteDirectory($this->output);
});

/**
 * Runs legacy:import on the fixture; returns the exit code.
 *
 * @param  array<string, mixed>  $options
 */
function runLegacyImport(object $test, array $options = []): int
{
    return Artisan::call('legacy:import', [
        '--map' => base_path('tests/Fixtures/legacy/url-map-reviewed.csv'),
        '--media-source' => $test->mediaSource,
        '--archive' => $test->archive,
        '--output' => $test->output,
        ...$options,
    ]);
}

/** @return array<string, mixed> the newest report */
function legacyImportReport(object $test): array
{
    $files = glob("{$test->output}/import-report-*.json") ?: [];
    sort($files);

    return json_decode((string) File::get((string) end($files)), true);
}

/** @return array<string, int> */
function legacyMediaMap(object $test): array
{
    return json_decode((string) File::get("{$test->output}/media-map.json"), true);
}

it('creates one category per category row, keeping the slug', function () {
    expect(runLegacyImport($this, ['--only' => ['categories']]))->toBe(0);

    expect(PostCategory::query()->orderBy('sort_order')->pluck('slug')->all())->toBe(['web-development', 'seo']);

    $webDev = PostCategory::where('slug', 'web-development')->sole();
    $seo = PostCategory::where('slug', 'seo')->sole();

    expect($webDev->name)->toBe('Web development')
        ->and($webDev->description)->toBe('Articole despre programare')
        ->and($seo->seo->description)->toBe('Totul despre SEO')
        // A merged category is a redirect row, never a category.
        ->and(PostCategory::where('slug', 'gazduire')->exists())->toBeFalse();
});

it('maps an original and its resized copies to one asset, counts the missing, and does not re-upload', function () {
    expect(runLegacyImport($this, ['--only' => ['media']]))->toBe(0);

    $map = legacyMediaMap($this);
    $site = 'https://www.webis.ro/wp-content/uploads/2024/07/';
    $zyanya = $map[$site.'zyanya.jpg'];

    expect(Asset::count())->toBe(3)
        ->and($map[$site.'zyanya-300x200.jpg'])->toBe($zyanya)
        ->and($map[$site.'zyanya-150x150.jpg'])->toBe($zyanya)
        ->and($map)->not->toHaveKey($site.'lipsa.jpg')
        ->and(Asset::find($zyanya)->alt)->toBe('Magazin Zyanya')
        ->and(Asset::find($zyanya)->width)->toBe(600)
        ->and(Asset::find($zyanya)->file())->not->toBeNull();

    $counts = legacyImportReport($this)['steps']['media']['counts'];
    expect($counts)->toMatchArray(['attached' => 3, 'missing' => 1, 'skipped_zip' => 1, 'skipped_not_image' => 1, 'derivatives_mapped' => 2]);

    $this->travel(1)->seconds();
    expect(runLegacyImport($this, ['--only' => ['media']]))->toBe(0);

    expect(Asset::count())->toBe(3)
        ->and(legacyMediaMap($this))->toBe($map)
        ->and(legacyImportReport($this)['steps']['media']['counts'])->toMatchArray(['reused' => 3])
        ->and(legacyImportReport($this)['steps']['media']['counts'])->not->toHaveKey('attached');
});

it('takes the largest resized copy that fits when an original is over the size cap', function () {
    config(['media-library.max_file_size' => strlen(legacyImportImage('jpg', 30, 20)) + 10]);

    runLegacyImport($this, ['--only' => ['media']]);

    $report = legacyImportReport($this)['steps']['media'];
    expect($report['resized_copy_used'][0])->toMatchArray(['wp_id' => 50, 'used' => '2024/07/zyanya-300x200.jpg'])
        ->and(Asset::find(legacyMediaMap($this)['https://www.webis.ro/wp-content/uploads/2024/07/zyanya.jpg'])->width)->toBe(30);
});

it('imports projects: fields, type, client reuse and cover image', function () {
    Client::factory()->create(['name' => 'Little Tokyo', 'url' => null]);

    expect(runLegacyImport($this, ['--only' => ['media', 'projects'], '--publish' => true]))->toBe(0);

    $map = legacyMediaMap($this);
    $site = 'https://www.webis.ro/wp-content/uploads/2024/07/';
    $zyanya = Project::where('slug', 'zyanya')->sole();
    $logo = Project::where('slug', 'zyanya-logo')->sole();
    $tokyo = Project::where('slug', 'little-tokyo')->sole();

    expect($zyanya->title)->toBe('Zyanya')
        ->and($zyanya->type)->toBe(ProjectType::Shop)
        ->and($zyanya->url)->toBe('https://zyanya.ro/')
        ->and($zyanya->cover_asset_id)->toBe($map[$site.'zyanya.jpg'])
        ->and($zyanya->cover->file())->not->toBeNull()
        ->and($zyanya->year)->toBe(2024)
        ->and($zyanya->summary)->toBe('Am creat website-ul pentru magazinul online de bijuterii Zyanya. Designul este minimalist și curat.')
        ->and($zyanya->need)->toBe('Clientul avea nevoie de un magazin.')
        ->and($zyanya->built)->toBe('Am construit magazinul.')
        ->and($zyanya->status)->toBe(ContentStatus::Published)
        // 10:00:25 in Bucharest (summer time) is 07:00:25 UTC.
        ->and($zyanya->published_at->utc()->format('Y-m-d H:i:s'))->toBe('2024-07-12 07:00:25')
        ->and($zyanya->client->name)->toBe('Zyanya')
        ->and($zyanya->client->sector)->toBe('Magazin de bijuterii')
        ->and($zyanya->client->show_in_logos)->toBeFalse();

    expect($logo->type)->toBe(ProjectType::Logo)
        // Same website (www. or not): the same client, not a "Zyanya Logo" one.
        ->and($logo->client_id)->toBe($zyanya->client_id)
        // The gallery in the order Pods kept.
        ->and($logo->images)->toBe([$map[$site.'logo-alb.png'], $map[$site.'logo.png']])
        ->and($logo->cover_asset_id)->toBe($map[$site.'logo.png']);

    // Type from the term when the map has no primary category; the URL from
    // the displayed link; the existing client reused by name.
    expect($tokyo->type)->toBe(ProjectType::Presentation)
        ->and($tokyo->url)->toBe('https://www.littletokyo.ro')
        ->and($tokyo->client->name)->toBe('Little Tokyo')
        ->and($tokyo->client->url)->toBe('https://www.littletokyo.ro')
        ->and($tokyo->cover_asset_id)->toBeNull()
        ->and($tokyo->year)->toBe(2023);

    expect(Client::count())->toBe(2);

    $report = legacyImportReport($this)['steps']['projects'];
    expect($report['counts'])->toMatchArray(['created' => 3, 'type_magazin' => 1, 'type_logo' => 1, 'type_prezentare' => 1, 'cover_unmapped' => 1]);
});

it('never copies a Yoast canonical pointing at webis.ro', function () {
    runLegacyImport($this, ['--only' => ['projects']]);

    expect(Project::where('slug', 'little-tokyo')->sole()->seo->canonical)->toBeNull()
        ->and(legacyImportReport($this)['steps']['projects']['canonicals_discarded'])
        ->toBe([['path' => '/proiect/little-tokyo', 'canonical' => 'https://webis.ro/proiect/little-tokyo/']]);
});

it('imports drafts unless --publish', function () {
    runLegacyImport($this, ['--only' => ['projects']]);

    expect(Project::pluck('status')->unique()->all())->toBe([ContentStatus::Draft])
        ->and(Project::where('slug', 'zyanya')->sole()->published_at)->not->toBeNull();
});

it('is idempotent: a re-run refreshes titles and summaries but keeps hand edits unless --force', function () {
    runLegacyImport($this, ['--only' => ['media', 'projects']]);
    $before = Project::count();

    Project::where('slug', 'zyanya')->sole()->update(['url' => 'https://altul.ro/', 'summary' => 'Editat de mână', 'title' => 'Titlu editat']);

    expect(runLegacyImport($this, ['--only' => ['media', 'projects']]))->toBe(0);

    $zyanya = Project::where('slug', 'zyanya')->sole();
    expect(Project::count())->toBe($before)
        ->and(Client::count())->toBe(2)
        ->and(Asset::count())->toBe(3)
        ->and($zyanya->url)->toBe('https://altul.ro/')
        ->and($zyanya->title)->toBe('Zyanya')
        ->and($zyanya->summary)->toStartWith('Am creat website-ul')
        ->and(legacyImportReport($this)['steps']['projects']['counts'])->toMatchArray(['updated' => 1, 'unchanged' => 2]);

    runLegacyImport($this, ['--only' => ['projects'], '--force' => true]);

    expect(Project::where('slug', 'zyanya')->sole()->url)->toBe('https://zyanya.ro/');
});

it('writes nothing on a dry run', function () {
    expect(runLegacyImport($this, ['--only' => ['categories', 'media', 'projects', 'redirects'], '--dry-run' => true, '--load-redirects' => true]))->toBe(0);

    expect(PostCategory::count())->toBe(0)
        ->and(Project::count())->toBe(0)
        ->and(Client::count())->toBe(0)
        ->and(Asset::count())->toBe(0)
        ->and(Redirect::count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([])
        ->and(File::exists("{$this->output}/media-map.json"))->toBeFalse()
        ->and(File::exists("{$this->output}/redirects.csv"))->toBeFalse()
        ->and(File::exists("{$this->output}/legacy-ids.json"))->toBeFalse();

    $report = legacyImportReport($this)['steps'];
    expect($report['media']['counts']['would_attach'])->toBe(3)
        ->and($report['projects']['counts']['created'])->toBe(3)
        ->and($report['categories']['counts']['created'])->toBe(2);
});

it('refuses the articles, pages and industries steps while no converter is bound', function (string $step) {
    app()->bind(BodyConverter::class, UnavailableBodyConverter::class);

    expect(runLegacyImport($this, ['--only' => [$step]]))->toBe(1)
        ->and(Artisan::output())->toContain('No body converter is available')
        ->and(Page::count())->toBe(0)
        ->and(legacyImportReport($this)['steps'][$step]['refused'])->toContain('content converters');
})->with(['articles', 'pages', 'industries']);

describe('with a body converter', function () {
    beforeEach(function () {
        $this->converter = new LegacyImportFakeConverter;
        app()->instance(BodyConverter::class, $this->converter);
    });

    it('imports articles at their target slug, in their category, dated by original_date', function () {
        expect(runLegacyImport($this, ['--only' => ['categories', 'media', 'articles']]))->toBe(0);

        $moved = Page::where('slug', 'care-sunt-tipurile-de-gazduire')->sole();
        $seo = Page::where('slug', 'ghid-seo')->sole();

        expect(Page::where('slug', '12705-2')->exists())->toBeFalse()
            ->and($moved->type)->toBe(PageType::Article)
            ->and($moved->category->slug)->toBe('web-development')
            ->and($moved->status)->toBe(ContentStatus::Draft)
            // original_date at the post's time of day, Bucharest time.
            ->and($moved->published_at->utc()->format('Y-m-d H:i:s'))->toBe('2022-03-03 07:15:00')
            ->and($seo->published_at->utc()->format('Y-m-d H:i:s'))->toBe('2021-04-21 09:27:44')
            ->and($seo->category->slug)->toBe('seo')
            ->and($seo->excerpt)->toBe('Un ghid SEO pe înțelesul tuturor')
            ->and($seo->seo->description)->toBe('Un ghid SEO pe înțelesul tuturor')
            ->and($seo->seo->focusKeyword)->toBe('ghid seo')
            ->and($seo->seo->canonical)->toBeNull()
            ->and($seo->hero_asset_id)->toBe(legacyMediaMap($this)['https://www.webis.ro/wp-content/uploads/2024/07/zyanya.jpg'])
            ->and($seo->blocks[0]['type'])->toBe('rich_text');
    });

    it('resolves Yoast title variables and imports pages and industries by type', function () {
        expect(runLegacyImport($this, ['--only' => ['pages', 'industries'], '--publish' => true]))->toBe(0);

        $page = Page::where('slug', 'creare-magazin-online')->sole();

        expect($page->type)->toBe(PageType::Page)
            ->and($page->seo->title)->toBe('Creare magazin online - Webis Iași')
            ->and($page->status)->toBe(ContentStatus::Published)
            ->and(Page::where('slug', 'creare-site-florarie')->sole()->type)->toBe(PageType::Industry)
            // The skip row: the live contact page is no import's business.
            ->and(Page::where('slug', 'contact')->exists())->toBeFalse();
    });

    it('leaves a slug another page holds alone, and reports it', function () {
        $live = Page::factory()->create(['slug' => 'creare-magazin-online', 'type' => PageType::Service, 'title' => 'Pagina live']);

        runLegacyImport($this, ['--only' => ['pages']]);

        expect($live->fresh()->title)->toBe('Pagina live')
            ->and(legacyImportReport($this)['steps']['pages']['exists_kept'][0])->toMatchArray(['slug' => 'creare-magazin-online', 'type' => 'service']);
    });

    it('never replaces non-empty blocks without --force', function () {
        runLegacyImport($this, ['--only' => ['articles']]);
        $page = Page::where('slug', 'ghid-seo')->sole();
        $page->update(['title' => 'Editat', 'blocks' => [['type' => 'rich_text', 'data' => ['content' => ['type' => 'doc', 'content' => [
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Text scris de mână.']]],
        ]]]]]]);

        runLegacyImport($this, ['--only' => ['articles']]);
        $page->refresh();

        expect($page->title)->toBe('Ghid SEO')
            ->and(json_encode($page->blocks, JSON_UNESCAPED_UNICODE))->toContain('Text scris de mână.')
            ->and(Page::count())->toBe(2);

        runLegacyImport($this, ['--only' => ['articles'], '--force' => true]);

        expect(json_encode($page->fresh()->blocks, JSON_UNESCAPED_UNICODE))->toContain('Corpul articolului ghid-seo.');
    });

    it('refuses blocks that do not validate', function () {
        $this->converter->blocks = [['type' => 'rich_text', 'data' => ['content' => 'nu e un document']]];

        runLegacyImport($this, ['--only' => ['articles']]);

        expect(Page::count())->toBe(0)
            ->and(legacyImportReport($this)['steps']['articles']['counts']['refused'])->toBe(2)
            ->and(legacyImportReport($this)['steps']['articles']['refused'][0]['reason'])->toStartWith('invalid blocks');
    });
});

describe('with the real converters', function () {
    it('converts an Elementor article with real asset ids and final link targets, counting unresolved images', function () {
        expect(runLegacyImport($this, ['--only' => ['categories', 'media', 'articles']]))->toBe(0);

        $page = Page::where('slug', 'ghid-seo')->sole();
        $json = json_encode($page->blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $zyanya = legacyMediaMap($this)['https://www.webis.ro/wp-content/uploads/2024/07/zyanya.jpg'];

        // The hero is the title; the body is rich text.
        expect($page->blocks[0]['type'])->toBe('hero')
            ->and($page->blocks[0]['data']['heading'])->toBe('Ghid SEO')
            ->and(array_column($page->blocks, 'type'))->toContain('rich_text')
            // The resized copy resolves to its original's real asset.
            ->and($json)->toContain('"assetId":'.$zyanya)
            // Links to the old site land on their final targets, slash form.
            ->and($json)->toContain('"href":"/blog/categorie/web-development/"')
            ->and($json)->toContain('"href":"/care-sunt-tipurile-de-gazduire/"')
            ->and($json)->not->toContain('vechi.jpg');

        $report = legacyImportReport($this)['steps']['articles'];
        expect($report['counts'])->toMatchArray(['created' => 2, 'unresolved_image_urls' => 1])
            ->and($report['unresolved_image'])->toContain(['slug' => 'ghid-seo', 'url' => 'https://www.webis.ro/wp-content/uploads/2016/01/vechi.jpg']);
    });

    it('converts an industry page from its Pods fields', function () {
        expect(runLegacyImport($this, ['--only' => ['industries']]))->toBe(0);

        $page = Page::where('slug', 'creare-site-florarie')->sole();

        expect($page->type)->toBe(PageType::Industry)
            ->and(json_encode($page->blocks, JSON_UNESCAPED_UNICODE))->toContain('Un site pentru florăria ta');
    });

    it('gives each project its body as rich text', function () {
        runLegacyImport($this, ['--only' => ['projects']]);

        $zyanya = Project::where('slug', 'zyanya')->sole();

        expect(array_column($zyanya->blocks, 'type'))->toBe(['rich_text'])
            ->and(json_encode($zyanya->blocks, JSON_UNESCAPED_UNICODE))->toContain('Designul este minimalist');
    });
});

/** @return array<string, list<string>> source → [target, code, type] */
function legacyRedirectRows(string $csv): array
{
    $rows = [];

    foreach (array_slice(preg_split('/\R/', trim($csv)) ?: [], 1) as $line) {
        $cells = str_getcsv($line, ';', '"', '');
        $rows[$cells[0]] = array_slice($cells, 1);
    }

    return $rows;
}

describe('redirects', function () {
    beforeEach(function () {
        runLegacyImport($this, ['--only' => ['media', 'projects']]);
        // Renamed on the new site: SlugHistory answers it, so no row.
        SlugHistory::remember(Project::where('slug', 'zyanya')->sole(), 'zyanya-veche');
    });

    it('writes the admin import format with every kind of row, minus loops, chains and SlugHistory', function () {
        expect(runLegacyImport($this, ['--only' => ['redirects']]))->toBe(0);

        $csv = File::get("{$this->output}/redirects.csv");
        $rows = legacyRedirectRows($csv);
        $zyanyaFile = Asset::find(legacyMediaMap($this)['https://www.webis.ro/wp-content/uploads/2024/07/zyanya.jpg'])->file()->getUrl();
        $zyanyaPath = parse_url($zyanyaFile, PHP_URL_PATH);

        expect(strtok($csv, "\n"))->toBe('sursa;destinatie;cod;tip')
            ->and($rows['/gazduire'])->toBe(['/blog/categorie/web-development/', '301', 'exact'])
            ->and($rows['/uncategorized'])->toBe(['', '410', 'exact'])
            ->and($rows['/2025/11/13/lucky-casino'])->toBe(['', '410', 'exact'])
            ->and($rows['/page'])->toBe(['/blog/', '301', 'prefix'])
            // A 410 for a whole date path of injected spam.
            ->and($rows['/2026/03/28'])->toBe(['', '410', 'prefix'])
            ->and($rows['/proiect/zyanya-2'])->toBe(['/proiect/zyanya/', '301', 'exact'])
            // Content whose target is another address: the old root category,
            // and /12705-2 → its original slug.
            ->and($rows['/web-development'])->toBe(['/blog/categorie/web-development/', '301', 'exact'])
            ->and($rows['/12705-2'])->toBe(['/care-sunt-tipurile-de-gazduire/', '301', 'exact'])
            // Images: original and resized copies to the asset's file; 410 when none.
            ->and($rows['/wp-content/uploads/2024/07/zyanya.jpg'])->toBe([$zyanyaPath, '301', 'exact'])
            ->and($rows['/wp-content/uploads/2024/07/zyanya-300x200.jpg'])->toBe([$zyanyaPath, '301', 'exact'])
            ->and($rows['/wp-content/uploads/2024/07/zyanya-150x150.jpg'])->toBe([$zyanyaPath, '301', 'exact'])
            ->and($rows['/wp-content/uploads/2024/07/lipsa.jpg'])->toBe(['', '410', 'exact'])
            ->and($rows['/wp-content/uploads/2024/07/video.mp4'])->toBe(['', '410', 'exact'])
            ->and($rows)->toHaveKey('/lant-2')
            // The dated-address rule answers its row: no table row.
            ->and($rows)->not->toHaveKeys(['/bucla-a', '/bucla-b', '/lant-1', '/proiect/zyanya-veche', '/ghid-seo', '/contact', '/2026/04/14/ghid-seo']);

        foreach ($rows as [$target]) {
            if ($target !== '' && ! str_starts_with($target, '/storage/')) {
                expect($target)->toEndWith('/');
            }
        }

        $report = legacyImportReport($this)['steps']['redirects'];
        expect($report['counts'])->toMatchArray(['dropped_loop' => 2, 'dropped_chain' => 1, 'excluded_slug_history' => 1])
            ->and($report['dropped_chain'][0]['walk'])->toBe(['/lant-1', '/lant-2']);

        $ids = json_decode(File::get("{$this->output}/legacy-ids.json"), true);
        expect($ids)->toMatchArray(['12' => '/care-sunt-tipurile-de-gazduire/', '20' => '/proiect/zyanya/', '11' => '/ghid-seo/']);
    });

    it('round-trips through the admin import', function () {
        runLegacyImport($this, ['--only' => ['redirects'], '--load-redirects' => true]);

        $written = count(legacyRedirectRows(File::get("{$this->output}/redirects.csv")));
        $loaded = Redirect::query()->pluck('target', 'source_path')->all();

        expect(Redirect::count())->toBe($written)
            ->and(legacyImportReport($this)['steps']['redirects']['counts'])->toMatchArray(['loaded' => $written, 'load_skipped' => 0])
            ->and(Redirect::where('source_path', '/page')->sole()->match_type)->toBe(RedirectMatchType::Prefix)
            ->and(Redirect::where('source_path', '/uncategorized')->sole()->status_code)->toBe(RedirectCode::Gone);

        // The same file through the admin's own upload changes nothing.
        Redirect::query()->delete();
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.redirects.import'), [
                'file' => UploadedFile::fake()->createWithContent('redirects.csv', File::get("{$this->output}/redirects.csv")),
            ])->assertRedirect();

        expect(Redirect::query()->pluck('target', 'source_path')->all())->toEqual($loaded);
    });
});

it('rejects an unknown step', function () {
    expect(runLegacyImport($this, ['--only' => ['posts']]))->toBe(2)
        ->and(Artisan::output())->toContain('Unknown step(s): posts');
});
