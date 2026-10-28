<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Site-wide SEO defaults. Per-item overrides live in each model's `seo` column.
 */
class SeoSettings extends Settings
{
    /** Appended to every <title> that doesn't already contain it, e.g. " | Webis". */
    public string $title_suffix;

    /** Fallback meta description for pages that have none of their own. */
    public ?string $default_description;

    /** Fallback share image (1200×630) when neither the item nor a generated OG image exists. */
    public ?int $default_og_image_asset_id;

    /** Organization logo for JSON-LD; Google wants at least 112×112. */
    public ?int $logo_asset_id;

    public static function group(): string
    {
        return 'seo';
    }
}
