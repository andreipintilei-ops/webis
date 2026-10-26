<?php

use App\Blocks\BlockRegistry;
use App\Models\Asset;
use App\Support\Legacy\BlockListValidator;
use App\Support\Legacy\Conversion;
use App\Support\Legacy\ElementorToBlocks;
use App\Support\Legacy\LegacyUrlIndex;
use App\Support\Legacy\MediaResolver;
use App\Support\Legacy\WordPressHtml;
use App\Support\RichText\RichText;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
| The fixtures in tests/Fixtures/legacy are real `_elementor_data`, cut from
| the old site and pruned to the settings the converter reads (no styling,
| no form configuration). Images resolve to real assets, so the blocks pass
| the registry's own validation, `exists` rules included.
*/

/**
 * A media resolver that knows the given WordPress attachments, each as a
 * freshly created asset.
 *
 * @param  list<int>  $attachmentIds
 * @return MediaResolver&object{assets: array<int, int>}
 */
function elementorMedia(array $attachmentIds = []): MediaResolver
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

function elementorConverter(?MediaResolver $media = null): ElementorToBlocks
{
    $registry = app(BlockRegistry::class);
    $urls = new LegacyUrlIndex(['/portofoliu' => '/portofoliu/', '/creare-magazin-online' => '/creare-magazin-online/', '/contact' => '/contact/']);

    return new ElementorToBlocks(
        new WordPressHtml($media ?? elementorMedia(), $urls, app(RichText::class)),
        new BlockListValidator($registry),
        $registry,
    );
}

/**
 * @return array<mixed>
 */
function elementorFixture(string $name): array
{
    return json_decode((string) file_get_contents(base_path("tests/Fixtures/legacy/{$name}.json")), true);
}

/**
 * @return list<array<string, mixed>>
 */
function blocksOfType(Conversion $conversion, string $type): array
{
    return array_values(array_filter($conversion->blocks, fn (array $block): bool => $block['type'] === $type));
}

it('makes the first heading the hero, with its subheading, buttons and image', function () {
    $media = elementorMedia([1867]);
    $conversion = elementorConverter($media)->convert(elementorFixture('elementor-page-magazin-online'), 'Creare magazin online');
    $hero = $conversion->blocks[0];

    expect($conversion->blockTypes())->toBe(['hero', 'features', 'stats', 'faq'])
        ->and($hero['data'])->toMatchArray([
            'heading' => 'Ai nevoie de un magazin online?',
            'subheading' => 'Oferim soluții personalizate și ușor de utilizat pentru a transforma visul tău de afacere online în realitate.',
            'image_asset_id' => $media->assets[1867],
            'layout' => 'split',
            'primary_cta' => ['label' => 'Cere Oferta', 'url' => '/contact/'],
            'secondary_cta' => ['label' => 'Vezi Portofoliul', 'url' => '/portofoliu/'],
        ]);
});

it('gives every later heading to a block or the rich text, never another hero', function () {
    $conversion = elementorConverter()->convert(elementorFixture('elementor-page-magazin-online'), 'Creare magazin online');

    expect(blocksOfType($conversion, 'hero'))->toHaveCount(1)
        ->and(blocksOfType($conversion, 'features')[0]['data']['heading'])->toBe('Creare magazin online')
        // The FAQ section's own heading and line, a rich text before the
        // questions: folded into the faq, the line its intro.
        ->and(blocksOfType($conversion, 'rich_text'))->toBe([])
        ->and(blocksOfType($conversion, 'faq')[0]['data']['heading'])->toBe('Întrebări frecvente')
        ->and(blocksOfType($conversion, 'faq')[0]['data']['intro'])->toStartWith('Oferim servicii SEO atat pentru clienti din Iasi')
        ->and($conversion->report->droppedCount('rich text (the FAQ\'s heading and intro)'))->toBe(1)
        // The form ending the page: the footer's call to talk takes its place.
        ->and(blocksOfType($conversion, 'quote_form'))->toBe([])
        ->and($conversion->report->droppedCount('form (the footer\'s call instead)'))->toBe(1)
        // "Serviciile noastre", "FAQs", "Contacteaza-ne": small upper-case headings over the real ones.
        ->and($conversion->report->droppedCount('heading (eyebrow)'))->toBe(3)
        ->and($conversion->report->droppedCount('form section: contact details'))->toBe(3);
});

it('moves a page\'s opening line, alone under the hero, into the hero', function () {
    $elements = elementorFixture('elementor-page-magazin-online');
    array_splice($elements, 1, 0, [['id' => 'intro', 'elType' => 'container', 'settings' => [], 'elements' => [
        ['id' => 'intro-text', 'elType' => 'widget', 'widgetType' => 'text-editor', 'settings' => ['editor' => '<p>Reprezinta o metoda de imbunatatire a traficului.</p>']],
    ]]]);

    $conversion = elementorConverter()->convert($elements, 'Creare magazin online');

    expect($conversion->blockTypes())->toBe(['hero', 'features', 'stats', 'faq'])
        ->and($conversion->blocks[0]['data']['subheading'])->toEndWith("\n\nReprezinta o metoda de imbunatatire a traficului.")
        ->and($conversion->report->droppedCount('rich text (into the hero)'))->toBe(1);
});

it('drops a picture standing alone right after the questions', function () {
    $elements = elementorFixture('elementor-page-magazin-online');
    // Before the form section (the last): an image on its own after the FAQ.
    array_splice($elements, -1, 0, [['id' => 'faq-art', 'elType' => 'container', 'settings' => [], 'elements' => [
        ['id' => 'faq-art-img', 'elType' => 'widget', 'widgetType' => 'image', 'settings' => ['image' => ['id' => 1867, 'url' => 'https://www.webis.ro/wp-content/uploads/faq.png']]],
    ]]]);

    $conversion = elementorConverter(elementorMedia([1867]))->convert($elements, 'Creare magazin online');

    expect($conversion->blockTypes())->toBe(['hero', 'features', 'stats', 'faq'])
        ->and($conversion->report->droppedCount('image (beside the FAQ)'))->toBeGreaterThanOrEqual(1);
});

it('drops a form further up the page too, the text after it kept', function () {
    $elements = elementorFixture('elementor-page-magazin-online');
    $elements[] = ['id' => 'after', 'elType' => 'container', 'settings' => [], 'elements' => [
        ['id' => 'after-text', 'elType' => 'widget', 'widgetType' => 'text-editor', 'settings' => ['editor' => '<p>Un paragraf după formular.</p>']],
    ]];

    $conversion = elementorConverter()->convert($elements, 'Creare magazin online');

    expect($conversion->blockTypes())->not->toContain('quote_form')
        ->and($conversion->blocks[array_key_last($conversion->blocks)]['data']['content_html'])->toContain('Un paragraf după formular.')
        ->and($conversion->report->droppedCount('form (the footer\'s call instead)'))->toBe(1);
});

it('takes an article\'s hero from its title, which the theme printed as the h1', function () {
    $conversion = elementorConverter()->convert(elementorFixture('elementor-article-2022'), 'Vizualizarea de pagini cu Elementor - avantaje si dezavantaje!', titleIsHeading: true);
    $html = blocksOfType($conversion, 'rich_text')[0]['data']['content_html'];

    expect($conversion->blockTypes())->toBe(['hero', 'rich_text'])
        ->and($conversion->blocks[0]['data']['heading'])->toBe('Vizualizarea de pagini cu Elementor - avantaje si dezavantaje!')
        // Headings drawn as bold text editors are headings again.
        ->and($html)->toStartWith('<h2 id="ce-este-elementor">Ce este Elementor?</h2>')
        ->and($html)->toContain('<h2 id="care-sunt-dezavantajele-utilizarii-unui-elementor">')
        // The old table of contents goes, with its heading: the new template builds its own.
        ->and($html)->not->toContain('Ce poti invata')
        ->and($conversion->report->droppedCount('widget:icon-list (table of contents)'))->toBe(1)
        ->and($conversion->report->droppedCount('heading (table of contents)'))->toBe(1)
        ->and($conversion->report->droppedCount('widget:video (hosted)'))->toBe(1)
        ->and($conversion->report->unresolvedCount('image'))->toBe(3);
});

it('merges consecutive text and headings into one rich text block', function () {
    $conversion = elementorConverter()->convert(elementorFixture('elementor-article-2023'), 'Cum să ai un site web performant pentru cabinetul tău de avocatură', titleIsHeading: true);
    $html = blocksOfType($conversion, 'rich_text')[0]['data']['content_html'];

    expect($conversion->blockTypes())->toBe(['hero', 'rich_text', 'faq'])
        // The article drew its sections in h4: the largest size used becomes h2.
        ->and(substr_count($html, '<h2 '))->toBe(2)
        ->and($html)->not->toContain('<h4')
        ->and($html)->toContain('<p>Un site web eficient poate fi cheia succesului');
});

it('turns a toggle or accordion into questions with plain-text answers', function () {
    $faq = blocksOfType(elementorConverter()->convert(elementorFixture('elementor-article-2023'), 'Avocați', titleIsHeading: true), 'faq')[0]['data'];

    expect($faq['heading'])->toBe('Q&A')
        ->and($faq['items'][0]['question'])->toBe('Cât costă crearea unui site web pentru avocați?')
        ->and($faq['items'][0]['answer'])->toStartWith('Costul serviciilor noastre pentru crearea site-ului depinde')
        ->and(collect($faq['items'])->pluck('answer')->implode(' '))->not->toContain('<')->not->toContain('w-full');
});

it('turns image boxes into features and counters into stats', function () {
    $conversion = elementorConverter()->convert(elementorFixture('elementor-page-magazin-online'), 'Creare magazin online');
    $features = blocksOfType($conversion, 'features')[0]['data'];
    $stats = blocksOfType($conversion, 'stats')[0]['data'];

    expect(array_column($features['items'], 'title'))->toBe(['Design responsive', 'Optimizare SEO', 'Produse fara limita', 'Generare factura', 'Plati online & ramburs', 'Simplu de utilizat'])
        ->and($features['items'][0]['text'])->toStartWith('Clientii vor putea accesa site-ul')
        ->and($stats['items'])->toBe([
            ['value' => '7+', 'label' => 'Ani de experienta', 'note' => null],
            ['value' => '237', 'label' => 'Proiecte finalizate', 'note' => null],
            ['value' => '184', 'label' => 'Clienti fericiti', 'note' => null],
            ['value' => '1820', 'label' => 'Cafele baute', 'note' => null],
        ]);
});

it('turns price tables into pricing tiers and progress bars into shares', function () {
    $conversion = elementorConverter()->convert(elementorFixture('elementor-page-mentenanta'), 'Mentenanta site wordpress');
    $pricing = blocksOfType($conversion, 'pricing_tiers')[0]['data'];
    $stats = blocksOfType($conversion, 'stats')[0]['data'];

    expect($conversion->blockTypes())->toBe(['hero', 'pricing_tiers', 'stats', 'rich_text'])
        ->and($pricing['heading'])->toBe('Pachete Mentenanta')
        ->and(array_column($pricing['tiers'], 'name'))->toBe(['Basic', 'Standard', 'Premium'])
        ->and($pricing['tiers'][0])->toMatchArray(['price' => '40 €', 'period' => 'Lunar'])
        ->and($pricing['tiers'][0]['features'])->toContain('Actualizare platforma')
        // The bars' heading heads the figures; each bar's share is its figure.
        ->and($stats['heading'])->toBe('Cele mai infectate site-uri')
        ->and(array_column($stats['items'], 'value'))->toBe(['78%', '12%', '5%', '3%', '1%'])
        ->and(array_column($stats['items'], 'label'))->toBe(['Wordpress', 'Joomla', 'Alte platforme', 'Magento', 'Drupal'])
        ->and($conversion->report->droppedCount('widget:progress'))->toBe(0);
});

it('reads an h1 hero, an animated headline, a popup button and a logo carousel', function () {
    $media = elementorMedia([2105, 1279, 1280, 1281]);
    $conversion = elementorConverter($media)->convert(elementorFixture('elementor-page-home-opening'), 'Acasa');
    $hero = $conversion->blocks[0]['data'];

    expect($conversion->blockTypes())->toBe(['hero', 'gallery'])
        ->and($hero)->toMatchArray([
            'heading' => 'Creare site Iasi | Web design Iasi | Logo design',
            'subheading' => 'Propulsam businessul tau catre succes prin Web Design, Logo Design, Web Development',
            'image_asset_id' => $media->assets[2105],
            'primary_cta' => ['label' => 'Solicita oferta', 'url' => '/contact/'],
        ])
        ->and(array_column($conversion->blocks[1]['data']['images'], 'asset_id'))->toBe([$media->assets[1279], $media->assets[1280], $media->assets[1281]])
        // Clouds and planets, the review-widget shortcode and a spacer.
        ->and($conversion->report->dropped())->toMatchArray(['widget:image (decorative)' => 3, 'widget:shortcode' => 1, 'widget:spacer' => 1]);
});

it('makes image + text, a call to action, a video and an inline image from the matching widgets', function () {
    // Real widgets from the old pages, put together in one tree: an image
    // column beside a text column (the 2022 articles), a heading, text and
    // button (the service pages), a YouTube video and a plain icon list.
    $media = elementorMedia([12452, 12441]);
    $widget = fn (string $type, array $settings) => ['id' => uniqid(), 'elType' => 'widget', 'widgetType' => $type, 'settings' => $settings, 'elements' => []];
    $column = fn (array ...$widgets) => ['id' => uniqid(), 'elType' => 'column', 'settings' => [], 'elements' => $widgets];
    $section = fn (array ...$children) => ['id' => uniqid(), 'elType' => 'section', 'settings' => [], 'elements' => $children];

    $data = [
        $section($column($widget('heading', ['title' => 'Care sunt avantajele utilizarii unui Elementor?']))),
        $section(
            $column($widget('image', ['image' => ['id' => 12452, 'url' => 'https://www.webis.ro/wp-content/uploads/2022/02/tricou.jpg']])),
            $column($widget('text-editor', ['editor' => '<ul><li>Este foarte usor de utilizat;</li><li>Ajuta la dezvoltarea mult mai rapida a site-ului;</li></ul>'])),
        ),
        $section($column(
            $widget('text-editor', ['editor' => '<p>Elementor este un plugin WordPress.</p>']),
            $widget('image', ['image' => ['id' => 12441, 'url' => 'https://www.webis.ro/wp-content/uploads/2022/02/A.jpg']]),
            $widget('icon-list', ['icon_list' => [['text' => 'Blog'], ['text' => 'Magazin online', 'link' => ['url' => '/creare-magazin-online']]]]),
        )),
        $section($column(
            $widget('heading', ['title' => 'Nu stii de unde sa incepi?']),
            $widget('text-editor', ['editor' => '<p>Sună acum pentru a transforma ideile tale într-un site web de impact.</p>']),
            $widget('button', ['text' => 'Cere oferta', 'link' => ['url' => 'tel:0770 700 607']]),
        )),
        $section($column(
            $widget('heading', ['title' => 'Cum functioneaza Elementor?']),
            $widget('video', ['video_type' => 'youtube', 'youtube_url' => 'https://www.youtube.com/watch?v=XHOmBV4js_E']),
        )),
    ];

    $conversion = elementorConverter($media)->convert($data, 'Elementor', titleIsHeading: true);
    [, $imageText, $richText, $cta, $video] = $conversion->blocks;

    expect($conversion->blockTypes())->toBe(['hero', 'image_text', 'rich_text', 'cta_banner', 'video'])
        ->and($imageText['data'])->toMatchArray(['heading' => 'Care sunt avantajele utilizarii unui Elementor?', 'image_asset_id' => $media->assets[12452], 'image_position' => 'left'])
        ->and($imageText['data']['content_html'])->toBe('<ul><li><p>Este foarte usor de utilizat;</p></li><li><p>Ajuta la dezvoltarea mult mai rapida a site-ului;</p></li></ul>')
        ->and($richText['data']['content_html'])->toBe('<p>Elementor este un plugin WordPress.</p><figure class="rt-image" data-asset-id="'.$media->assets[12441].'"></figure><ul><li><p>Blog</p></li><li><p><a href="/creare-magazin-online/">Magazin online</a></p></li></ul>')
        ->and($cta['data'])->toMatchArray([
            'heading' => 'Nu stii de unde sa incepi?',
            'text' => 'Sună acum pentru a transforma ideile tale într-un site web de impact.',
            'primary_cta' => ['label' => 'Cere oferta', 'url' => 'tel:0770700607'],
        ])
        ->and($video['data'])->toMatchArray(['title' => 'Cum functioneaza Elementor?', 'youtube_id' => 'XHOmBV4js_E']);
});

it('counts the decorative widgets it drops', function () {
    $widget = fn (string $type, array $settings = []) => ['id' => uniqid(), 'elType' => 'widget', 'widgetType' => $type, 'settings' => $settings, 'elements' => []];
    $data = [['id' => 's', 'elType' => 'container', 'settings' => [], 'elements' => [
        $widget('text-editor', ['editor' => '<p>Text.</p>']),
        $widget('spacer'), $widget('divider'), $widget('google_maps'), $widget('social-icons'),
        $widget('hotspot', ['hotspot' => [['hotspot_tooltip_content' => '<a href="https://pmcwebis.wpcomstaging.com/dlep/">DLEP</a>']]]),
        $widget('progress'), $widget('loop-carousel'), $widget('theme-post-content'), $widget('shortcode', ['shortcode' => '[trustindex]']),
        $widget('image', ['image' => ['id' => 35, 'url' => 'https://webis.ro/wp-content/uploads/2024/03/nori-8.png'], 'opacity' => ['size' => 1]]),
        ['id' => 'm', 'elType' => 'container', 'settings' => ['hide_desktop' => 'hidden-desktop'], 'elements' => [$widget('text-editor', ['editor' => '<p>Copia de pe telefon.</p>'])]],
    ]]];

    $report = elementorConverter()->convert($data, 'Pagina')->report;

    expect($report->dropped())->toMatchArray([
        'widget:spacer' => 1, 'widget:divider' => 1, 'widget:google_maps' => 1, 'widget:social-icons' => 1,
        'widget:hotspot' => 1, 'widget:progress' => 1, 'widget:loop-carousel' => 1, 'widget:theme-post-content' => 1,
        'widget:shortcode' => 1, 'widget:image (decorative)' => 1, 'hidden on desktop: container' => 1,
        'link:staging (in a dropped element)' => 1,
    ]);
});

it('returns only blocks that pass the registry\'s validation', function (string $fixture, bool $titleIsHeading) {
    $conversion = elementorConverter(elementorMedia([1867, 2105, 1279, 1280, 1281, 12452]))->convert(elementorFixture($fixture), 'Titlu', $titleIsHeading);

    expect($conversion->blocks)->not->toBeEmpty()
        ->and(app(BlockRegistry::class)->validate($conversion->blocks))->toHaveCount(count($conversion->blocks))
        ->and(array_filter(array_keys($conversion->report->dropped()), fn (string $kind): bool => str_starts_with($kind, 'invalid:')))->toBe([]);
})->with([
    ['elementor-page-magazin-online', false],
    ['elementor-page-mentenanta', false],
    ['elementor-page-home-opening', false],
    ['elementor-article-2022', true],
    ['elementor-article-2023', true],
]);

it('reports a block it cannot make valid instead of returning it', function () {
    $data = [['id' => 's', 'elType' => 'container', 'settings' => [], 'elements' => [
        ['id' => 'v', 'elType' => 'widget', 'widgetType' => 'video', 'settings' => ['video_type' => 'youtube', 'youtube_url' => 'https://www.youtube.com/@webis'], 'elements' => []],
    ]]];

    $conversion = elementorConverter()->convert($data, 'Video');

    expect($conversion->blockTypes())->toBe(['hero'])
        ->and($conversion->report->droppedCount('invalid:video'))->toBe(1)
        ->and($conversion->report->notes()[0])->toContain('Introdu un link YouTube valid.');
});
