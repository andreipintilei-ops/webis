<?php

namespace App\Enums;

/**
 * How a redirect's `source_path` is matched against a 404ing request. Exact
 * matches are tried first, then the longest matching prefix.
 */
enum RedirectMatchType: string
{
    case Exact = 'exact';
    case Prefix = 'prefix';

    public function label(): string
    {
        return match ($this) {
            self::Exact => 'Exact',
            self::Prefix => 'Prefix',
        };
    }
}
