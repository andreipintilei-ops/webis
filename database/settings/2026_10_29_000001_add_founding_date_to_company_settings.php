<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * The year Webis SRL was founded, for the Organization's foundingDate: part
 * of what tells it apart from newer businesses with similar names.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('company.founding_date', '2016');
    }

    public function down(): void
    {
        $this->migrator->delete('company.founding_date');
    }
};
