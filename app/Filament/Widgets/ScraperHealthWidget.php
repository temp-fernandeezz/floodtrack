<?php

namespace App\Filament\Widgets;

use App\Services\ScraperHealthRecorder;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Visibilidade operacional do scraper: quando rodou pela última vez e o que fez —
 * sem isso, um scraper travado ou uma execução que falhou silenciosamente passa despercebido.
 */
class ScraperHealthWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    public function getStats(): array
    {
        $recorder = app(ScraperHealthRecorder::class);

        $fetch  = $recorder->lastFetch();
        $expire = $recorder->lastExpire();

        return [
            Stat::make('Última busca de notícias', $fetch['at']?->locale('pt_BR')->diffForHumans() ?? 'Nunca executado')
                ->description($fetch['at']
                    ? "{$fetch['imported']} importados de {$fetch['total']} itens vistos"
                    : 'Aguardando primeira execução agendada')
                ->descriptionIcon(Heroicon::OutlinedRss)
                ->color($this->staleColor($fetch['at'], toleranceMinutes: 20)),

            Stat::make('Última expiração automática', $expire['at']?->locale('pt_BR')->diffForHumans() ?? 'Nunca executado')
                ->description($expire['at']
                    ? "{$expire['count']} ponto(s) marcado(s) como resolvido"
                    : 'Aguardando primeira execução agendada')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color($this->staleColor($expire['at'], toleranceMinutes: 90)),
        ];
    }

    /**
     * Sinaliza em amarelo/vermelho quando a última execução está mais atrasada
     * do que o esperado pelo schedule — indício de scraper travado ou schedule parado.
     */
    private function staleColor(?\Illuminate\Support\Carbon $at, int $toleranceMinutes): string
    {
        if (! $at) {
            return 'gray';
        }

        return $at->diffInMinutes(now()) > $toleranceMinutes ? 'danger' : 'success';
    }
}
