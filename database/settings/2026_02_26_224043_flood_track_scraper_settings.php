<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('floodtrack_scraper.allowed_domains', ['g1.globo.com']);
        $this->migrator->add('floodtrack_scraper.observed_states', []);
    }
};
