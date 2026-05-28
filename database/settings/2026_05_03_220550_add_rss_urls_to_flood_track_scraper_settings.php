<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('flood_track_scraper.rss_urls', [
            'https://g1.globo.com/rss/g1/sp',
        ]);
    }

    public function down(): void
    {
        $this->migrator->delete('flood_track_scraper.rss_urls');
    }
};
