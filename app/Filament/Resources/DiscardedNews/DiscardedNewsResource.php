<?php

namespace App\Filament\Resources\DiscardedNews;

use App\Filament\Resources\DiscardedNews\Pages\ListDiscardedNews;
use App\Filament\Resources\DiscardedNews\Tables\DiscardedNewsTable;
use App\Models\NewsArticle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Vitrine somente-leitura das notícias que o scraper descartou (a IA julgou que
 * não relatam uma ocorrência real de alagamento). Existe pra dar visibilidade
 * sobre o que está sendo filtrado — sem isso, um falso negativo da IA some
 * silenciosamente e ninguém percebe.
 */
class DiscardedNewsResource extends Resource
{
    protected static ?string $model = NewsArticle::class;

    protected static ?string $modelLabel = 'Notícia descartada';

    protected static ?string $pluralModelLabel = 'Notícias descartadas';

    protected static ?string $navigationLabel = 'Notícias descartadas';

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoramento';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('discard_reason');
    }

    public static function table(Table $table): Table
    {
        return DiscardedNewsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiscardedNews::route('/'),
        ];
    }
}
