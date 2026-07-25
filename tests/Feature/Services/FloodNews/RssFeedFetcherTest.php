<?php

namespace Tests\Feature\Services\FloodNews;

use App\Services\FloodNews\RssFeedFetcher;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RssFeedFetcherTest extends TestCase
{
    private const FEED_URL = 'https://exemplo.com/rss';

    public function test_parses_valid_rss_items(): void
    {
        $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0">
            <channel>
                <item>
                    <title>Rua fica alagada em bairro de São Paulo</title>
                    <link>https://exemplo.com/noticia-1</link>
                    <description><![CDATA[<p>Moradores relatam <b>transtornos</b>.</p>]]></description>
                    <pubDate>Tue, 25 Jul 2026 10:00:00 -0300</pubDate>
                </item>
            </channel>
        </rss>
        XML;

        Http::fake([self::FEED_URL => Http::response($xml, 200)]);

        $items = (new RssFeedFetcher())->fetch(self::FEED_URL);

        $this->assertCount(1, $items);
        $this->assertSame('Rua fica alagada em bairro de São Paulo', $items[0]->title);
        $this->assertSame('https://exemplo.com/noticia-1', $items[0]->link);
        $this->assertSame('Moradores relatam transtornos.', $items[0]->summary);
        $this->assertNotNull($items[0]->publishedAt);
    }

    public function test_skips_items_missing_title_or_link(): void
    {
        $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0">
            <channel>
                <item>
                    <title></title>
                    <link>https://exemplo.com/sem-titulo</link>
                </item>
                <item>
                    <title>Sem link</title>
                    <link></link>
                </item>
                <item>
                    <title>Notícia válida</title>
                    <link>https://exemplo.com/valida</link>
                </item>
            </channel>
        </rss>
        XML;

        Http::fake([self::FEED_URL => Http::response($xml, 200)]);

        $items = (new RssFeedFetcher())->fetch(self::FEED_URL);

        $this->assertCount(1, $items);
        $this->assertSame('Notícia válida', $items[0]->title);
    }

    public function test_returns_empty_array_when_http_status_is_not_ok(): void
    {
        Http::fake([self::FEED_URL => Http::response('erro', 500)]);

        $items = (new RssFeedFetcher())->fetch(self::FEED_URL);

        $this->assertSame([], $items);
    }

    public function test_returns_empty_array_when_body_is_not_valid_xml(): void
    {
        Http::fake([self::FEED_URL => Http::response('isso não é xml', 200)]);

        $items = (new RssFeedFetcher())->fetch(self::FEED_URL);

        $this->assertSame([], $items);
    }

    public function test_returns_empty_array_when_xml_has_no_channel_items(): void
    {
        Http::fake([self::FEED_URL => Http::response('<?xml version="1.0"?><rss><channel></channel></rss>', 200)]);

        $items = (new RssFeedFetcher())->fetch(self::FEED_URL);

        $this->assertSame([], $items);
    }

    public function test_returns_empty_array_on_connection_exception(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('timeout');
        });

        $items = (new RssFeedFetcher())->fetch(self::FEED_URL);

        $this->assertSame([], $items);
    }
}
