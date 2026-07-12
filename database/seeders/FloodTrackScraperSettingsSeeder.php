<?php

namespace Database\Seeders;

use App\Settings\FloodTrackScraperSettings;
use Illuminate\Database\Seeder;

class FloodTrackScraperSettingsSeeder extends Seeder
{
    public function run(): void
    {
        /** @var FloodTrackScraperSettings $settings */
        $settings = app(FloodTrackScraperSettings::class);

        $settings->allowed_domains = ['g1.globo.com'];

        $settings->save();
    }
}
