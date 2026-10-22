<?php

namespace App\Support\Legacy\Import;

use App\Support\Legacy\MediaResolver;
use App\Support\Legacy\UrlResolver;

/**
 * What a body converter needs besides the post itself: the media resolver,
 * to turn every image into a real asset id, and the link resolver, to point
 * internal links at their final targets so none goes through a redirect.
 */
final readonly class ConversionContext
{
    public function __construct(
        public MediaResolver $media,
        public UrlResolver $urls,
    ) {}

    public static function for(ImportContext $context): self
    {
        return new self(
            new ImportMediaResolver($context->media(), $context->mediaSource()),
            ImportUrlResolver::fromMap($context->map),
        );
    }
}
