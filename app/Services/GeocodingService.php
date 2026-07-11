<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    protected string $baseUrl = 'https://nominatim.openstreetmap.org/search';

    /** Cache de endereço → coordenadas, evita bater na API para o mesmo endereço de novo. */
    protected int $cacheTtlSeconds = 60 * 60 * 24 * 30; // 30 dias

    /**
     * Nominatim exige no máximo 1 requisição/segundo por cliente.
     * Guardamos o timestamp (em microssegundos) da última chamada nesta instância de processo.
     */
    private static ?float $lastRequestAt = null;

    protected float $minIntervalSeconds = 1.1;

    public function search(string $address): ?array
    {
        $address = trim($address);

        if ($address === '') {
            return null;
        }

        $cacheKey = 'geocoding:' . md5(mb_strtolower($address));

        return Cache::remember($cacheKey, $this->cacheTtlSeconds, function () use ($address) {
            return $this->searchUncached($address);
        });
    }

    private function searchUncached(string $address): ?array
    {
        $this->throttle();

        try {
            $response = Http::withHeaders([
                'User-Agent'      => 'FloodTrack/1.0 (nandafernandes259@gmail.com)',
                'Accept-Language' => 'pt-BR,pt;q=0.9',
            ])
                ->timeout(10)
                ->retry(2, 1000, throw: false)
                ->get($this->baseUrl, [
                    'q'              => $address,
                    'format'         => 'json',
                    'limit'          => 1,
                    'countrycodes'   => 'br',
                    'addressdetails' => 0,
                ]);

            if (! $response->successful()) {
                Log::warning('Nominatim retornou erro', ['address' => $address, 'status' => $response->status()]);

                return null;
            }

            $data = $response->json();

            if (empty($data)) {
                return null;
            }

            return [
                'latitude'  => (float) $data[0]['lat'],
                'longitude' => (float) $data[0]['lon'],
            ];
        } catch (\Throwable $e) {
            Log::error('Erro ao buscar geocoding: ' . $e->getMessage(), ['address' => $address]);

            return null;
        }
    }

    /**
     * Garante pelo menos minIntervalSeconds entre chamadas reais à API,
     * conforme a política de uso do Nominatim.
     */
    private function throttle(): void
    {
        if (self::$lastRequestAt !== null) {
            $elapsed = microtime(true) - self::$lastRequestAt;
            $wait    = $this->minIntervalSeconds - $elapsed;

            if ($wait > 0) {
                usleep((int) ($wait * 1_000_000));
            }
        }

        self::$lastRequestAt = microtime(true);
    }
}
