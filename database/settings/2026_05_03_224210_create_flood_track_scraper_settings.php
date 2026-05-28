<?php

use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->settingExists('flood_track_scraper', 'observed_states')) {
            $this->migrator->add('flood_track_scraper.observed_states', []);
        }

        if (! $this->settingExists('flood_track_scraper', 'allowed_domains')) {
            $this->migrator->add('flood_track_scraper.allowed_domains', []);
        }

        if (! $this->settingExists('flood_track_scraper', 'rss_urls')) {
            $this->migrator->add('flood_track_scraper.rss_urls', [
                'https://g1.globo.com/rss/g1/sp',
            ]);
        }
    }

    public function down(): void
    {
        if ($this->settingExists('flood_track_scraper', 'observed_states')) {
            $this->migrator->delete('flood_track_scraper.observed_states');
        }

        if ($this->settingExists('flood_track_scraper', 'allowed_domains')) {
            $this->migrator->delete('flood_track_scraper.allowed_domains');
        }

        if ($this->settingExists('flood_track_scraper', 'rss_urls')) {
            $this->migrator->delete('flood_track_scraper.rss_urls');
        }
    }

    private function settingExists(string $group, string $name): bool
    {
        return DB::table('settings')
            ->where('group', $group)
            ->where('name', $name)
            ->exists();
    }
};
