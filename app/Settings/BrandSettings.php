<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * The site's visual identity: which media-library images are the logo and the
 * favicon. The favicon files themselves are generated from the chosen image
 * (App\Actions\Brand\GenerateFavicons).
 */
class BrandSettings extends Settings
{
    /** The logo on light backgrounds (the page). */
    public ?int $logo_asset_id;

    /** The logo on dark backgrounds (the open menu). */
    public ?int $logo_negative_asset_id;

    /** A square mark; favicons and the home-screen icon are made from it. */
    public ?int $favicon_asset_id;

    /** The square app icon: the admin's sidebar and the login screen. */
    public ?int $icon_asset_id;

    /**
     * Paths (on the public disk) of the generated favicon files, keyed by
     * kind; empty until a favicon is chosen.
     *
     * @phpstan-var array<string, string>
     */
    public array $favicons;

    public static function group(): string
    {
        return 'brand';
    }
}
