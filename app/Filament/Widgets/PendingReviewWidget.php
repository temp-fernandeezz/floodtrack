<?php

namespace App\Filament\Widgets;

use App\Models\FloodPoint;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Fila de moderação direto no dashboard — aprova/rejeita sem precisar navegar
 * até o resource de Pontos de Alagamento.
 */
class PendingReviewWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FloodPoint::query()->where('review_status', 'pending')->latest('created_at')
            )
            ->heading('Fila de moderação')
            ->description('Notícias e reportes cidadão aguardando aprovação')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('cidade')
                    ->label('Local')
                    ->searchable()
                    ->formatStateUsing(fn (string $state, FloodPoint $record) => $state . ($record->uf ? ', ' . $record->uf : '')),
                TextColumn::make('source_type')
                    ->label('Origem')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'citizen' => 'Reporte cidadão',
                        'news'    => 'Notícia',
                        default   => 'Manual',
                    })
                    ->color(fn (string $state) => $state === 'citizen' ? 'info' : 'gray'),
                TextColumn::make('nivel')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'alto'  => 'danger',
                        'medio' => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('descricao')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Recebido em')
                    ->formatStateUsing(fn ($state) => $state?->locale('pt_BR')->diffForHumans())
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Aprovar')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (FloodPoint $record) => $record->update(['review_status' => 'approved'])),

                Action::make('reject')
                    ->label('Rejeitar')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (FloodPoint $record) => $record->update(['review_status' => 'rejected'])),
            ])
            ->emptyStateHeading('Nada pendente')
            ->emptyStateDescription('Todos os pontos já foram revisados.')
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle);
    }
}
