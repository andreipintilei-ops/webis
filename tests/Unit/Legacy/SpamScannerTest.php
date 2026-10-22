<?php

use App\Support\Legacy\SpamScanner;

function spamTypes(array $result): array
{
    return array_map(fn (array $hit): string => $hit['type'].':'.$hit['detail'], $result['hits']);
}

it('flags a hidden link to a casino', function () {
    $result = (new SpamScanner)->scan('<p>Servicii web</p><div style="display:none"><a href="https://best-casino.example/bonus" title="online casino">x</a></div>');

    expect(spamTypes($result))->toContain('hidden_css:display:none', 'outbound_link:best-casino.example', 'spam_word:casino')
        ->and($result['domains'])->toBe(['best-casino.example' => 1]);
});

it('does not flag an ordinary article with allowed links', function () {
    $html = '<h2>Ce este SEO?</h2><p>Optimizarea pentru motoarele de căutare, explicată. '
        .'Vezi <a href="https://www.webis.ro/contact/">contact</a>, <a href="/portofoliu/">portofoliu</a>, '
        .'<a href="https://www.facebook.com/webis">Facebook</a> și <a href="mailto:office@webis.ro">e-mail</a>.</p>'
        .'<p style="font-size: 0.9em; line-height: 1px">Notă</p><img src="https://www.webis.ro/wp-content/uploads/2021/04/seo.jpg" alt="SEO on-page">';

    $result = (new SpamScanner)->scan($html);

    expect($result['hits'])->toBe([])
        ->and($result['domains'])->toBe(['facebook.com' => 1]);
});

it('allows the clients\' own sites passed in', function () {
    $html = '<a href="https://zzeleno.ro/">zzeleno.ro</a>';

    expect(spamTypes((new SpamScanner)->scan($html)))->toBe(['outbound_link:zzeleno.ro'])
        ->and((new SpamScanner(['https://zzeleno.ro/']))->scan($html)['hits'])->toBe([]);
});

it('flags active content and its source', function () {
    $html = '<script src="https://evil.example/x.js"></script><iframe src="https://www.youtube.com/embed/abc"></iframe>'
        .'<object data="x.swf"></object><embed src="x.swf"><img src="a.jpg" onerror="alert(1)">';

    expect(spamTypes((new SpamScanner)->scan($html)))
        ->toContain('script:evil.example', 'iframe:youtube.com', 'object:', 'embed:', 'event_handler:onerror');
});

it('flags every way of hiding content', function (string $style, string $rule) {
    expect(spamTypes((new SpamScanner)->scan("<div style=\"{$style}\">x</div>")))->toContain("hidden_css:{$rule}");
})->with([
    ['display: none', 'display:none'],
    ['visibility:hidden', 'visibility:hidden'],
    ['text-indent:-9999px', 'text-indent'],
    ['font-size:0', 'font-size:0'],
    ['height:1px;overflow:hidden', 'height:1px'],
    ['position:absolute; left:-5000px', 'off-screen'],
    ['top:-999px; position: absolute', 'off-screen'],
]);

it('does not mistake ordinary sizes for hiding', function (string $style) {
    expect((new SpamScanner)->scan("<div style=\"{$style}\">x</div>")['hits'])->toBe([]);
})->with([
    'font-size: 0.8em', 'line-height:1px', 'max-height:1px', 'text-indent: 10px', 'position:absolute; left:-5px',
]);

it('flags payload vocabulary in attributes as well as text', function () {
    $hits = spamTypes((new SpamScanner)->scan('<img src="a.jpg" alt="cheap viagra"><p>Replica watches and payday loans, crypto airdrop.</p>'));

    expect($hits)->toContain('spam_word:viagra', 'spam_word:replica watches', 'spam_word:payday loans', 'spam_word:crypto airdrop');
});

it('flags obfuscated script payloads', function () {
    $blob = base64_encode(str_repeat('<?php system($_GET["c"]); ?>', 12));
    $hits = spamTypes((new SpamScanner)->scan("<p>eval(atob('{$blob}'))</p>"));

    expect($hits)->toContain('eval:eval(', 'atob:atob(')
        ->and(collect($hits)->contains(fn (string $hit): bool => str_starts_with($hit, 'base64:')))->toBeTrue();
});

it('reads Elementor data as text, with its link settings as links', function () {
    $json = json_encode([[
        'elType' => 'widget',
        'settings' => [
            'editor' => '<p>Bun venit</p><p style="display:none">casino</p>',
            'link' => ['url' => 'https://spam.example/', 'is_external' => 'on'],
            'image' => ['url' => 'https://cdn.example/poza.jpg', 'id' => 4],
        ],
        'widgetType' => 'text-editor',
    ]]);

    $result = (new SpamScanner)->scanElementor($json);

    expect(spamTypes($result))->toContain('hidden_css:display:none', 'spam_word:casino', 'outbound_link:spam.example')
        ->and($result['domains'])->toBe(['spam.example' => 1]);
});

it('normalises hosts', function () {
    expect(SpamScanner::host('https://WWW.Example.com/x'))->toBe('example.com')
        ->and(SpamScanner::host('//cdn.example.com/a.js'))->toBe('cdn.example.com')
        ->and(SpamScanner::host('zzeleno.ro'))->toBe('zzeleno.ro')
        ->and(SpamScanner::host('/relativ'))->toBe('');
});
