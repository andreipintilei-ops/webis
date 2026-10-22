<?php

namespace App\Support\Legacy\Import;

use RuntimeException;

final class ConverterUnavailable extends RuntimeException
{
    public static function forKind(string $kind): self
    {
        return new self(
            "No body converter is available for {$kind}s yet: they need the content converters "
            .'(RichText::fromHtml, WordPressHtml, ElementorToBlocks, PodsToBlocks). '
            .'Nothing was imported for this step; run it again once they are bound to '.BodyConverter::class.'.'
        );
    }
}
