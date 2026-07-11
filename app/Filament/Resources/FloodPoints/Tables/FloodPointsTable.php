<?php

namespace App\Filament\Resources\FloodPoints\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class FloodPointsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cidade')->searchable()->sortable(),
                TextColumn::make('uf')->label('UF')->sortable(),
                TextColumn::make('bairro')->searchable()->toggleable(),
                TextColumn::make('nivel')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state) => match ($state) {
                        'alto'  => 'danger',
                        'medio' => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('source_type')
                    ->label('Origem')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state) => match ($state) {
                        'citizen' => 'info',
                        'news'    => 'gray',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'citizen' => 'Reporte cidadão',
                        'news'    => 'Notícia',
                        default   => 'Manual',
                    }),
                TextColumn::make('review_status')
                    ->label('Revisão')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state) => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'warning',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'approved' => 'Aprovado',
                        'rejected' => 'Rejeitado',
                        default    => 'Pendente',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state) => match ($state) {
                        'ativo'     => 'info',
                        'resolvido' => 'gray',
                        default     => 'gray',
                    }),
                TextColumn::make('confidence')
                    ->label('Confiança')
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('data_ocorrencia')
                    ->label('Ocorrência')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('source_type')
                    ->label('Origem')
                    ->options([
                        'citizen' => 'Reporte cidadão',
                        'news'    => 'Notícia',
                        'manual'  => 'Manual',
                    ]),
                SelectFilter::make('review_status')
                    ->label('Revisão')
                    ->options([
                        'pending'  => 'Pendente',
                        'approved' => 'Aprovado',
                        'rejected' => 'Rejeitado',
                    ]),
                SelectFilter::make('nivel')
                    ->label('Nível')
                    ->options([
                        'baixo' => 'Baixo',
                        'medio' => 'Médio',
                        'alto'  => 'Alto',
                    ]),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'ativo'     => 'Ativo',
                        'resolvido' => 'Resolvido',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->label('Aprovar selecionados')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Aprovar pontos selecionados')
                        ->modalDescription('Os pontos aprovados passarão a aparecer no mapa público.')
                        ->modalSubmitActionLabel('Aprovar')
                        ->action(function (Collection $records) {
                            $records->each->update(['review_status' => 'approved']);
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Pontos aprovados com sucesso'),

                    BulkAction::make('reject')
                        ->label('Rejeitar selecionados')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Rejeitar pontos selecionados')
                        ->modalDescription('Os pontos rejeitados não aparecerão no mapa público.')
                        ->modalSubmitActionLabel('Rejeitar')
                        ->action(function (Collection $records) {
                            $records->each->update(['review_status' => 'rejected']);
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Pontos rejeitados'),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
