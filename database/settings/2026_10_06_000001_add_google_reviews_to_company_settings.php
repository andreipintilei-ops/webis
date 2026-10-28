<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * The Google Business Profile's rating, shown in the hero above the client
 * logos (components/site/google-rating). The link opens the profile's
 * reviews; the place is the one the old site's review widget pointed at.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('company.google_rating', 4.7);
        $this->migrator->add('company.google_review_count', null);
        $this->migrator->add('company.google_reviews_url', 'https://search.google.com/local/reviews?placeid=ChIJdb43T2j6ykARljUR4Ws4GEI');
    }
};
