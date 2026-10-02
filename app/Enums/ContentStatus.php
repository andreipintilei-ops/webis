<?php

namespace App\Enums;

/**
 * Publication state shared by pages, projects and posts.
 *
 * Scheduled items carry a future `published_at`; the `content:publish-due`
 * command flips them to Published once that moment passes. Only Published
 * items with `published_at <= now()` are ever public.
 */
enum ContentStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Ciornă',
            self::Scheduled => 'Programat',
            self::Published => 'Publicat',
        };
    }
}
