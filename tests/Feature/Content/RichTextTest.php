<?php

use App\Models\Asset;
use App\Models\Post;
use App\Support\RichText\RichText;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param  list<array<string, mixed>>  $content
 * @return array<string, mixed>
 */
function rtDoc(array $content): array
{
    return ['type' => 'doc', 'content' => $content];
}

/**
 * @param  list<array<string, mixed>>|string  $content
 * @return array<string, mixed>
 */
function rtPara(array|string $content): array
{
    return ['type' => 'paragraph', 'content' => is_string($content) ? [['type' => 'text', 'text' => $content]] : $content];
}

/**
 * @return array<string, mixed>
 */
function rtHeading(int $level, string $text): array
{
    return ['type' => 'heading', 'attrs' => ['level' => $level], 'content' => [['type' => 'text', 'text' => $text]]];
}

/**
 * @param  array<string, mixed>  $doc
 */
function renderRich(array $doc): string
{
    $richText = app(RichText::class);

    return $richText->render($richText->sanitize($doc));
}

it('renders the allowed formatting', function () {
    $html = renderRich(rtDoc([
        rtHeading(2, 'Ce primești'),
        rtPara([
            ['type' => 'text', 'text' => 'Site '],
            ['type' => 'text', 'text' => 'rapid', 'marks' => [['type' => 'bold']]],
            ['type' => 'text', 'text' => ' și '],
            ['type' => 'text', 'text' => 'optimizat', 'marks' => [['type' => 'italic']]],
        ]),
        ['type' => 'bulletList', 'content' => [
            ['type' => 'listItem', 'content' => [rtPara('SEO tehnic')]],
        ]],
        ['type' => 'callout', 'attrs' => ['variant' => 'tip'], 'content' => [rtPara('Sfat')]],
    ]));

    expect($html)
        ->toContain('<h2 id="ce-primesti">Ce primești</h2>')
        ->toContain('<strong>rapid</strong>')
        ->toContain('<em>optimizat</em>')
        ->toContain('<ul><li><p>SEO tehnic</p></li></ul>')
        ->toContain('<aside class="callout callout-tip"><p>Sfat</p></aside>');
});

it('drops unknown nodes together with their content', function () {
    $html = renderRich(rtDoc([
        rtPara('Înainte'),
        ['type' => 'script', 'content' => [['type' => 'text', 'text' => 'alert(1)']]],
        ['type' => 'iframe', 'attrs' => ['src' => 'https://evil.test']],
        rtPara('După'),
    ]));

    expect($html)->toBe('<p>Înainte</p><p>După</p>');
});

it('escapes text, so HTML typed into the editor stays text', function () {
    expect(renderRich(rtDoc([rtPara('<img src=x onerror=alert(1)>')])))
        ->toBe('<p>&lt;img src=x onerror=alert(1)&gt;</p>');
});

it('removes unsafe links but keeps their text', function (string $href) {
    $html = renderRich(rtDoc([rtPara([
        ['type' => 'text', 'text' => 'click', 'marks' => [['type' => 'link', 'attrs' => ['href' => $href]]]],
    ])]));

    expect($html)->toBe('<p>click</p>');
})->with([
    'javascript:' => 'javascript:alert(1)',
    'mixed-case javascript' => ' JaVaScRiPt:alert(1)',
    'data:' => 'data:text/html;base64,PHNjcmlwdD4=',
    'protocol-relative' => '//evil.test/x',
]);

it('keeps internal links plain and opens external ones safely', function () {
    config(['app.url' => 'https://www.webis.ro']);

    $html = renderRich(rtDoc([rtPara([
        ['type' => 'text', 'text' => 'servicii', 'marks' => [['type' => 'link', 'attrs' => ['href' => '/servicii', 'target' => '_blank', 'rel' => 'nofollow']]]],
        ['type' => 'text', 'text' => ' '],
        ['type' => 'text', 'text' => 'acasă', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'https://webis.ro/']]]],
        ['type' => 'text', 'text' => ' '],
        ['type' => 'text', 'text' => 'partener', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'https://exemplu.ro', 'rel' => 'sponsored onclick']]]],
    ])]));

    expect($html)
        ->toContain('<a href="/servicii">servicii</a>')
        ->toContain('<a href="https://webis.ro/">acasă</a>')
        ->toContain('<a href="https://exemplu.ro" target="_blank" rel="noopener noreferrer sponsored">partener</a>');
});

it('keeps headings between h2 and h4 with unique ids', function () {
    $richText = app(RichText::class);
    $body = $richText->sanitize(rtDoc([
        rtHeading(1, 'Titlu'),
        rtHeading(3, 'Prețuri'),
        rtHeading(3, 'Prețuri'),
        rtHeading(6, 'Detaliu'),
    ]));

    expect($richText->render($body))
        ->toContain('<h2 id="titlu">')
        ->toContain('<h3 id="preturi">')
        ->toContain('<h3 id="preturi-2">')
        ->toContain('<h4 id="detaliu">')
        ->and($richText->toc($body))->toBe([
            ['id' => 'titlu', 'text' => 'Titlu', 'level' => 2],
            ['id' => 'preturi', 'text' => 'Prețuri', 'level' => 3],
            ['id' => 'preturi-2', 'text' => 'Prețuri', 'level' => 3],
        ]);
});

it('renders media-library images as placeholders and lists their ids', function () {
    $richText = app(RichText::class);
    $body = $richText->sanitize(rtDoc([
        ['type' => 'assetImage', 'attrs' => ['assetId' => 7, 'caption' => 'Pagina <b>principală</b>']],
        ['type' => 'assetImage', 'attrs' => ['assetId' => 'x']],
    ]));

    expect($richText->render($body))
        ->toBe('<figure class="rt-image" data-asset-id="7"><figcaption>Pagina &lt;b&gt;principală&lt;/b&gt;</figcaption></figure>')
        ->and($richText->assetIds($body))->toBe([7]);
});

it('renders tables', function () {
    $cell = fn (string $type, string $text) => ['type' => $type, 'content' => [rtPara($text)]];

    $html = renderRich(rtDoc([['type' => 'table', 'content' => [
        ['type' => 'tableRow', 'content' => [$cell('tableHeader', 'Pachet'), $cell('tableHeader', 'Preț')]],
        ['type' => 'tableRow', 'content' => [$cell('tableCell', 'Start'), $cell('tableCell', '2.500 lei')]],
    ]]]));

    expect($html)->toContain('<table><tbody><tr><th><p>Pachet</p></th>')
        ->toContain('<td><p>2.500 lei</p></td>');
});

it('derives html, toc and reading time when a post body is saved', function () {
    $words = implode(' ', array_fill(0, 450, 'cuvânt'));

    $post = Post::factory()->create(['body' => rtDoc([rtHeading(2, 'Introducere'), rtPara($words)])]);

    expect($post->body_html)->toStartWith('<h2 id="introducere">Introducere</h2><p>cuvânt')
        ->and($post->toc)->toBe([['id' => 'introducere', 'text' => 'Introducere', 'level' => 2]])
        ->and($post->reading_minutes)->toBe(3);
});

it('tracks images embedded in a post body', function () {
    $image = Asset::factory()->create();

    $post = Post::factory()->create(['body' => rtDoc([
        ['type' => 'assetImage', 'attrs' => ['assetId' => $image->id]],
    ])]);

    expect($image->usages()->sole()->field)->toBe('body')
        ->and($image->usages()->sole()->usable_id)->toBe($post->id);
});

/**
 * HTML → fromHtml → render: what an imported body looks like on the page.
 */
function roundTrip(string $html): string
{
    $richText = app(RichText::class);

    return $richText->render($richText->sanitize($richText->fromHtml($html)));
}

it('parses HTML into the same nodes the editor makes', function () {
    $richText = app(RichText::class);

    expect($richText->fromHtml('<h2>Ce primești</h2><p>Site <strong>rapid</strong> și <em>optimizat</em></p>'))->toBe(rtDoc([
        ['type' => 'heading', 'attrs' => ['level' => 2, 'id' => 'ce-primesti'], 'content' => [['type' => 'text', 'text' => 'Ce primești']]],
        rtPara([
            ['type' => 'text', 'text' => 'Site '],
            ['type' => 'text', 'text' => 'rapid', 'marks' => [['type' => 'bold']]],
            ['type' => 'text', 'text' => ' și '],
            ['type' => 'text', 'text' => 'optimizat', 'marks' => [['type' => 'italic']]],
        ]),
    ]))->and($richText->fromHtml('  '))->toBe(RichText::emptyDocument());
});

it('round-trips representative HTML through fromHtml, sanitize and render', function (string $html, string $expected) {
    expect(roundTrip($html))->toBe($expected);
})->with([
    'headings and marks' => ['<h2>Titlu</h2><p><b>Gros</b>, <i>înclinat</i>, <u>subliniat</u>, <s>tăiat</s> și <code>cod</code></p>', '<h2 id="titlu">Titlu</h2><p><strong>Gros</strong>, <em>înclinat</em>, <u>subliniat</u>, <s>tăiat</s> și <code>cod</code></p>'],
    'lists' => ['<ul><li><p>Unu</p></li><li><p>Doi</p></li></ul><ol start="3"><li><p>Trei</p></li></ol>', '<ul><li><p>Unu</p></li><li><p>Doi</p></li></ul><ol start="3"><li><p>Trei</p></li></ol>'],
    'quote, rule and break' => ['<blockquote><p>Citat</p></blockquote><hr><p>Rând<br>nou</p>', '<blockquote><p>Citat</p></blockquote><hr><p>Rând<br>nou</p>'],
    'links' => ['<p><a href="/contact/">Contact</a> și <a href="https://example.com" rel="nofollow">extern</a></p>', '<p><a href="/contact/">Contact</a> și <a href="https://example.com" target="_blank" rel="noopener noreferrer nofollow">extern</a></p>'],
    'table' => ['<table><tbody><tr><th><p>Pachet</p></th></tr><tr><td colspan="2"><p>Basic</p></td></tr></tbody></table>', '<table><tbody><tr><th><p>Pachet</p></th></tr><tr><td colspan="2"><p>Basic</p></td></tr></tbody></table>'],
    'code block' => ['<pre><code class="language-php">echo 1;</code></pre>', '<pre><code class="language-php">echo 1;</code></pre>'],
]);

it('drops from HTML what the allowlist does not allow', function () {
    expect(roundTrip('<h1>Prea mare</h1><p>Text<script>alert(1)</script></p><iframe src="https://evil.test"></iframe><p><a href="javascript:alert(1)">link</a></p>'))
        ->toBe('<p>Prea mare</p><p>Text</p><p>link</p>');
});
