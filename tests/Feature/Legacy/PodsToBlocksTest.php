<?php

use App\Blocks\BlockRegistry;
use App\Models\Asset;
use App\Support\Legacy\BlockListValidator;
use App\Support\Legacy\HtmlToBlocks;
use App\Support\Legacy\LegacyUrlIndex;
use App\Support\Legacy\MediaResolver;
use App\Support\Legacy\PodsToBlocks;
use App\Support\Legacy\WordPressHtml;
use App\Support\RichText\RichText;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
| tests/Fixtures/legacy/pods-domenii-agentie-turism.json is the real field set
| of the `domenii` page "Creare site agentie turism Tour-operator" (#8787).
*/

/**
 * @param  list<int>  $attachmentIds
 * @return MediaResolver&object{assets: array<int, int>}
 */
function podsMedia(array $attachmentIds): MediaResolver
{
    $assets = [];

    foreach ($attachmentIds as $id) {
        $assets[$id] = Asset::factory()->create()->id;
    }

    return new class($assets) implements MediaResolver
    {
        /** @param array<int, int> $assets */
        public function __construct(public array $assets) {}

        public function assetIdForUrl(string $url): ?int
        {
            return null;
        }

        public function assetIdForAttachment(int $attachmentId): ?int
        {
            return $this->assets[$attachmentId] ?? null;
        }
    };
}

function legacyHtml(MediaResolver $media): WordPressHtml
{
    return new WordPressHtml($media, new LegacyUrlIndex(['/contact' => '/contact/']), app(RichText::class));
}

/**
 * @return array{wp_id: int, title: string, fields: array<string, string|list<string>>}
 */
function podsFixture(): array
{
    return json_decode((string) file_get_contents(base_path('tests/Fixtures/legacy/pods-domenii-agentie-turism.json')), true);
}

it('maps a real industry page\'s fields onto the blocks of its old layout', function () {
    // The featured image and five of the six gallery images are in the library.
    $media = podsMedia([14333, 14331, 14332, 14334, 14335]);
    $registry = app(BlockRegistry::class);
    $fixture = podsFixture();

    $conversion = (new PodsToBlocks(legacyHtml($media), new BlockListValidator($registry), $registry))->convert($fixture['fields'], $fixture['title']);
    [$hero, $benefits, $packages, $models, $gallery, $closing] = $conversion->blocks;

    // No closing form: the footer's call to talk ends the page. Both texts
    // under the old title are the hero's, no section of their own after it.
    expect($conversion->blockTypes())->toBe(['hero', 'features', 'features', 'rich_text', 'gallery', 'rich_text'])
        ->and($hero['data'])->toMatchArray([
            'heading' => 'Creare site agentie turism Tour-operator',
            'image_asset_id' => $media->assets[14333],
            'layout' => 'split',
            'primary_cta' => ['label' => 'Cere o ofertă', 'url' => '/contact/'],
        ])
        ->and(explode("\n\n", $hero['data']['subheading']))->toHaveCount(2)
        ->and($hero['data']['subheading'])->toStartWith("Echipa noastra va vine in ajutor propunandu-va diferite pachete de realizare site agentie de turism, avand o calitate superioara si un pret de nerefuzat.\n\nUn pret site agentie de turism poate fi discutat")
        ->and($benefits['data'])->toMatchArray(['heading' => 'Creare site agentie de turism', 'intro' => 'Un site pentru agentie de turism va permite'])
        ->and(array_column($benefits['data']['items'], 'title'))->toBe([
            'Vizualizarea pe harta a destinatiilor si a ofertelor de cazare',
            'Cautarea ofertelor si a destinatiilor in functie de diverse facilitati sau criterii',
            'Sortarea ofertelor in functie de numarul de stele, pret, popularitate',
            'Vizualizarea celor mai populare destinatii/ hoteluri/ camere',
            'Integrarea modului de plata online',
            'Vizualizarea hotelurilor in functie de destinatie',
        ])
        ->and($packages['data']['heading'])->toBe('Site agentie turism')
        ->and($packages['data']['items'][0])->toMatchArray([
            'title' => 'Website la cheie',
            'text' => 'Domeniu .ro gratuit · Pachet gazduire 12 luni GRATUIT · Design responsiv · Galerie foto si video pentru fiecare destinatie',
        ])
        ->and($models['data']['content_html'])->toStartWith('<h2 id="model-site-agentie-turism-tour-operator">Model site agentie turism / Tour operator</h2><p>Va prezentam')
        ->and($models['data']['content_html'])->toContain('<strong>site-uri pentru agentii de turism</strong>')
        ->and(array_column($gallery['data']['images'], 'asset_id'))->toBe([$media->assets[14331], $media->assets[14332], $media->assets[14333], $media->assets[14334], $media->assets[14335]])
        ->and($closing['data']['content_html'])->toStartWith('<p>Prin procesul de creare site web agentie de turism')
        ->and($conversion->report->unresolvedItems())->toBe(['image' => ['attachment #14336']])
        ->and($conversion->report->dropped())->toBe(['pods:galerie_h4' => 1, 'pods:imagine_background_prima_sectiune' => 1, 'pods:imagine_h3' => 1]);
});

it('keeps a description too long for the hero in a rich text after it', function () {
    $registry = app(BlockRegistry::class);
    $fields = podsFixture()['fields'];
    $fields['descriere_h1'] = str_repeat('Un text foarte lung despre site. ', 30);

    $conversion = (new PodsToBlocks(legacyHtml(podsMedia([])), new BlockListValidator($registry), $registry))->convert($fields, 'Turism');

    expect($conversion->blocks[0]['data']['subheading'])->toBeNull()
        ->and($conversion->blockTypes()[1])->toBe('rich_text')
        ->and($conversion->blocks[1]['data']['content_html'])->toStartWith('<p>Un text foarte lung despre site.');
});

it('returns blocks that pass the registry\'s validation', function () {
    $registry = app(BlockRegistry::class);
    $fixture = podsFixture();

    $blocks = (new PodsToBlocks(legacyHtml(podsMedia([14333, 14331])), new BlockListValidator($registry), $registry))
        ->convert($fixture['fields'], $fixture['title'])->blocks;

    expect($registry->validate($blocks))->toHaveCount(count($blocks));
});

it('keeps the packages as rich text when a list is too long for a card', function () {
    $registry = app(BlockRegistry::class);
    $fields = podsFixture()['fields'];
    $fields['beneficii_h3_-_descriere_3'] = '<ul>'.str_repeat('<li>Panou de control cu acces securizat cu nume de utilizator si parola</li>', 8).'</ul>';

    $conversion = (new PodsToBlocks(legacyHtml(podsMedia([])), new BlockListValidator($registry), $registry))->convert($fields, 'Turism');

    expect($conversion->blockTypes())->toBe(['hero', 'features', 'rich_text', 'rich_text', 'rich_text'])
        ->and($conversion->blocks[2]['data']['content_html'])->toStartWith('<h2 id="site-agentie-turism">Site agentie turism</h2><h3 id="website-la-cheie">Website la cheie</h3><ul><li><p>Domeniu .ro gratuit</p></li>');
});

it('converts a classic project body to rich text, with no hero', function () {
    $registry = app(BlockRegistry::class);
    $converter = new HtmlToBlocks(legacyHtml(podsMedia([])), new BlockListValidator($registry), $registry);

    $project = $converter->convert('Logo-ul Tekgren a fost creat pentru a reflecta inovatia si eficienta in domeniul panourilor solare.', 'Tekgren logo', withHero: false);
    $page = $converter->convert('<p>Text</p><iframe src="https://www.youtube.com/embed/XHOmBV4js_E"></iframe>', 'Pagină');

    expect($project->blockTypes())->toBe(['rich_text'])
        ->and($project->blocks[0]['data']['content_html'])->toBe('<p>Logo-ul Tekgren a fost creat pentru a reflecta inovatia si eficienta in domeniul panourilor solare.</p>')
        ->and($page->blockTypes())->toBe(['hero', 'rich_text', 'video'])
        ->and($page->blocks[2]['data'])->toMatchArray(['title' => 'Pagină', 'youtube_id' => 'XHOmBV4js_E']);
});
