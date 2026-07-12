<?php

namespace App\Filament\Pages;

use App\Settings\FloodTrackScraperSettings;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class FloodTrackScraperSettingsPage extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Scraper e Regras';

    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';

    protected static ?string $title = 'Configuração do Scraper';

    protected static string $settings = FloodTrackScraperSettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Expiração e Deduplicação')
                    ->description('Controla quando um ponto "sai do ar" e quando duas notícias são tratadas como o mesmo evento.')
                    ->extraAttributes(['class' => 'text-base'])
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('expira_after_hours')
                            ->label('Expira após (horas)')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->extraInputAttributes(['class' => 'text-base'])
                            ->helperText('Pontos ativos com ocorrência mais antiga que isso passam a "resolvido" e somem do mapa (mas continuam disponíveis para exportação).'),

                        TextInput::make('dedup_window_hours')
                            ->label('Janela de deduplicação (horas)')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->extraInputAttributes(['class' => 'text-base'])
                            ->helperText('Notícias novas sobre a mesma cidade/bairro dentro dessa janela são consolidadas no ponto existente em vez de criar um novo.'),
                    ]),

                Section::make('Domínios Permitidos')
                    ->description('Se preenchido, o scraper aceitará apenas links desses domínios.')
                    ->extraAttributes(['class' => 'text-base'])
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('allowed_domains')
                            ->schema([
                                TextInput::make('value')
                                    ->label('Domínio')
                                    ->placeholder('g1.globo.com')
                                    ->required()
                                    ->extraInputAttributes(['class' => 'text-base']),
                            ])
                            ->addActionLabel('Adicionar domínio')
                            ->reorderable()
                            ->itemLabel(fn ($state) => $state['value'] ?? 'Domínio'),
                    ]),

                Section::make('RSS Monitorados')
                    ->description('Cadastre os feeds RSS que o scraper deve consultar. Sugestões de feeds regionais do G1: https://g1.globo.com/rss/g1/sp/, https://g1.globo.com/rss/g1/rj/, https://g1.globo.com/rss/g1/mg/ (verificados e válidos).')
                    ->extraAttributes(['class' => 'text-base'])
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Repeater::make('rss_urls')
                            ->schema([
                                TextInput::make('url')
                                    ->label('URL do RSS')
                                    ->placeholder('https://g1.globo.com/rss/g1/sp')
                                    ->url()
                                    ->required()
                                    ->extraInputAttributes(['class' => 'text-base']),
                            ])
                            ->addActionLabel('Adicionar RSS')
                            ->reorderable()
                            ->itemLabel(fn ($state) => $state['url'] ?? 'RSS'),
                    ]),
            ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['rss_urls'] = collect($data['rss_urls'] ?? [])
            ->map(fn ($url) => ['url' => $url])
            ->toArray();

        $data['allowed_domains'] = collect($data['allowed_domains'] ?? [])
            ->map(fn ($domain) => ['value' => $domain])
            ->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['rss_urls'] = collect($data['rss_urls'] ?? [])
            ->pluck('url')
            ->filter()
            ->values()
            ->toArray();

        $data['allowed_domains'] = collect($data['allowed_domains'] ?? [])
            ->pluck('value')
            ->filter()
            ->values()
            ->toArray();

        return $data;
    }
}
