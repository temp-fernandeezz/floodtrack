<?php

namespace App\Services;

use App\Models\FloodPoint;
use Illuminate\Support\Collection;

/**
 * Calcula um ranking de risco histórico por cidade, a partir da frequência e
 * severidade das ocorrências aprovadas nos últimos N dias. Não depende de
 * dados de chuva em tempo real — é só um retrato do histórico registrado.
 */
class FloodRiskScoreService
{
    private const SEVERITY_WEIGHTS = ['baixo' => 1, 'medio' => 2, 'alto' => 3];

    public function rankingPorCidade(int $days = 90, int $limit = 15): Collection
    {
        $since = now()->subDays($days);

        $rows = FloodPoint::query()
            ->selectRaw(
                "cidade, uf, count(*) as ocorrencias,
                SUM(CASE nivel WHEN 'alto' THEN 3 WHEN 'medio' THEN 2 ELSE 1 END) as pontuacao_bruta"
            )
            ->where('review_status', 'approved')
            ->where('data_ocorrencia', '>=', $since)
            ->whereNotNull('cidade')
            ->groupBy('cidade', 'uf')
            ->orderByDesc('pontuacao_bruta')
            ->limit($limit)
            ->get();

        $max = $rows->max('pontuacao_bruta') ?: 1;

        return $rows->map(function ($row) use ($max) {
            $row->score = (int) round($row->pontuacao_bruta / $max * 100);
            $row->nivel_risco = match (true) {
                $row->score >= 70 => 'alto',
                $row->score >= 35 => 'medio',
                default => 'baixo',
            };

            return $row;
        });
    }
}
