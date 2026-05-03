<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class FloodTrackScraperSettings extends Settings
{
    public array $observed_states = [];
    public array $allowed_domains = [];

    public static function group(): string
    {
        return 'floodtrack_scraper';
    }
}
