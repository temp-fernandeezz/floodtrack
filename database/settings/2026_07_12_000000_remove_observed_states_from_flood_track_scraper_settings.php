<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->deleteIfExists('flood_track_scraper.observed_states');
    }

    public function down(): void
    {
        $this->migrator->add('flood_track_scraper.observed_states', []);
    }
};
