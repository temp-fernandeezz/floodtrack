<?php

namespace App\Filament\Resources\DiscardedNews\Tables;

use App\Models\FloodPoint;
use App\Models\NewsArticle;
use App\Services\GeocodingService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DiscardedNewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->wrap()
                    ->limit(100),
                TextColumn::make('source')
                    ->label('Fonte')
                    ->badge()
                    ->sortable(),
                TextColumn::make('discard_reason')
                    ->label('Motivo do descarte')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('published_at')
                    ->label('Publicada em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('discarded_at')
                    ->label('Descartada em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('discarded_at', 'desc')
            ->filters([
                SelectFilter::make('source')
                    ->label('Fonte')
                    ->options(fn () => \App\Models\NewsArticle::whereNotNull('discard_reason')
                        ->distinct()
                        ->pluck('source', 'source')
                        ->all()),
            ])
            ->recordActions([
                Action::make('ver_noticia')
                    ->label('Ver notícia original')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn ($record) => $record->url)
                    ->openUrlInNewTab(),

                Action::make('adicionar_ao_mapa')
                    ->label('Adicionar ao mapa')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->color('success')
                    ->modalHeading('Reverter descarte e adicionar ao mapa')
                    ->modalDescription('A IA descartou essa notícia por não julgar uma ocorrência real. Preencha a localização manualmente para criar o ponto mesmo assim.')
                    ->modalSubmitActionLabel('Adicionar')
                    ->schema([
                        TextInput::make('cidade')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('uf')
                            ->label('UF')
                            ->maxLength(2)
                            ->dehydrateStateUsing(fn ($state) => $state ? strtoupper($state) : null)
                            ->rule('size:2'),

                        TextInput::make('bairro')
                            ->maxLength(255),

                        Select::make('nivel')
                            ->label('Nível')
                            ->required()
                            ->default('medio')
                            ->options([
                                'baixo' => 'Baixo',
                                'medio' => 'Médio',
                                'alto' => 'Alto',
                            ]),
                    ])
                    ->action(function (array $data, NewsArticle $record) {
                        if (FloodPoint::where('source_url', $record->url)->exists()) {
                            Notification::make()
                                ->title('Já existe um ponto para essa notícia')
                                ->warning()
                                ->send();

                            return;
                        }

                        $geocoding = app(GeocodingService::class);

                        $address = collect([$data['bairro'] ?? null, $data['cidade'], $data['uf'] ?? null, 'Brasil'])
                            ->filter()
                            ->implode(', ');

                        $geo = $geocoding->search($address);

                        if (! $geo && ! empty($data['bairro'])) {
                            $fallback = collect([$data['cidade'], $data['uf'] ?? null, 'Brasil'])->filter()->implode(', ');
                            $geo = $geocoding->search($fallback);
                        }

                        FloodPoint::create([
                            'cidade'          => $data['cidade'],
                            'uf'              => $data['uf'] ?? null,
                            'bairro'          => $data['bairro'] ?? null,
                            'logradouro'      => null,
                            'latitude'        => $geo['latitude'] ?? null,
                            'longitude'       => $geo['longitude'] ?? null,
                            'nivel'           => $data['nivel'],
                            'status'          => 'ativo',
                            'descricao'       => $record->title,
                            'data_ocorrencia' => $record->published_at,
                            'source_type'     => 'news',
                            'source_url'      => $record->url,
                            'review_status'   => $geo ? 'approved' : 'pending',
                            'confidence'      => 60,
                        ]);

                        $record->update([
                            'discard_reason' => null,
                            'discarded_at'   => null,
                        ]);

                        Notification::make()
                            ->title($geo ? 'Ponto adicionado ao mapa' : 'Ponto criado, mas pendente (geocoding não encontrou o endereço)')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
