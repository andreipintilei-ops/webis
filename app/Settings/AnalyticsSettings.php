<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Tracking and verification ids. GA4 only ever loads after cookie consent
 * (Consent Mode v2); verification tags are plain meta tags.
 */
class AnalyticsSettings extends Settings
{
    /** GA4 measurement id, e.g. G-XXXXXXXXXX. */
    public ?string $ga4_measurement_id;

    /** Google Ads conversion id, e.g. AW-XXXXXXXXX, for the lead conversion. */
    public ?string $google_ads_id;

    public ?string $google_site_verification;

    public ?string $bing_site_verification;

    public static function group(): string
    {
        return 'analytics';
    }
}
