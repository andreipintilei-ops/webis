<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * The quote form's budget choices: the form never offered any. The leads
 * imported from the old site keep their budget (leads.budget).
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->delete('leads.budget_options');
    }

    public function down(): void
    {
        $this->migrator->add('leads.budget_options', []);
    }
};
