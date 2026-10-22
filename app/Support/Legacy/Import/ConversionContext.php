<?php

namespace App\Support\Legacy\Import;

use App\Models\Redirect;

/**
 * What a body converter needs besides the post itself: the media map, to turn
 * every `<img src>` into an asset id, and each old address's final target, to
 * rewrite internal links so none goes through a redirect.
 */
final readonly class ConversionContext
{
    /**
     * @param  array<string, string>  $links  normalised old path → its final target (slash form, `/x/`)
     */
    public function __construct(
        public MediaMap $media,
        public array $links = [],
    ) {}

    /**
     * Where a link to the old site should point now: its final target, or
     * null when the address isn't one the map knows (or is gone).
     */
    public function linkTarget(string $href): ?string
    {
        return $this->links[Redirect::normalisePath($href)] ?? null;
    }
}
