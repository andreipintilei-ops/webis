<?php

namespace App\Support\Legacy\Import;

/**
 * The binding until the content converters (WP5b) land: nothing converts, so
 * the articles, pages and industries steps refuse with a clear message.
 */
final class UnavailableBodyConverter implements BodyConverter
{
    public function convert(LegacyPost $post, string $kind, ConversionContext $context): ConvertedBody
    {
        throw ConverterUnavailable::forKind($kind);
    }

    public function available(): bool
    {
        return false;
    }
}
