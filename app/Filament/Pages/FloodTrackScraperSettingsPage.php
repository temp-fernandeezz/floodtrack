<?php

namespace App\Filament\Pages;

use App\Settings\FloodTrackScraperSettings;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class FloodTrackScraperSettingsPage extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Scraper URLs';

    protected static ?string $title = 'Configuração do Scraper';

    protected static string $settings = FloodTrackScraperSettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Expiração e Deduplicação')
                    ->description('Controla quando um ponto "sai do ar" e quando duas notícias são tratadas como o mesmo evento.')
                    ->schema([
                        TextInput::make('expira_after_hours')
                            ->label('Expira após (horas)')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->helperText('Pontos ativos com ocorrência mais antiga que isso passam a "resolvido" e somem do mapa (mas continuam disponíveis para exportação).'),

                        TextInput::make('dedup_window_hours')
                            ->label('Janela de deduplicação (horas)')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->helperText('Notícias novas sobre a mesma cidade/bairro dentro dessa janela são consolidadas no ponto existente em vez de criar um novo.'),
                    ])
                    ->columns(2),

                Section::make('RSS Monitorados')
                    ->description('Cadastre os feeds RSS que o scraper deve consultar. Sugestões de feeds regionais do G1: https://g1.globo.com/rss/g1/sp/, https://g1.globo.com/rss/g1/rj/, https://g1.globo.com/rss/g1/mg/ (verificados e válidos).')
                    ->schema([
                        Repeater::make('rss_urls')
                            ->schema([
                                TextInput::make('url')
                                    ->label('URL do RSS')
                                    ->placeholder('https://g1.globo.com/rss/g1/sp')
                                    ->url()
                                    ->required(),
                            ])
                            ->addActionLabel('Adicionar RSS')
                            ->reorderable()
                            ->itemLabel(fn ($state) => $state['url'] ?? 'RSS'),
                    ]),

                Section::make('Domínios Permitidos')
                    ->description('Se preenchido, o scraper aceitará apenas links desses domínios.')
                    ->schema([
                        Repeater::make('allowed_domains')
                            ->schema([
                                TextInput::make('value')
                                    ->label('Domínio')
                                    ->placeholder('g1.globo.com')
                                    ->required(),
                            ])
                            ->addActionLabel('Adicionar domínio')
                            ->reorderable()
                            ->itemLabel(fn ($state) => $state['value'] ?? 'Domínio'),
                    ]),

                Section::make('Estados Observados')
                    ->description('Configure quais estados e padrões de URL devem ser monitorados.')
                    ->schema([
                        Repeater::make('observed_states')
                            ->schema([
                                Toggle::make('enabled')
                                    ->label('Ativo')
                                    ->default(true),

                                Select::make('state')
                                    ->label('UF')
                                    ->options([
                                        'AC' => 'AC',
                                        'AL' => 'AL',
                                        'AP' => 'AP',
                                        'AM' => 'AM',
                                        'BA' => 'BA',
                                        'CE' => 'CE',
                                        'DF' => 'DF',
                                        'ES' => 'ES',
                                        'GO' => 'GO',
                                        'MA' => 'MA',
                                        'MT' => 'MT',
                                        'MS' => 'MS',
                                        'MG' => 'MG',
                                        'PA' => 'PA',
                                        'PB' => 'PB',
                                        'PR' => 'PR',
                                        'PE' => 'PE',
                                        'PI' => 'PI',
                                        'RJ' => 'RJ',
                                        'RN' => 'RN',
                                        'RS' => 'RS',
                                        'RO' => 'RO',
                                        'RR' => 'RR',
                                        'SC' => 'SC',
                                        'SP' => 'SP',
                                        'SE' => 'SE',
                                        'TO' => 'TO',
                                    ])
                                    ->required(),

                                TextInput::make('label')
                                    ->label('Nome do Estado')
                                    ->placeholder('São Paulo'),

                                Repeater::make('allowed_patterns')
                                    ->label('Padrões Permitidos')
                                    ->schema([
                                        TextInput::make('pattern')
                                            ->label('Trecho da URL')
                                            ->placeholder('/sp/vale-do-paraiba-regiao/')
                                            ->required(),
                                    ])
                                    ->addActionLabel('Adicionar padrão permitido')
                                    ->collapsible()
                                    ->reorderable(),

                                Repeater::make('deny_patterns')
                                    ->label('Padrões Bloqueados')
                                    ->schema([
                                        TextInput::make('pattern')
                                            ->label('Trecho da URL')
                                            ->placeholder('/sp/exemplo-bloqueado/')
                                            ->required(),
                                    ])
                                    ->addActionLabel('Adicionar padrão bloqueado')
                                    ->reorderable(),
                            ])
                            ->addActionLabel('Adicionar Estado')
                            ->reorderable()
                            ->collapsible()

                            ->itemLabel(fn ($state) => $state['state'] ?? 'Estado'),
                    ])->ColumnSpan('full'),
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

        $data['observed_states'] = collect($data['observed_states'] ?? [])
            ->map(function ($state) {
                $state['allowed_patterns'] = collect($state['allowed_patterns'] ?? [])
                    ->map(fn ($pattern) => ['pattern' => $pattern])
                    ->toArray();

                $state['deny_patterns'] = collect($state['deny_patterns'] ?? [])
                    ->map(fn ($pattern) => ['pattern' => $pattern])
                    ->toArray();

                return $state;
            })
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

        $data['observed_states'] = collect($data['observed_states'] ?? [])
            ->map(function ($state) {
                $state['allowed_patterns'] = collect($state['allowed_patterns'] ?? [])
                    ->pluck('pattern')
                    ->filter()
                    ->values()
                    ->toArray();

                $state['deny_patterns'] = collect($state['deny_patterns'] ?? [])
                    ->pluck('pattern')
                    ->filter()
                    ->values()
                    ->toArray();

                return $state;
            })
            ->toArray();

        return $data;
    }
}
