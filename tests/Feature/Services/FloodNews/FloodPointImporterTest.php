<?php

namespace Tests\Feature\Services\FloodNews;

use App\Models\FloodPoint;
use App\Services\FloodNews\ExtractedLocation;
use App\Services\FloodNews\FloodPointImporter;
use App\Services\FloodNews\NewsItem;
use App\Services\GeocodingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FloodPointImporterTest extends TestCase
{
    use RefreshDatabase;

    private function item(string $title = 'Rua alagada em Santos'): NewsItem
    {
        return new NewsItem($title, 'https://exemplo.com/noticia', '', null);
    }

    private function fakeGeocodingSuccess(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '-23.96', 'lon' => '-46.33'],
            ], 200),
        ]);
    }

    private function fakeGeocodingFailure(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([], 200),
        ]);
    }

    public function test_creates_approved_point_with_coordinates_when_geocoding_succeeds(): void
    {
        $this->fakeGeocodingSuccess();

        $location = new ExtractedLocation(bairro: 'Gonzaga', cidade: 'Santos', uf: 'SP', nivel: 'medio', confidence: 70);
        $importer = new FloodPointImporter(new GeocodingService());

        $point = $importer->import($this->item(), $location, 'news', 'https://exemplo.com/noticia', now()->toDateTimeString());

        $this->assertTrue($point->wasRecentlyCreated);
        $this->assertSame('approved', $point->review_status);
        $this->assertSame(-23.96, $point->latitude);
        $this->assertSame(-46.33, $point->longitude);
        $this->assertSame('ativo', $point->status);
    }

    public function test_creates_pending_point_without_coordinates_when_geocoding_fails(): void
    {
        $this->fakeGeocodingFailure();

        $location = new ExtractedLocation(bairro: 'Bairro Inexistente', cidade: 'Cidade Fictícia', uf: 'SP', nivel: 'baixo', confidence: 40);
        $importer = new FloodPointImporter(new GeocodingService());

        $point = $importer->import($this->item(), $location, 'news', 'https://exemplo.com/noticia', now()->toDateTimeString());

        $this->assertTrue($point->wasRecentlyCreated);
        $this->assertSame('pending', $point->review_status);
        $this->assertNull($point->latitude);
        $this->assertNull($point->longitude);
        // O ponto continua sendo salvo (não é descartado) para aparecer nos cards de revisão manual.
        $this->assertDatabaseHas('flood_points', ['descricao' => $this->item()->title, 'review_status' => 'pending']);
    }

    public function test_creates_pending_point_when_no_city_identified(): void
    {
        $location = new ExtractedLocation(bairro: null, cidade: null, uf: null, nivel: 'medio', confidence: 30);
        $importer = new FloodPointImporter(new GeocodingService());

        $point = $importer->import($this->item(), $location, 'news', 'https://exemplo.com/noticia', now()->toDateTimeString());

        $this->assertSame('pending', $point->review_status);
        $this->assertSame('Não identificada', $point->cidade);
        Http::assertNothingSent();
    }

    public function test_merges_into_existing_active_point_in_same_city_and_neighborhood(): void
    {
        $this->fakeGeocodingSuccess();

        $existing = FloodPoint::create([
            'cidade' => 'Santos', 'uf' => 'SP', 'bairro' => 'Gonzaga',
            'latitude' => -23.96, 'longitude' => -46.33,
            'nivel' => 'baixo', 'status' => 'ativo', 'descricao' => 'Primeira notícia',
            'data_ocorrencia' => now()->subHours(2), 'source_type' => 'news',
            'source_url' => 'https://exemplo.com/primeira', 'review_status' => 'approved',
            'confidence' => 50, 'merged_sources_count' => 1,
        ]);

        $location = new ExtractedLocation(bairro: 'Gonzaga', cidade: 'Santos', uf: 'SP', nivel: 'alto', confidence: 80);
        $importer = new FloodPointImporter(new GeocodingService());

        $point = $importer->import($this->item('Segunda notícia sobre o mesmo alagamento'), $location, 'news', 'https://exemplo.com/segunda', now()->toDateTimeString());

        $this->assertFalse($point->wasRecentlyCreated);
        $this->assertSame($existing->id, $point->id);
        $this->assertSame(2, $point->merged_sources_count);
        $this->assertSame(80, $point->confidence);
        // Severidade sobe para a mais alta entre as fontes consolidadas.
        $this->assertSame('alto', $point->nivel);
        $this->assertSame(1, FloodPoint::count());
    }

    public function test_does_not_merge_points_in_different_neighborhoods(): void
    {
        $this->fakeGeocodingSuccess();

        FloodPoint::create([
            'cidade' => 'Santos', 'uf' => 'SP', 'bairro' => 'Gonzaga',
            'latitude' => -23.96, 'longitude' => -46.33,
            'nivel' => 'baixo', 'status' => 'ativo', 'descricao' => 'Outro bairro',
            'data_ocorrencia' => now()->subHours(1), 'source_type' => 'news',
            'source_url' => 'https://exemplo.com/outra', 'review_status' => 'approved',
            'confidence' => 50, 'merged_sources_count' => 1,
        ]);

        $location = new ExtractedLocation(bairro: 'Boqueirão', cidade: 'Santos', uf: 'SP', nivel: 'medio', confidence: 60);
        $importer = new FloodPointImporter(new GeocodingService());

        $point = $importer->import($this->item(), $location, 'news', 'https://exemplo.com/noticia', now()->toDateTimeString());

        $this->assertTrue($point->wasRecentlyCreated);
        $this->assertSame(2, FloodPoint::count());
    }

    public function test_does_not_merge_points_outside_dedup_window(): void
    {
        $this->fakeGeocodingSuccess();

        FloodPoint::create([
            'cidade' => 'Santos', 'uf' => 'SP', 'bairro' => 'Gonzaga',
            'latitude' => -23.96, 'longitude' => -46.33,
            'nivel' => 'baixo', 'status' => 'ativo', 'descricao' => 'Notícia antiga',
            'data_ocorrencia' => now()->subHours(20), 'source_type' => 'news',
            'source_url' => 'https://exemplo.com/antiga', 'review_status' => 'approved',
            'confidence' => 50, 'merged_sources_count' => 1,
        ]);

        $location = new ExtractedLocation(bairro: 'Gonzaga', cidade: 'Santos', uf: 'SP', nivel: 'medio', confidence: 60);
        $importer = new FloodPointImporter(new GeocodingService());

        // janela de dedup padrão de 12h — a notícia de 20h atrás não deve ser considerada duplicata.
        $point = $importer->import($this->item(), $location, 'news', 'https://exemplo.com/nova', now()->toDateTimeString(), dedupWindowHours: 12);

        $this->assertTrue($point->wasRecentlyCreated);
        $this->assertSame(2, FloodPoint::count());
    }
}
