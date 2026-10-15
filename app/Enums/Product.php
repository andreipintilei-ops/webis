<?php

namespace App\Enums;

/**
 * Webis's own products. They appear only on Produse SaaS and their own pages
 * — never as client work on /portofoliu. A client can use some of them
 * (Client::$products): a product page's "Folosit de" strip shows those
 * clients' logos (components/site/used-by).
 */
enum Product: string
{
    case SmileSoft = 'smilesoft';
    case Rims = 'rims';
    case ParticipatoryBudgeting = 'bugetare-participativa';
    case Confirmo = 'confirmo';

    public function label(): string
    {
        return match ($this) {
            self::SmileSoft => 'SmileSoft',
            self::Rims => 'RIMS',
            self::ParticipatoryBudgeting => 'Bugetare participativă',
            self::Confirmo => 'Confirmo',
        };
    }

    /** Its own site, for a product that lives outside webis.ro. */
    public function externalUrl(): ?string
    {
        return match ($this) {
            self::Confirmo => 'https://confirmo.ro/',
            default => null,
        };
    }
}
