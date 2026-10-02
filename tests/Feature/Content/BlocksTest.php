<?php

use App\Blocks\Block;
use App\Blocks\BlockRegistry;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Testimonial;
use App\Support\YouTube;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

/**
 * A valid `data` payload for every v1 block type, built on real rows so the
 * `exists` rules pass.
 *
 * @return array<string, array<string, mixed>>
 */
function validBlockSamples(): array
{
    $asset = Asset::factory()->create();
    $page = Page::factory()->create();
    $project = Project::factory()->create();
    $client = Client::factory()->create();
    $testimonial = Testimonial::factory()->create();
    $post = Post::factory()->create();
    $text = ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Text']]]]];

    return [
        'hero' => [
            'heading' => 'Creăm site-uri care aduc clienți',
            'layout' => 'split',
            'background' => 'none',
            'align' => 'center',
            'logos' => false,
            'image_asset_id' => $asset->id,
            'primary_cta' => ['label' => 'Cere o ofertă', 'url' => '/contact'],
        ],
        'statement' => ['eyebrow' => 'Despre noi', 'statement' => 'Din 2016 dezvoltăm software în Iași,
pentru organizații care nu găsesc
de-a gata ce le trebuie.', 'text' => 'Am început cu site-uri și magazine online.', 'link' => ['label' => 'Despre Webis', 'url' => '/despre']],
        'rich_text' => ['content' => $text],
        'services_grid' => ['source' => 'manual', 'page_ids' => [$page->id]],
        'features' => ['columns' => 3, 'items' => [['icon' => 'rocket', 'title' => 'Rapid', 'text' => 'Sub 2 secunde.']]],
        'portfolio_showcase' => ['source' => 'category', 'category_id' => ProjectCategory::factory()->create()->id, 'limit' => 6],
        'client_logos' => ['source' => 'manual', 'client_ids' => [$client->id]],
        'notable_clients' => ['source' => 'institutions'],
        'testimonials' => ['source' => 'manual', 'testimonial_ids' => [$testimonial->id], 'limit' => 3],
        'stats' => ['items' => [['value' => '250+', 'label' => 'site-uri livrate']]],
        'process_steps' => ['steps' => [['title' => 'Analiză', 'text' => 'Discutăm obiectivele.']]],
        'pricing_tiers' => ['tiers' => [[
            'name' => 'Start',
            'price' => 'de la 2.500 lei',
            'features' => ['5 pagini', 'SEO de bază'],
            'highlighted' => true,
            'cta' => ['label' => 'Alege', 'url' => '/contact'],
        ]]],
        'faq' => ['items' => [['question' => 'Cât durează?', 'answer' => 'Între 3 și 6 săptămâni.']]],
        'image_text' => ['content' => $text, 'image_asset_id' => $asset->id, 'image_position' => 'left'],
        'gallery' => ['columns' => 3, 'images' => [['asset_id' => $asset->id, 'caption' => 'Acasă']]],
        'video' => ['title' => 'Prezentare', 'url' => 'https://youtu.be/dQw4w9WgXcQ'],
        'blog_teaser' => ['source' => 'manual', 'post_ids' => [$post->id], 'limit' => 3],
        'cta_banner' => ['heading' => 'Hai să discutăm', 'primary_cta' => ['label' => 'Sună-ne', 'url' => 'tel:+40700000000'], 'variant' => 'accent'],
        'quote_form' => ['service_page_id' => $page->id, 'show_contact_details' => true],
    ];
}

/**
 * @param  array<string, mixed>  $data
 * @return list<array<string, mixed>>
 */
function oneBlock(string $type, array $data): array
{
    return [['id' => 'b1', 'type' => $type, 'v' => 1, 'data' => $data]];
}

it('registers every v1 block type with a label and a view', function () {
    $registry = app(BlockRegistry::class);

    expect(array_keys($registry->all()))->toBe(array_keys(validBlockSamples()));

    foreach ($registry->all() as $type => $block) {
        expect($block::label())->not->toBeEmpty()
            ->and($block->view())->toBe('blocks.'.str_replace('_', '-', $type))
            ->and($block::version())->toBeGreaterThanOrEqual(1);
    }
});

it('accepts a valid sample of every block type', function () {
    $registry = app(BlockRegistry::class);

    foreach (validBlockSamples() as $type => $data) {
        $blocks = $registry->validate(oneBlock($type, $data));

        expect($blocks[0]['type'])->toBe($type);
    }
});

it('starts every block type with exactly the fields it stores', function () {
    $registry = app(BlockRegistry::class);

    foreach ($registry->all() as $type => $block) {
        $made = $registry->make($type);

        expect($made['id'])->not->toBeEmpty()
            ->and(array_keys($block->prepare($made['data'])))
            ->toEqualCanonicalizing(array_keys($block->prepare([])));
    }
});

it('reports errors under the block and field that caused them', function () {
    try {
        app(BlockRegistry::class)->validate([
            ['id' => 'a', 'type' => 'faq', 'data' => ['items' => [['question' => 'Cât costă?']]]],
            ['id' => 'b', 'type' => 'hero', 'data' => ['layout' => 'split', 'primary_cta' => ['label' => 'Hai', 'url' => 'javascript:alert(1)']]],
        ]);
        $this->fail('Expected a validation error.');
    } catch (ValidationException $e) {
        expect(array_keys($e->errors()))->toEqualCanonicalizing([
            'blocks.0.data.items.0.answer',
            'blocks.1.data.heading',
            'blocks.1.data.primary_cta.url',
        ]);
    }
});

it('rejects unknown types, duplicate ids and extra keys', function (array $blocks, string $errorKey) {
    expect(fn () => app(BlockRegistry::class)->validate($blocks))
        ->toThrow(fn (ValidationException $e) => expect($e->errors())->toHaveKey($errorKey));
})->with([
    'unknown type' => [[['id' => 'a', 'type' => 'carousel', 'data' => []]], 'blocks.0.type'],
    'unknown hero background' => [[['id' => 'a', 'type' => 'hero', 'data' => ['heading' => 'X', 'layout' => 'centered', 'background' => 'video']]], 'blocks.0.data.background'],
    'unknown hero alignment' => [[['id' => 'a', 'type' => 'hero', 'data' => ['heading' => 'X', 'layout' => 'centered', 'align' => 'right']]], 'blocks.0.data.align'],
    'duplicate ids' => [[
        ['id' => 'a', 'type' => 'rich_text', 'data' => ['content' => ['type' => 'doc', 'content' => []]]],
        ['id' => 'a', 'type' => 'rich_text', 'data' => ['content' => ['type' => 'doc', 'content' => []]]],
    ], 'blocks.0.id'],
    'extra block key' => [[['id' => 'a', 'type' => 'rich_text', 'data' => [], 'html' => '<script>']], 'blocks.0'],
]);

it('requires the picked records when a block is set to manual', function () {
    expect(fn () => app(BlockRegistry::class)->validate(oneBlock('client_logos', ['source' => 'manual'])))
        ->toThrow(ValidationException::class);
});

it('normalises data on save: ids, versions, unknown fields and rich text', function () {
    $page = Page::factory()->create(['blocks' => [
        ['type' => 'rich_text', 'data' => [
            'content' => ['type' => 'doc', 'content' => [
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Salut']]],
                ['type' => 'script', 'content' => [['type' => 'text', 'text' => 'alert(1)']]],
            ]],
            'injected' => '<script>',
        ]],
        ['id' => 'v', 'type' => 'video', 'data' => ['title' => 'Demo', 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10']],
    ]]);

    [$text, $video] = $page->fresh()?->blocks ?? [];

    expect($text['id'])->toBeString()->not->toBeEmpty()
        ->and($text['v'])->toBe(1)
        ->and($text['data'])->not->toHaveKey('injected')
        ->and($text['data']['content_html'])->toBe('<p>Salut</p>')
        ->and($video['id'])->toBe('v')
        ->and($video['data']['youtube_id'])->toBe('dQw4w9WgXcQ');
});

it('keeps blocks of a retired type instead of losing them', function () {
    $blocks = app(BlockRegistry::class)->prepare([
        ['id' => 'old', 'type' => 'retired_widget', 'v' => 3, 'data' => ['x' => 1]],
    ]);

    expect($blocks)->toBe([['id' => 'old', 'type' => 'retired_widget', 'v' => 3, 'data' => ['x' => 1]]]);
});

it('tracks every image used inside blocks', function () {
    [$hero, $galleryImage, $inline, $poster] = Asset::factory()->count(4)->create();

    $project = Project::factory()->create(['blocks' => [
        ['type' => 'hero', 'data' => ['heading' => 'X', 'image_asset_id' => $hero->id]],
        ['type' => 'gallery', 'data' => ['images' => [['asset_id' => $galleryImage->id], ['asset_id' => $hero->id]]]],
        ['type' => 'rich_text', 'data' => ['content' => ['type' => 'doc', 'content' => [
            ['type' => 'assetImage', 'attrs' => ['assetId' => $inline->id]],
        ]]]],
        ['type' => 'video', 'data' => ['title' => 'V', 'url' => 'dQw4w9WgXcQ', 'poster_asset_id' => $poster->id]],
    ]]);

    $tracked = $project->assetUsages()->where('field', 'blocks')->pluck('asset_id')->sort()->values()->all();

    expect($tracked)->toBe(collect([$hero, $galleryImage, $inline, $poster])->pluck('id')->sort()->values()->all());
});

it('extracts YouTube ids from the usual URL forms', function (string $url, ?string $id) {
    expect(YouTube::idFromUrl($url))->toBe($id);
})->with([
    ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
    ['https://youtu.be/dQw4w9WgXcQ?si=abc', 'dQw4w9WgXcQ'],
    ['https://www.youtube.com/shorts/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
    ['https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
    ['dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
    ['https://vimeo.com/123456', null],
]);

it('gives each block class a distinct type key', function () {
    $types = array_map(fn (Block $block) => $block::type(), BlockRegistry::defaultBlocks());

    expect($types)->toHaveCount(19)->and(array_unique($types))->toHaveCount(19);
});

it('rejects a half-filled link on a feature card', function () {
    expect(fn () => app(BlockRegistry::class)->validate([
        ['id' => 'a', 'type' => 'features', 'data' => ['columns' => 3, 'items' => [
            ['title' => 'Bun', 'link' => ['label' => 'Află mai multe', 'url' => '/solutii']],
            ['title' => 'Fără adresă', 'link' => ['label' => 'Află mai multe', 'url' => '']],
        ]]],
    ]))->toThrow(fn (ValidationException $e) => expect(array_keys($e->errors()))->toBe(['blocks.0.data.items.1.link.url']));
});
