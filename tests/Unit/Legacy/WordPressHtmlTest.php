<?php

use App\Support\Legacy\ConversionReport;
use App\Support\Legacy\LegacyUrlIndex;
use App\Support\Legacy\MediaResolver;
use App\Support\Legacy\WordPressHtml;
use App\Support\RichText\RichText;

/**
 * Media resolver for these tests: one known image, attachment #7.
 */
function wpHtmlMedia(): MediaResolver
{
    return new class implements MediaResolver
    {
        public function assetIdForUrl(string $url): ?int
        {
            return str_contains($url, '/wp-content/uploads/2024/03/ServiciuWeb_1080x') ? 70 : null;
        }

        public function assetIdForAttachment(int $attachmentId): ?int
        {
            return $attachmentId === 7 ? 70 : null;
        }
    };
}

function wpHtml(): WordPressHtml
{
    return new WordPressHtml(
        wpHtmlMedia(),
        new LegacyUrlIndex(['/contact' => '/contact/', '/creare-site-iasi' => '/', '/ce-este-seo-on-page' => '/ce-este-seo-on-page/'], [21 => '/contact/']),
        app(RichText::class),
    );
}

/**
 * @return array{0: string, 1: ConversionReport}
 */
function wpClean(string $html): array
{
    $report = new ConversionReport;

    return [wpHtml()->clean($html, $report)['html'], $report];
}

it('demotes h1 to h2 and h5/h6 to h4', function () {
    [$html] = wpClean('<h1>Titlu</h1><h3>Trei</h3><h5>Cinci</h5><h6>Șase</h6>');

    expect($html)->toBe('<h2>Titlu</h2><h3>Trei</h3><h4>Cinci</h4><h4>Șase</h4>');
});

it('unwraps divs and spans and keeps their text, without running lines together', function () {
    [$html] = wpClean('<div class="elementor-element"><div><span style="color:red">Primul</span> rând</div><div>Al doilea rând</div></div><section><p>Paragraf</p></section>');

    expect($html)->toBe('<p>Primul rând</p><p>Al doilea rând</p><p>Paragraf</p>');
});

it('keeps a bold, italic or underlined span as the mark', function () {
    [$html] = wpClean('<p><span style="font-weight: bold;">Gros</span> și <span style="text-decoration: underline;">subliniat</span></p>');

    expect($html)->toBe('<p><strong>Gros</strong> și <u>subliniat</u></p>');
});

it('strips shortcodes and Gutenberg comments, and counts them', function () {
    [$html, $report] = wpClean('<!-- wp:paragraph --><p>Text [caption id="x"]cu legendă[/caption] aici</p><!-- /wp:paragraph -->[trustindex no-registration=google]');

    expect($html)->toBe('<p>Text cu legendă aici</p>')
        ->and($report->droppedCount('shortcode'))->toBe(2)
        ->and($report->droppedCount('wp-block-comment'))->toBe(2);
});

it('turns a mapped image into an asset image and drops and counts an unmapped one', function () {
    $report = new ConversionReport;
    $segments = wpHtml()->toSegments('<p>Înainte</p><p><img src="https://www.webis.ro/wp-content/uploads/2024/03/ServiciuWeb_1080x-300x300.jpg" class="wp-image-7" width="300"></p><p>Text <img src="https://www.webis.ro/wp-content/uploads/2021/04/pierdut.jpg"> după</p>', $report);

    expect($segments)->toHaveCount(1)
        ->and($segments[0]['doc']['content'])->toBe([
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Înainte']]],
            ['type' => 'assetImage', 'attrs' => ['assetId' => 70]],
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Text după']]],
        ])
        ->and($report->unresolvedItems())->toBe(['image' => ['https://www.webis.ro/wp-content/uploads/2021/04/pierdut.jpg']]);
});

it('keeps a figure caption on the asset image', function () {
    $segments = wpHtml()->toSegments('<figure class="wp-block-image"><a href="https://www.webis.ro/wp-content/uploads/2024/03/ServiciuWeb_1080x.jpg"><img src="https://www.webis.ro/wp-content/uploads/2024/03/ServiciuWeb_1080x-1024x1024.jpg"></a><figcaption>Servicii <strong>web</strong></figcaption></figure>', new ConversionReport);

    expect($segments[0]['doc']['content'])->toBe([['type' => 'assetImage', 'attrs' => ['assetId' => 70, 'caption' => 'Servicii web']]]);
});

it('splits the document at a YouTube iframe and drops other iframes', function () {
    $report = new ConversionReport;
    $segments = wpHtml()->toSegments('<p>Unu</p><div class="video"><iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ?rel=0" width="560"></iframe></div><p>Doi</p><iframe src="https://maps.google.com/maps?q=iasi"></iframe>', $report);

    expect(array_column($segments, 'type'))->toBe(['doc', 'video', 'doc'])
        ->and($segments[1]['url'])->toBe('https://www.youtube.com/watch?v=dQw4w9WgXcQ')
        ->and($segments[2]['doc']['content'][0]['content'][0]['text'])->toBe('Doi')
        ->and($report->droppedCount('element:iframe'))->toBe(1);
});

it('rewrites internal links to their final trailing-slash target', function () {
    [$html, $report] = wpClean('<p><a href="https://www.webis.ro/contact">Contact</a>, <a href="http://webis.ro/creare-site-iasi/">acasă</a>, <a href="/ce-este-seo-on-page/#seo">SEO</a>, <a href="/?p=21">id</a> și <a href="/nu-mai-exista/">dispărut</a></p>');

    expect($html)->toBe('<p><a href="/contact/">Contact</a>, <a href="/">acasă</a>, <a href="/ce-este-seo-on-page/#seo">SEO</a>, <a href="/contact/">id</a> și dispărut</p>')
        ->and($report->unresolvedItems())->toBe(['link' => ['/nu-mai-exista/']]);
});

it('unlinks the stale staging domain and counts it', function () {
    [$html, $report] = wpClean('<ul><li><a style="color: #262a6f;" href="https://pmcwebis.wpcomstaging.com/dlep/" target="_blank" rel="noopener">Direcția Locală</a></li></ul>');

    expect($html)->toBe('<ul><li><p>Direcția Locală</p></li></ul>')
        ->and($report->droppedCount('link:staging'))->toBe(1);
});

it('strips style, class, id, data and size attributes', function () {
    [$html] = wpClean('<p class="lead" id="x" style="color:red" data-id="7fe6092">Text <a href="https://example.com" class="btn" rel="nofollow" target="_blank">extern</a></p><ol start="3"><li>trei</li></ol>');

    expect($html)->toBe('<p>Text <a href="https://example.com" rel="nofollow">extern</a></p><ol start="3"><li><p>trei</p></li></ol>');
});

it('normalises non-breaking spaces and removes empty paragraphs', function () {
    [$html] = wpClean('<p>&nbsp;</p><p>Un&nbsp;text&nbsp; cu   spații</p><p><br></p><p> </p><p><br>Rând<br></p>');

    expect($html)->toBe('<p>Un text cu spații</p><p>Rând</p>');
});

it('lets a table through', function () {
    $doc = wpHtml()->toSegments('<table class="x"><tbody><tr><th>Pachet</th><th>Preț</th></tr><tr><td>Basic</td><td colspan="2" style="width:50%">40 €</td></tr></tbody></table>', new ConversionReport)[0]['doc'];

    expect(app(RichText::class)->render($doc))
        ->toBe('<table><tbody><tr><th><p>Pachet</p></th><th><p>Preț</p></th></tr><tr><td><p>Basic</p></td><td colspan="2"><p>40 €</p></td></tr></tbody></table>');
});

it('drops a script and counts it', function () {
    [$html, $report] = wpClean("<p>Mulțumim!</p><script>\n  fbq('track', 'Lead');\n</script>");

    expect($html)->toBe('<p>Mulțumim!</p>')
        ->and($report->droppedCount('element:script'))->toBe(1);
});

it('wraps a classic body with no markup into paragraphs', function () {
    [$html] = wpClean("Logo-ul Tekgren a fost creat\npentru panouri solare.\n\nAl doilea paragraf.");

    expect($html)->toBe('<p>Logo-ul Tekgren a fost creat<br>pentru panouri solare.</p><p>Al doilea paragraf.</p>');
});

it('reads plain text with one line per block', function () {
    expect(WordPressHtml::plainText('<div class="markdown"><p>Costul&nbsp;depinde.</p><ul><li>Unu</li><li>Doi</li></ul></div><script>x()</script>'))
        ->toBe("Costul depinde.\n– Unu\n– Doi");
});
