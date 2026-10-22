<?php

use App\Support\Legacy\ContentSource;

it('tells where a post keeps its body', function (?string $content, ?string $elementor, ContentSource $expected) {
    expect(ContentSource::detect($content, $elementor))->toBe($expected);
})->with([
    'elementor wins over its own copy in post_content' => ['<p>Copie</p>', '[{"id":"a1","elType":"section"}]', ContentSource::Elementor],
    'elementor with an empty post_content' => ['', '[{"id":"a1"}]', ContentSource::Elementor],
    'gutenberg' => ["<!-- wp:paragraph -->\n<p>Bună</p>\n<!-- /wp:paragraph -->", null, ContentSource::Gutenberg],
    'classic' => ['<p>Ce este SEO?</p>', null, ContentSource::Classic],
    'empty' => ["  \n ", null, ContentSource::Empty],
    'null' => [null, null, ContentSource::Empty],
    'unused elementor data is not elementor' => ['<p>Text</p>', '[]', ContentSource::Classic],
    'blank elementor data and no content' => ['', '', ContentSource::Empty],
]);
