<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * The other tracking tools the admin can switch on, each loaded only after
 * the visitor's consent for its category (Support\Consent\CookieConsent),
 * and the Google Ads conversion sent for a lead. Hotjar's id is the old
 * site's.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('analytics.google_ads_lead_label', null);
        $this->migrator->add('analytics.gtm_container_id', null);
        $this->migrator->add('analytics.meta_pixel_id', null);
        $this->migrator->add('analytics.linkedin_partner_id', null);
        $this->migrator->add('analytics.hotjar_site_id', null);
        $this->migrator->add('analytics.clarity_project_id', null);
    }
};
