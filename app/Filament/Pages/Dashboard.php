<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\FloodStatsWidget;
use App\Filament\Widgets\PendingReviewWidget;
use App\Filament\Widgets\ScraperHealthWidget;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            FloodStatsWidget::class,
            ScraperHealthWidget::class,
            PendingReviewWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fetchNews')
                ->label('Buscar notícias agora')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('primary')
                ->requiresConfirmation()
                ->modalDescription('Roda o scraper de RSS agora, sem esperar os 10 minutos do agendamento. Pode levar alguns segundos.')
                ->action(function () {
                    Artisan::call('flood:fetch-news');

                    Notification::make()
                        ->title('Busca de notícias concluída')
                        ->body(trim(Artisan::output()) ?: 'Nenhuma saída do comando.')
                        ->success()
                        ->send();
                }),

            Action::make('expirePoints')
                ->label('Expirar pontos antigos agora')
                ->icon(Heroicon::OutlinedClock)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Marca como resolvido os pontos ativos mais antigos que o tempo configurado nas Settings, sem esperar a próxima execução agendada.')
                ->action(function () {
                    Artisan::call('flood:expire-points');

                    Notification::make()
                        ->title('Expiração concluída')
                        ->body(trim(Artisan::output()) ?: 'Nenhuma saída do comando.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
