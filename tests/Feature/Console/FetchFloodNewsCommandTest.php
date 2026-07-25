<?php

namespace Tests\Feature\Console;

use App\Models\FloodPoint;
use App\Models\NewsArticle;
use App\Settings\FloodTrackScraperSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchFloodNewsCommandTest extends TestCase
{
    use RefreshDatabase;

    private const RSS_URL = 'https://g1.globo.com/rss/g1/';

    private function fakeRss(string $title, string $link, string $summary = ''): void
    {
        $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0">
            <channel>
                <item>
                    <title>{$title}</title>
                    <link>{$link}</link>
                    <description>{$summary}</description>
                    <pubDate>Tue, 25 Jul 2026 10:00:00 -0300</pubDate>
                </item>
            </channel>
        </rss>
        XML;

        Http::fake([
            self::RSS_URL => Http::response($xml, 200),
            'nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '-23.96', 'lon' => '-46.33'],
            ], 200),
        ]);
    }

    public function test_imports_relevant_news_as_approved_flood_point(): void
    {
        config(['services.anthropic.key' => null]);
        FloodTrackScraperSettings::fake(['rss_urls' => [self::RSS_URL], 'allowed_domains' => []]);

        $this->fakeRss(
            'Bairro alagado em Santos - SP após temporal',
            'https://g1.globo.com/sp/santos-regiao/noticia/1.ghtml',
        );

        $this->artisan('flood:fetch-news')->assertExitCode(0);

        $this->assertDatabaseCount('news_articles', 1);
        $point = FloodPoint::first();
        $this->assertNotNull($point);
        $this->assertSame('approved', $point->review_status);
        $this->assertSame('SP', $point->uf);
    }

    public function test_ignores_news_without_flood_keywords(): void
    {
        FloodTrackScraperSettings::fake(['rss_urls' => [self::RSS_URL], 'allowed_domains' => []]);

        $this->fakeRss('Prefeitura inaugura novo parque municipal', 'https://g1.globo.com/sp/noticia/1.ghtml');

        $this->artisan('flood:fetch-news')->assertExitCode(0);

        $this->assertDatabaseCount('news_articles', 0);
        $this->assertDatabaseCount('flood_points', 0);
    }

    public function test_does_not_reprocess_same_article_url_twice(): void
    {
        FloodTrackScraperSettings::fake(['rss_urls' => [self::RSS_URL], 'allowed_domains' => []]);

        $this->fakeRss('Rua alagada em Santos - SP', 'https://g1.globo.com/sp/santos/noticia/1.ghtml');

        $this->artisan('flood:fetch-news')->assertExitCode(0);
        $this->assertDatabaseCount('news_articles', 1);

        $this->artisan('flood:fetch-news')->assertExitCode(0);
        // A mesma URL não deve gerar um segundo NewsArticle nem um segundo FloodPoint.
        $this->assertDatabaseCount('news_articles', 1);
    }

    public function test_respects_allowed_domains_setting(): void
    {
        FloodTrackScraperSettings::fake(['rss_urls' => [self::RSS_URL], 'allowed_domains' => ['outrosite.com']]);

        $this->fakeRss('Rua alagada em Santos - SP', 'https://g1.globo.com/sp/santos/noticia/1.ghtml');

        $this->artisan('flood:fetch-news')->assertExitCode(0);

        $this->assertDatabaseCount('news_articles', 0);
    }
}
