<?php

namespace App\Filament\Resources\FloodPoints\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FloodPointsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cidade')->searchable()->sortable(),
                TextColumn::make('bairro')->searchable(),
                TextColumn::make('nivel')->badge()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('data_ocorrencia')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('created_at')->since()->label('Criado'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
