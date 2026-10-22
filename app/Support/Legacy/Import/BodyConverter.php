<?php

namespace App\Support\Legacy\Import;

/**
 * Turns an old post's body into blocks: the one seam between `legacy:import`
 * and the content converters (`RichText::fromHtml`, `WordPressHtml`,
 * `ElementorToBlocks`, `PodsToBlocks`).
 *
 * The importer picks the post, its slug, category, date, SEO and status; the
 * converter only decides what its body becomes. Bound in the container, so
 * tests swap in a fake. Until the converters exist the binding is
 * UnavailableBodyConverter, and the articles, pages and industries steps
 * refuse to run rather than import empty bodies.
 */
interface BodyConverter
{
    /**
     * Throws ConverterUnavailable when it cannot convert this kind of post.
     *
     * @param  string  $kind  'article', 'page' or 'industry'
     */
    public function convert(LegacyPost $post, string $kind, ConversionContext $context): ConvertedBody;

    /**
     * Whether convert() can run at all; checked once, before a step touches
     * anything.
     */
    public function available(): bool;
}
