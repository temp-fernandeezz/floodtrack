<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class FloodTrackScraperSettings extends Settings
{
    public array $observed_states = [];
    public array $allowed_domains = [];

    public array $rss_urls = [];

    public static function group(): string
    {
        return 'flood_track_scraper';
    }
}
