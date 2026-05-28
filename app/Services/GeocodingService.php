<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeocodingService
{
    protected string $baseUrl = 'https://nominatim.openstreetmap.org/search';

    public function search(string $address): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent'      => 'FloodTrack/1.0 (nandafernandes259@gmail.com)',
                'Accept-Language' => 'pt-BR,pt;q=0.9',
            ])->get($this->baseUrl, [
                'q'            => $address,
                'format'       => 'json',
                'limit'        => 1,
                'countrycodes' => 'br',
                'addressdetails' => 0,
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            if (empty($data)) {
                return null;
            }

            return [
                'latitude' => (float) $data[0]['lat'],
                'longitude' => (float) $data[0]['lon'],
            ];

        } catch (\Exception $e) {
            \Log::error('Erro ao buscar geocoding: ' . $e->getMessage());
            return null;
        }
    }
}
