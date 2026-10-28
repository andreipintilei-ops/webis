<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * The footer's closing call, page by page (Setări › Subsol): for each of
 * Support\FooterCta::PAGES, its title, description, button and the button's
 * link. A page left blank, or a field of it, falls back to `default`'s.
 */
class FooterCtaSettings extends Settings
{
    /**
     * Key (FooterCta::PAGES) → its call.
     *
     * @phpstan-var array<string, array{title: string|null, intro: string|null, button: string|null, url: string|null}>
     */
    public array $pages;

    public static function group(): string
    {
        return 'footer_cta';
    }
}
