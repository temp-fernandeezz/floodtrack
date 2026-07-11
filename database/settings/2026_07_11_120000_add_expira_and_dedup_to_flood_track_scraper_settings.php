<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('flood_track_scraper.expira_after_hours', 24);
        $this->migrator->add('flood_track_scraper.dedup_window_hours', 12);
    }

    public function down(): void
    {
        $this->migrator->delete('flood_track_scraper.expira_after_hours');
        $this->migrator->delete('flood_track_scraper.dedup_window_hours');
    }
};
