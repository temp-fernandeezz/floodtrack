<?php

namespace App\Services\FloodNews;

use App\Models\FloodPoint;
use App\Services\GeocodingService;
use Illuminate\Support\Facades\Log;

/**
 * Cria o FloodPoint a partir de uma notícia já filtrada e com local extraído.
 *
 * Se não há cidade identificável ou o geocoding falha, o ponto ainda é salvo
 * (sem coordenadas, com review_status = pending) para aparecer nos cards de
 * revisão manual — só pontos com coordenadas entram no mapa.
 */
class FloodPointImporter
{
    public function __construct(
        private readonly GeocodingService $geocoding,
    ) {
    }

    public function import(NewsItem $item, ExtractedLocation $location, string $sourceType, string $sourceUrl, ?string $publishedAt): FloodPoint
    {
        if (! $location->hasIdentifiableCity()) {
            return $this->createPending($item, $location, $sourceType, $sourceUrl, $publishedAt, cidade: 'Não identificada');
        }

        $geo = $this->geocode($location);

        if (! $geo) {
            Log::warning('Geocoding não encontrado', [
                'cidade' => $location->cidade,
                'bairro' => $location->bairro,
                'uf'     => $location->uf,
            ]);

            return $this->createPending($item, $location, $sourceType, $sourceUrl, $publishedAt);
        }

        return FloodPoint::create([
            'cidade'          => $location->cidade,
            'uf'              => $location->uf,
            'bairro'          => $location->bairro,
            'logradouro'      => null,
            'latitude'        => $geo['latitude'],
            'longitude'       => $geo['longitude'],
            'nivel'           => $location->nivel,
            'status'          => 'ativo',
            'descricao'       => $item->title,
            'data_ocorrencia' => $publishedAt,
            'source_type'     => $sourceType,
            'source_url'      => $sourceUrl,
            'review_status'   => 'approved',
            'confidence'      => $location->confidence,
        ]);
    }

    private function geocode(ExtractedLocation $location): ?array
    {
        $address = collect([$location->bairro, $location->cidade, $location->uf, 'Brasil'])->filter()->implode(', ');
        $geo     = $this->geocoding->search($address);

        if (! $geo && $location->bairro) {
            $fallback = collect([$location->cidade, $location->uf, 'Brasil'])->filter()->implode(', ');
            $geo      = $this->geocoding->search($fallback);
        }

        return $geo;
    }

    private function createPending(
        NewsItem $item,
        ExtractedLocation $location,
        string $sourceType,
        string $sourceUrl,
        ?string $publishedAt,
        ?string $cidade = null,
    ): FloodPoint {
        return FloodPoint::create([
            'cidade'          => $cidade ?? $location->cidade,
            'uf'              => $location->uf,
            'bairro'          => $location->bairro,
            'logradouro'      => null,
            'latitude'        => null,
            'longitude'       => null,
            'nivel'           => $location->nivel,
            'status'          => 'ativo',
            'descricao'       => $item->title,
            'data_ocorrencia' => $publishedAt,
            'source_type'     => $sourceType,
            'source_url'      => $sourceUrl,
            'review_status'   => 'pending',
            'confidence'      => $location->confidence,
        ]);
    }
}
