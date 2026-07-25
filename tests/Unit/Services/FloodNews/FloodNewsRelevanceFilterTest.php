<?php

namespace Tests\Unit\Services\FloodNews;

use App\Services\FloodNews\FloodNewsRelevanceFilter;
use App\Services\FloodNews\NewsItem;
use App\Settings\FloodTrackScraperSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FloodNewsRelevanceFilterTest extends TestCase
{
    use RefreshDatabase;

    private FloodNewsRelevanceFilter $filter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filter = new FloodNewsRelevanceFilter();
    }

    private function item(string $title, string $summary = '', string $link = 'https://g1.globo.com/sp/noticia.html'): NewsItem
    {
        return new NewsItem($title, $link, $summary, null);
    }

    public function test_detects_flood_keywords_as_relevant(): void
    {
        $this->assertTrue($this->filter->isRelevant($this->item('Rua fica alagada após forte chuva')));
        $this->assertTrue($this->filter->isRelevant($this->item('Enchente atinge bairro', 'moradores relatam prejuízos')));
    }

    public function test_ignores_news_without_flood_keywords(): void
    {
        $this->assertFalse($this->filter->isRelevant($this->item('Prefeitura inaugura novo parque')));
    }

    public function test_blocks_relevant_looking_news_that_are_actually_about_something_else(): void
    {
        // Menciona "alagada" de passagem, mas é sobre um afogamento — não deve virar ponto no mapa.
        $this->assertFalse(
            $this->filter->isRelevant($this->item('Corpo é encontrado em rua alagada', 'vítima de afogamento'))
        );
    }

    public function test_is_case_insensitive(): void
    {
        $this->assertTrue($this->filter->isRelevant($this->item('RUA ALAGADA APÓS TEMPORAL')));
    }

    public function test_allows_any_domain_when_no_allowed_domains_configured(): void
    {
        $settings = FloodTrackScraperSettings::fake(['allowed_domains' => []]);

        $this->assertTrue($this->filter->isAllowedBySettings('https://g1.globo.com/x', $settings));
        $this->assertTrue($this->filter->isAllowedBySettings('https://qualquer-outro-site.com/x', $settings));
    }

    public function test_restricts_to_allowed_domains_when_configured(): void
    {
        $settings = FloodTrackScraperSettings::fake(['allowed_domains' => ['g1.globo.com']]);

        $this->assertTrue($this->filter->isAllowedBySettings('https://g1.globo.com/sp/noticia.html', $settings));
        $this->assertTrue($this->filter->isAllowedBySettings('https://sub.g1.globo.com/sp/noticia.html', $settings));
        $this->assertFalse($this->filter->isAllowedBySettings('https://outro-site.com/noticia.html', $settings));
    }

    public function test_detects_known_news_sources(): void
    {
        $this->assertSame('g1', $this->filter->detectSource('https://g1.globo.com/sp/noticia.html'));
        $this->assertSame('uol', $this->filter->detectSource('https://noticias.uol.com.br/x'));
        // "folha.uol.com.br" contém o trecho "uol.com", então cai na regra do UOL antes da do Folha.
        $this->assertSame('uol', $this->filter->detectSource('https://folha.uol.com.br/x'));
        $this->assertSame('folha', $this->filter->detectSource('https://folha.com.br/x'));
    }

    public function test_falls_back_to_raw_host_for_unknown_sources(): void
    {
        $this->assertSame('meusite.com.br', $this->filter->detectSource('https://meusite.com.br/noticia'));
    }
}
