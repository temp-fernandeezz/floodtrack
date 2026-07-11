<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Chuva recente próxima a uma coordenada, via OpenWeatherMap (Current Weather Data).
 *
 * Sem chave configurada, o serviço fica silenciosamente desativado (mesmo padrão do
 * LocationExtractorService::isConfigured()) — nunca quebra a aplicação por falta dela.
 *
 * Cadastro gratuito (self-service, confirmado): https://openweathermap.org/api
 */
class RainfallService
{
    private string $baseUrl = 'https://api.openweathermap.org/data/2.5/weather';

    public function isConfigured(): bool
    {
        return ! empty(config('services.openweathermap.key'));
    }

    /**
     * @return array{mm_recente: float, local: string}|null
     */
    public function chuvaRecente(float $lat, float $lng): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        // Arredonda pra ~1km — evita bater na API a cada pequeno movimento do mapa.
        $cacheKey = sprintf('owm:rain:%.2f:%.2f', $lat, $lng);

        return Cache::remember($cacheKey, now()->addMinutes(15), fn () => $this->fetch($lat, $lng));
    }

    private function fetch(float $lat, float $lng): ?array
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl, [
                'lat'   => $lat,
                'lon'   => $lng,
                'appid' => config('services.openweathermap.key'),
                'units' => 'metric',
                'lang'  => 'pt_br',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Erro ao buscar chuva na OpenWeatherMap: ' . $e->getMessage());

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        // "rain" só existe no payload quando está chovendo; some quando não há precipitação.
        $mm = (float) ($data['rain']['1h'] ?? $data['rain']['3h'] ?? 0);

        return [
            'mm_recente' => round($mm, 1),
            'local'      => $data['name'] ?? '',
        ];
    }
}
