<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class FloodTrackScraperSettings extends Settings
{
    public array $observed_states = [];
    public array $allowed_domains = [];

    public array $rss_urls = [];

    public int $expira_after_hours = 24;

    public int $dedup_window_hours = 12;

    public static function group(): string
    {
        return 'flood_track_scraper';
    }
}
