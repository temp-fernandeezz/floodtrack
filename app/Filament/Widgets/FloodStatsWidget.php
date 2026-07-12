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
        $noticias = NewsArticle::count();

        $pendingNews    = FloodPoint::where('review_status', 'pending')->where('source_type', 'news')->count();
        $pendingCitizen = FloodPoint::where('review_status', 'pending')->where('source_type', 'citizen')->count();

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

            Stat::make('Notícias pendentes', $pendingNews)
                ->description('Importadas pelo scraper, aguardando revisão')
                ->descriptionIcon(Heroicon::OutlinedNewspaper)
                ->color($pendingNews > 0 ? 'warning' : 'success'),

            Stat::make('Reportes cidadão pendentes', $pendingCitizen)
                ->description('Enviados pelo formulário público')
                ->descriptionIcon(Heroicon::OutlinedUserGroup)
                ->color($pendingCitizen > 0 ? 'warning' : 'success'),

            Stat::make('Notícias processadas', $noticias)
                ->description("{$comCoords}% dos pontos com coordenadas")
                ->descriptionIcon(Heroicon::OutlinedGlobeAlt)
                ->color('success'),
        ];
    }
}
