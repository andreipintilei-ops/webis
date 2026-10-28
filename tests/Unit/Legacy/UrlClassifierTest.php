<?php

use App\Support\Legacy\UrlClassifier;

function classify(string $type, ?string $subType, string $slug, string $path, array $categories = ['seo', 'blog', 'web-design', 'uncategorized']): array
{
    return (new UrlClassifier($categories))->classify($type, $subType, $slug, $path);
}

it('pre-fills each WordPress object with its decision and a trailing-slash target', function (string $type, ?string $subType, string $slug, string $path, string $decision, string $target) {
    $result = classify($type, $subType, $slug, $path);

    expect($result['decision'])->toBe($decision)
        ->and($result['target_path'])->toBe($target);
})->with([
    'project' => ['post', 'proiect', 'zyanya', '/proiect/zyanya', 'project', '/proiect/zyanya/'],
    'article' => ['post', 'post', 'ce-este-seo-on-page', '/ce-este-seo-on-page', 'article', '/ce-este-seo-on-page/'],
    'industry' => ['post', 'domenii', 'creare-site-stomatologie', '/servicii/creare-site-stomatologie', 'industry', '/servicii/creare-site-stomatologie/'],
    'page' => ['post', 'page', 'creare-magazin-online', '/creare-magazin-online', 'page', '/creare-magazin-online/'],
    'privacy policy' => ['post', 'page', 'politica-de-confidentialitate', '/politica-de-confidentialitate', 'legal', '/politica-de-confidentialitate/'],
    'category' => ['term', 'category', 'web-design', '/web-design', 'category', '/blog/categorie/web-design/'],
    'tag with a namesake category' => ['term', 'post_tag', 'seo', '/tag/seo', 'redirect', '/blog/categorie/seo/'],
    'project category' => ['term', 'categorie_proiect', 'logo-design', '/categorie-proiect/logo-design', 'redirect', '/portofoliu/'],
    'home page' => ['home-page', null, '', '/', 'skip', '/'],
    'front page' => ['post', 'page', 'creare-site-iasi', '/', 'skip', '/'],
    'project archive' => ['post-type-archive', 'proiect', 'proiect', '/proiect', 'skip', '/portofoliu/'],
]);

it('skips the four old root URLs the new site already serves', function (string $type, ?string $subType, string $slug, string $target) {
    $result = classify($type, $subType, $slug, "/{$slug}");

    expect($result['decision'])->toBe('skip')
        ->and($result['target_path'])->toBe($target)
        ->and($result['notes'])->toContain('Already live');
})->with([
    'blog (a category)' => ['term', 'category', 'blog', '/blog/'],
    'contact' => ['post', 'page', 'contact', '/contact/'],
    'portofoliu' => ['post', 'page', 'portofoliu', '/portofoliu/'],
    'proiect' => ['post', 'page', 'proiect', '/portofoliu/'],
]);

it('answers the junk slugs with gone, whatever their type', function (string $type, string $subType, string $slug) {
    $result = classify($type, $subType, $slug, "/{$slug}");

    expect($result['decision'])->toBe('gone')
        ->and($result['target_path'])->toBe('');
})->with([
    ['term', 'category', 'wazambacasinoaustralia-org'],
    ['post', 'post', '12705-2'],
    ['term', 'category', 'uncategorized'],
]);

it('leaves the cases with no safe rule undecided, with a note', function (string $type, ?string $subType, string $slug, string $path, string $note) {
    $result = classify($type, $subType, $slug, $path);

    expect($result['decision'])->toBe('')
        ->and($result['target_path'])->toBe('')
        ->and($result['notes'])->toContain($note)->toContain(UrlClassifier::NEEDS_DECISION);
})->with([
    'tag without a namesake' => ['term', 'post_tag', 'php', '/tag/php', 'No category with the same slug'],
    'carte' => ['post', 'carte', 'carte-1', '/carte/carte-1', 'Open item'],
    'unknown type' => ['post', 'elementor_library', 'x', '/x', 'No rule for post/elementor_library'],
]);

it('sends a tag whose namesake is the blog category to the blog itself', function () {
    $result = classify('term', 'post_tag', 'blog', '/tag/blog');

    expect($result)->toMatchArray(['decision' => 'redirect', 'target_path' => '/blog/']);
});

it('lists the WordPress shapes Yoast never does', function () {
    $shapes = collect(UrlClassifier::wordpressShapes())->keyBy('old_path');

    expect($shapes->keys()->all())->toBe(['/feed', '/comments/feed', '/page', '/author'])
        ->and($shapes['/feed']['decision'])->toBe('gone')
        ->and($shapes['/comments/feed']['decision'])->toBe('gone')
        ->and($shapes['/page'])->toMatchArray(['decision' => 'redirect', 'target_path' => '/blog/'])
        ->and($shapes['/page']['notes'])->toStartWith('Prefix row')
        ->and($shapes['/author'])->toMatchArray(['decision' => 'redirect', 'target_path' => '/blog/'])
        ->and($shapes['/author']['notes'])->toStartWith('Prefix row');
});

it('builds attachment page paths under their parent', function () {
    expect(UrlClassifier::attachmentPagePath('/proiect/zyanya', 'zyanya-cover'))->toBe('/proiect/zyanya/zyanya-cover')
        ->and(UrlClassifier::attachmentPagePath('/ce-este-seo', '123'))->toBe('/ce-este-seo/attachment/123');
});

it('derives a post\'s old addresses from its old slugs and dates', function () {
    $paths = UrlClassifier::oldPaths('/ce-este-css', 'post', 'ce-este-css', ['css-vechi', '12392', 'ce-este-css'], ['2022-02-08']);

    expect($paths)->toEqualCanonicalizing([
        '/css-vechi',
        '/2022/02/08/ce-este-css',
        '/2022/02/08/css-vechi',
    ]);
});

it('keeps old slugs under their post type\'s base, and dates only for blog posts', function () {
    expect(UrlClassifier::oldPaths('/proiect/zyanya-logo', 'proiect', 'zyanya-logo', ['zyanya-2'], ['2024-07-12']))
        ->toBe(['/proiect/zyanya-2']);
});
