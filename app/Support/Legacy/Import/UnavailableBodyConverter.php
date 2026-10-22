<?php

namespace App\Support\Legacy\Import;

/**
 * A converter that converts nothing: bound in place of LegacyBodyConverter,
 * the articles, pages and industries steps refuse with a clear message and
 * projects keep empty bodies.
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
