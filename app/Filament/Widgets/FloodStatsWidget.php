<?php

namespace App\Filament\Widgets;

use App\Models\FloodPoint;
use App\Models\NewsArticle;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FloodStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $total    = FloodPoint::count();
        $ativos   = FloodPoint::where('status', 'ativo')->count();
        $pending  = FloodPoint::where('review_status', 'pending')->count();
        $noticias = NewsArticle::count();

        $semCoords = FloodPoint::where(function ($q) {
            $q->whereNull('latitude')->orWhereNull('longitude')
              ->orWhere('latitude', 0)->orWhere('longitude', 0);
        })->count();

        $comCoords = $total > 0
            ? round((($total - $semCoords) / $total) * 100)
            : 0;

        return [
            Stat::make('Total de pontos', $total)
                ->description('Todos os pontos registrados')
                ->descriptionIcon(Heroicon::OutlinedMapPin)
                ->color('primary'),

            Stat::make('Pontos ativos', $ativos)
                ->description('Ocorrências em andamento')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger'),

            Stat::make('Aguardando revisão', $pending)
                ->description('Pontos importados pendentes')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color('warning'),

            Stat::make('Notícias processadas', $noticias)
                ->description("{$comCoords}% com coordenadas")
                ->descriptionIcon(Heroicon::OutlinedNewspaper)
                ->color('success'),
        ];
    }
}
