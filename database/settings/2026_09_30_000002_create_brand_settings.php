<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('brand.logo_asset_id', null);
        $this->migrator->add('brand.logo_negative_asset_id', null);
        $this->migrator->add('brand.favicon_asset_id', null);
        $this->migrator->add('brand.favicons', []);
    }
};
