<?php

use App\Models\Asset;
use App\Support\RichText\RichText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(fn () => Storage::fake('public'));

/**
 * The saved HTML of a document with these images (asset id => caption),
 * a paragraph before and after each — as Block::prepare() stores it.
 *
 * @param  array<int, string>  $images
 */
function richHtmlWithImages(array $images): string
{
    $content = [];

    foreach ($images as $id => $caption) {
        $content[] = ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => "Înainte de {$id}"]]];
        $content[] = ['type' => 'assetImage', 'attrs' => ['assetId' => $id, 'caption' => $caption]];
    }

    $content[] = ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'La final']]];

    $richText = app(RichText::class);

    return $richText->render($richText->sanitize(['type' => 'doc', 'content' => $content]));
}

function renderRichText(string $html): string
{
    return Blade::render('<x-rich-text :html="$html" />', ['html' => $html]);
}

it('turns an image placeholder into the image, keeping its caption', function () {
    $asset = Asset::factory()->withImage(1200, 800)->create(['alt' => 'Fațada clădirii']);

    $html = richHtmlWithImages([$asset->id => 'Sediul <Webis> din Iași']);

    expect($html)->toContain('data-asset-id="'.$asset->id.'"');

    expect(renderRichText($html))
        ->toContain('<div class="rich-text">')
        ->toMatch('/<p>Înainte de \d+<\/p>\s*<figure class="rt-image">\s*<img\s+src="[^"]+"/')
        ->toContain('alt="Fațada clădirii"')
        ->toContain('width="1200" height="800"')
        ->toContain('loading="lazy"')
        // The caption as it was saved: escaped once, not twice.
        ->toContain('<figcaption>Sediul &lt;Webis&gt; din Iași</figcaption>')
        ->toContain('<p>La final</p>')
        ->not->toContain('data-asset-id');
});

it('leaves out an image whose asset is unknown, or has no file, and keeps the text around it', function () {
    $fileless = Asset::factory()->create();

    $output = renderRichText(richHtmlWithImages([999999 => 'Pierdută', $fileless->id => 'Fără fișier']));

    expect($output)
        ->not->toContain('<figure')
        ->not->toContain('figcaption')
        ->not->toContain('Pierdută')
        ->not->toContain('<img')
        ->toContain('<p>Înainte de 999999</p>')
        ->toContain('<p>La final</p>');
});

it('loads every image in a body with one query, however many there are', function () {
    $queries = function (int $count): array {
        $assets = Asset::factory()->withImage()->count($count)->create();
        $html = richHtmlWithImages($assets->mapWithKeys(fn (Asset $asset) => [$asset->id => ''])->all());

        DB::flushQueryLog();
        DB::enableQueryLog();
        $output = renderRichText($html);
        $log = DB::getQueryLog();
        DB::disableQueryLog();

        expect(substr_count($output, '<img'))->toBe($count);

        return array_column($log, 'query');
    };

    $one = $queries(1);
    $five = $queries(5);

    expect(count(array_filter($five, fn (string $sql) => str_contains($sql, 'from `assets`'))))->toBe(1)
        ->and(count($five))->toBe(count($one));
});

it('renders plain rich text without a query', function () {
    DB::enableQueryLog();

    expect(renderRichText('<p>Doar text</p>'))->toContain('<p>Doar text</p>')
        ->and(DB::getQueryLog())->toBe([]);

    DB::disableQueryLog();
});
