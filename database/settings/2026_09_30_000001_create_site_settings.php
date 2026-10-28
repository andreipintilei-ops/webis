<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Starting values for every settings group. Contact details are left empty on
 * purpose — they are entered in the admin (Setări), never guessed here.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('company.name', 'Webis');
        $this->migrator->add('company.legal_name', 'Webis SRL');
        $this->migrator->add('company.vat_id', null);
        $this->migrator->add('company.registration_number', null);
        $this->migrator->add('company.phone', null);
        $this->migrator->add('company.email', null);
        $this->migrator->add('company.street_address', null);
        $this->migrator->add('company.locality', 'Iași');
        $this->migrator->add('company.region', 'Iași');
        $this->migrator->add('company.postal_code', null);
        $this->migrator->add('company.country_code', 'RO');
        $this->migrator->add('company.latitude', null);
        $this->migrator->add('company.longitude', null);
        $this->migrator->add('company.opening_hours', []);
        $this->migrator->add('company.social', []);

        $this->migrator->add('seo.title_suffix', ' | Webis');
        $this->migrator->add('seo.default_description', null);
        $this->migrator->add('seo.default_og_image_asset_id', null);
        $this->migrator->add('seo.logo_asset_id', null);

        $this->migrator->add('analytics.ga4_measurement_id', null);
        $this->migrator->add('analytics.google_ads_id', null);
        $this->migrator->add('analytics.google_site_verification', null);
        $this->migrator->add('analytics.bing_site_verification', null);

        $this->migrator->add('leads.notification_emails', []);
        $this->migrator->add('leads.budget_options', []);
        $this->migrator->add('leads.send_confirmation', true);
    }
};
