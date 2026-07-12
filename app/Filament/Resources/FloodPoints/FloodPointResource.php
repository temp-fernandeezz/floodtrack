<?php

namespace App\Filament\Resources\FloodPoints;

use App\Filament\Resources\FloodPoints\Pages\CreateFloodPoint;
use App\Filament\Resources\FloodPoints\Pages\EditFloodPoint;
use App\Filament\Resources\FloodPoints\Pages\ListFloodPoints;
use App\Filament\Resources\FloodPoints\Schemas\FloodPointForm;
use App\Filament\Resources\FloodPoints\Tables\FloodPointsTable;
use App\Models\FloodPoint;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FloodPointResource extends Resource
{
    protected static ?string $model = FloodPoint::class;

    protected static ?string $modelLabel = 'Ponto de alagamento';

    protected static ?string $pluralModelLabel = 'Pontos de alagamento';

    protected static ?string $navigationLabel = 'Pontos de alagamento';

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoramento';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return FloodPointForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FloodPointsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::where('review_status', 'pending')->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['cidade', 'bairro', 'descricao'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFloodPoints::route('/'),
            'create' => CreateFloodPoint::route('/create'),
            'edit' => EditFloodPoint::route('/{record}/edit'),
        ];
    }
}
