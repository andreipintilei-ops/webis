<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Tracking and verification ids. Every tracking tool loads only after the
 * visitor's cookie consent for its category (Support\Consent\CookieConsent,
 * Consent Mode v2 for Google's); verification tags are plain meta tags.
 */
class AnalyticsSettings extends Settings
{
    /** GA4 measurement id, e.g. G-XXXXXXXXXX. Analytics. */
    public ?string $ga4_measurement_id;

    /** Google Ads conversion id, e.g. AW-XXXXXXXXX. Marketing. */
    public ?string $google_ads_id;

    /** The label of the Ads conversion sent for a lead (AW-…/label). */
    public ?string $google_ads_lead_label;

    /** Google Tag Manager container, e.g. GTM-XXXXXXX. Loads with either category. */
    public ?string $gtm_container_id;

    /** Meta (Facebook) Pixel id. Marketing. */
    public ?string $meta_pixel_id;

    /** LinkedIn Insight Tag partner id. Marketing. */
    public ?string $linkedin_partner_id;

    /** Hotjar site id. Analytics. */
    public ?string $hotjar_site_id;

    /** Microsoft Clarity project id. Analytics. */
    public ?string $clarity_project_id;

    public ?string $google_site_verification;

    public ?string $bing_site_verification;

    public static function group(): string
    {
        return 'analytics';
    }
}
