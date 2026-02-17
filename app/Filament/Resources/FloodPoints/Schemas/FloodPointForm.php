<?php

namespace App\Filament\Resources\FloodPoints\Schemas;

use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FloodPointForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Localização')
                    ->schema([
                        Forms\Components\TextInput::make('cidade')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('uf')
                            ->label('UF')
                            ->maxLength(2)
                            ->dehydrateStateUsing(fn ($state) => $state ? strtoupper($state) : null)
                            ->rule('size:2'),

                        Forms\Components\TextInput::make('bairro')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('logradouro')
                            ->label('Logradouro (rua/avenida)')
                            ->maxLength(255),

                        Grid::make(1)->schema([
                            Forms\Components\TextInput::make('latitude')
                                ->numeric()
                                ->rule('between:-90,90')
                                ->helperText('Opcional. Se vazio, o ponto fica pendente para ajuste no mapa.'),

                            Forms\Components\TextInput::make('longitude')
                                ->numeric()
                                ->rule('between:-180,180')
                                ->helperText('Opcional. Se vazio, o ponto fica pendente para ajuste no mapa.'),
                        ]),
                    ])
                    ->columns(2),

                Section::make('Ocorrência')
                    ->schema([
                        Forms\Components\Select::make('nivel')
                            ->required()
                            ->options([
                                'baixo' => 'Baixo',
                                'medio' => 'Médio',
                                'alto' => 'Alto',
                            ]),

                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'ativo' => 'Ativo',
                                'resolvido' => 'Resolvido',
                            ])
                            ->default('ativo'),

                        Forms\Components\DateTimePicker::make('data_ocorrencia')
                            ->label('Data/Hora da ocorrência')
                            ->seconds(false)
                            ->default(now()),

                        Forms\Components\Textarea::make('descricao')
                            ->columnSpanFull()
                            ->rows(4),

                        // --- Campos “babadeiros” pra automação por notícia (se existirem no banco) ---

                        Forms\Components\Select::make('review_status')
                            ->label('Revisão')
                            ->options([
                                'pending' => 'Pendente',
                                'approved' => 'Aprovado',
                                'rejected' => 'Rejeitado',
                            ])
                            ->default('approved')
                            ->visible(fn () => self::fieldExists('review_status')),

                        Forms\Components\TextInput::make('confidence')
                            ->label('Confiança (0-100)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->visible(fn () => self::fieldExists('confidence')),

                        Forms\Components\TextInput::make('source_url')
                            ->label('Link da notícia')
                            ->url()
                            ->visible(fn () => self::fieldExists('source_url')),

                        Forms\Components\Hidden::make('source_type')
                            ->default('manual')
                            ->visible(fn () => self::fieldExists('source_type')),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Pequeno helper: evita quebrar o form se você ainda não criou as colunas no banco.
     */
    private static function fieldExists(string $column): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasColumn('flood_points', $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
