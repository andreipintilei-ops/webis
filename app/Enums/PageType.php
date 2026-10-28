<?php

namespace App\Enums;

/**
 * Every CMS page lives in the one `pages` table at a root slug; the type only
 * decides its template, JSON-LD and where it is listed. An article keeps its
 * root address too, with a category and a publish date: /blog and its
 * category pages list them (Public\ArticleController).
 */
enum PageType: string
{
    case Home = 'home';
    case Page = 'page';
    case Service = 'service';
    case Industry = 'industry';
    case Legal = 'legal';
    case Contact = 'contact';
    case Article = 'article';

    public function label(): string
    {
        return match ($this) {
            self::Home => 'Prima pagină',
            self::Page => 'Pagină',
            self::Service => 'Serviciu',
            self::Industry => 'Industrie',
            self::Legal => 'Pagină legală',
            self::Contact => 'Contact',
            self::Article => 'Articol',
        };
    }
}
