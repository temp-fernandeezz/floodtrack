<?php

namespace Tests\Feature\Services;

use App\Services\GeocodingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeocodingServiceTest extends TestCase
{
    public function test_returns_coordinates_from_first_result(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '-23.550520', 'lon' => '-46.633308'],
            ], 200),
        ]);

        $result = (new GeocodingService())->search('Praça da Sé, São Paulo, SP, Brasil');

        $this->assertSame(-23.550520, $result['latitude']);
        $this->assertSame(-46.633308, $result['longitude']);
    }

    public function test_returns_null_when_no_results_found(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([], 200),
        ]);

        $result = (new GeocodingService())->search('endereço que não existe em lugar nenhum');

        $this->assertNull($result);
    }

    public function test_returns_null_when_address_is_empty(): void
    {
        $result = (new GeocodingService())->search('   ');

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_returns_null_on_http_error(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response('erro', 500),
        ]);

        $result = (new GeocodingService())->search('Rua Exemplo, Cidade Exemplo, SP, Brasil');

        $this->assertNull($result);
    }

    public function test_caches_result_and_does_not_hit_http_twice_for_same_address(): void
    {
        Cache::flush();

        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '-23.5', 'lon' => '-46.6'],
            ], 200),
        ]);

        $service = new GeocodingService();
        $service->search('Mesmo Endereço, São Paulo, SP, Brasil');
        $service->search('Mesmo Endereço, São Paulo, SP, Brasil');

        Http::assertSentCount(1);
    }
}
