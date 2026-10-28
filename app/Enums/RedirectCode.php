<?php

namespace App\Enums;

enum RedirectCode: int
{
    case Permanent = 301;
    case Temporary = 302;
    case Gone = 410;

    public function label(): string
    {
        return match ($this) {
            self::Permanent => '301 — Permanent',
            self::Temporary => '302 — Temporar',
            self::Gone => '410 — Șters definitiv',
        };
    }

    /**
     * A 410 answers the request itself; it never points anywhere.
     */
    public function needsTarget(): bool
    {
        return $this !== self::Gone;
    }
}
