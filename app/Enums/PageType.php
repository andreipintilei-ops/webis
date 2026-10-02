<?php

namespace App\Enums;

/**
 * Every CMS page lives in the one `pages` table at a root slug; the type only
 * decides its template, JSON-LD and where it is listed.
 */
enum PageType: string
{
    case Home = 'home';
    case Page = 'page';
    case Service = 'service';
    case Industry = 'industry';
    case Legal = 'legal';
    case Contact = 'contact';

    public function label(): string
    {
        return match ($this) {
            self::Home => 'Prima pagină',
            self::Page => 'Pagină',
            self::Service => 'Serviciu',
            self::Industry => 'Industrie',
            self::Legal => 'Pagină legală',
            self::Contact => 'Contact',
        };
    }
}
