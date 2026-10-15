<?php

namespace App\Enums;

/**
 * What a client project was — the filter on /portofoliu (?tip=magazin) and
 * how a service page picks its related work (software for Software la
 * comandă; presentation sites and online shops for Web și e-commerce).
 * Products are never projects: they live on Produse SaaS.
 */
enum ProjectType: string
{
    case Software = 'software';
    case Presentation = 'prezentare';
    case Shop = 'magazin';
    case Logo = 'logo';

    /** One project's kind, as a tag. */
    public function label(): string
    {
        return match ($this) {
            self::Software => 'Software la comandă',
            self::Presentation => 'Site de prezentare',
            self::Shop => 'Magazin online',
            self::Logo => 'Identitate vizuală',
        };
    }

    /** The filter's name for all of them. */
    public function plural(): string
    {
        return match ($this) {
            self::Software => 'Software la comandă',
            self::Presentation => 'Site-uri de prezentare',
            self::Shop => 'Magazine online',
            self::Logo => 'Identitate vizuală',
        };
    }

    /**
     * The types a service page shows as related work, by its slug; none for
     * any other page.
     *
     * @return list<self>
     */
    public static function forServicePage(string $slug): array
    {
        return match ($slug) {
            'software-la-comanda' => [self::Software],
            'web' => [self::Presentation, self::Shop],
            default => [],
        };
    }
}
