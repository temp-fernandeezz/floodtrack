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

    public function import(NewsItem $item, ExtractedLocation $location, string $sourceType, string $sourceUrl, ?string $publishedAt, int $dedupWindowHours = 12): FloodPoint
    {
        if (! $location->hasIdentifiableCity()) {
            return $this->createPending($item, $location, $sourceType, $sourceUrl, $publishedAt, cidade: 'Não identificada');
        }

        $duplicate = $this->findDuplicate($location, $dedupWindowHours);

        if ($duplicate) {
            return $this->mergeIntoDuplicate($duplicate, $location, $publishedAt);
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

    /**
     * Procura um ponto ativo recente pra mesma cidade/bairro — evita pin duplicado
     * quando duas notícias diferentes cobrem o mesmo evento de alagamento.
     */
    private function findDuplicate(ExtractedLocation $location, int $windowHours): ?FloodPoint
    {
        return FloodPoint::query()
            ->where('cidade', $location->cidade)
            ->where('status', 'ativo')
            ->where(function ($q) use ($location) {
                $location->bairro
                    ? $q->where('bairro', $location->bairro)
                    : $q->whereNull('bairro');
            })
            ->where('data_ocorrencia', '>=', now()->subHours($windowHours))
            ->orderByDesc('data_ocorrencia')
            ->first();
    }

    private function mergeIntoDuplicate(FloodPoint $existing, ExtractedLocation $location, ?string $publishedAt): FloodPoint
    {
        $existing->merged_sources_count++;
        $existing->confidence = max($existing->confidence, $location->confidence);

        if ($publishedAt && (! $existing->data_ocorrencia || $publishedAt > $existing->data_ocorrencia)) {
            $existing->data_ocorrencia = $publishedAt;
        }

        $severityOrder = ['baixo' => 1, 'medio' => 2, 'alto' => 3];

        if (($severityOrder[$location->nivel] ?? 0) > ($severityOrder[$existing->nivel] ?? 0)) {
            $existing->nivel = $location->nivel;
        }

        $existing->save();

        return $existing;
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
