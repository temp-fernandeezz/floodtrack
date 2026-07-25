<?php

namespace Tests\Feature\Services\FloodNews;

use App\Services\FloodNews\FloodLocationParser;
use App\Services\FloodNews\NewsItem;
use App\Services\LocationExtractorService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FloodLocationParserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Sem chave configurada, LocationExtractorService::isConfigured() é false
        // e o parser cai direto no fallback por regex (sem chamada HTTP).
        config(['services.anthropic.key' => null]);
    }

    private function parser(): FloodLocationParser
    {
        return new FloodLocationParser(new LocationExtractorService());
    }

    public function test_infers_uf_from_g1_regional_url_without_calling_ai(): void
    {
        $item = new NewsItem(
            'Chuva forte causa alagamentos na cidade',
            'https://g1.globo.com/sp/santos-regiao/noticia/2026/07/25/chuva.ghtml',
            '',
            null,
        );

        $location = $this->parser()->extract($item);

        $this->assertSame('SP', $location->uf);
        Http::assertNothingSent();
    }

    public function test_infers_city_from_text_pattern(): void
    {
        $item = new NewsItem(
            'Bairro alagado em Santos - SP após temporal',
            'https://g1.globo.com/noticia/1.ghtml',
            'Moradores relatam prejuízos com a forte chuva',
            null,
        );

        $location = $this->parser()->extract($item);

        $this->assertSame('Santos', $location->cidade);
    }

    public function test_severity_is_alto_when_text_mentions_transbordamento(): void
    {
        $item = new NewsItem('Rio transborda e causa estado de emergência em Petrópolis - RJ', 'https://g1.globo.com/x', '', null);

        $location = $this->parser()->extract($item);

        $this->assertSame('alto', $location->nivel);
    }

    public function test_discards_location_when_ai_says_not_a_real_occurrence(): void
    {
        config(['services.anthropic.key' => 'test-key']);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['text' => json_encode(['ocorrencia_real' => false, 'cidade' => null, 'bairro' => null, 'uf' => null, 'nivel' => 'medio'])],
                ],
            ], 200),
        ]);

        $item = new NewsItem('Filme sobre grande enchente histórica de 1929 estreia no cinema', 'https://g1.globo.com/x', '', null);

        $location = $this->parser()->extract($item);

        $this->assertFalse($location->isRelevantOccurrence());
        $this->assertFalse($location->hasIdentifiableCity());
    }

    public function test_uses_ai_provided_city_but_prioritizes_uf_from_url(): void
    {
        config(['services.anthropic.key' => 'test-key']);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['text' => json_encode(['ocorrencia_real' => true, 'cidade' => 'Manaus', 'bairro' => 'Centro', 'uf' => 'AM', 'nivel' => 'alto'])],
                ],
            ], 200),
        ]);

        // URL indica SP (feed regional), mas isso é hipotético só para testar a prioridade da UF da URL.
        $item = new NewsItem('Alagamento atinge bairro', 'https://g1.globo.com/sp/santos-regiao/noticia/1.ghtml', '', null);

        $location = $this->parser()->extract($item);

        $this->assertSame('Manaus', $location->cidade);
        $this->assertSame('Centro', $location->bairro);
        $this->assertSame('SP', $location->uf);
        $this->assertSame('alto', $location->nivel);
    }
}
