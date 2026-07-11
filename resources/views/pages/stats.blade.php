@extends('layouts.app')

@section('title', 'Estatísticas')
@section('description', 'Dados e estatísticas do sistema FloodTrack — pontos de alagamento detectados, cidades monitoradas e distribuição por severidade.')

@push('seo')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    {{-- Cabeçalho --}}
    <div class="mb-6 flex items-start justify-between gap-4 print:mb-4">
        <div>
            <h1 class="text-3xl font-semibold text-black print:text-2xl">Relatório de Ocorrências</h1>
            <p class="mt-1 text-zinc-500 print:text-sm">
                FloodTrack · Gerado em {{ now()->format('d/m/Y \à\s H:i') }}
                @if($primeira && $ultima)
                    · Período: {{ \Carbon\Carbon::parse($primeira)->format('d/m/Y') }}
                    a {{ \Carbon\Carbon::parse($ultima)->format('d/m/Y') }}
                @endif
            </p>
        </div>

        <button onclick="window.print()"
            class="flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700 print:hidden">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Exportar PDF
        </button>
    </div>

    {{-- Cards principais --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4 print:grid-cols-4 print:gap-3">
        @php
            $cards = [
                ['label' => 'Total de ocorrências', 'value' => number_format($total), 'color' => 'violet'],
                ['label' => 'Com coordenadas no mapa', 'value' => number_format($comCoords) . ' (' . ($total > 0 ? round($comCoords / $total * 100) : 0) . '%)', 'color' => 'blue'],
                ['label' => 'Ocorrências ativas', 'value' => number_format($ativos), 'color' => 'red'],
                ['label' => 'Notícias processadas', 'value' => number_format($noticias), 'color' => 'emerald'],
            ];
        @endphp

        @foreach($cards as $card)
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm print:rounded-lg print:p-3">
                <div class="text-xs font-medium uppercase tracking-wide text-zinc-500 print:text-[10px]">
                    {{ $card['label'] }}
                </div>
                <div class="mt-2 text-3xl font-bold text-zinc-900 print:text-2xl">
                    {{ $card['value'] }}
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3 print:grid-cols-3 print:gap-4">

        {{-- Distribuição por nível --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm print:rounded-lg print:p-4">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-500">Por nível de severidade</h2>

            @php
                $nivelConfig = [
                    'alto'  => ['label' => 'Alto',  'color' => 'bg-red-500',     'text' => 'text-red-700'],
                    'medio' => ['label' => 'Médio', 'color' => 'bg-yellow-400',  'text' => 'text-yellow-700'],
                    'baixo' => ['label' => 'Baixo', 'color' => 'bg-emerald-500', 'text' => 'text-emerald-700'],
                ];
                $maxNivel = $porNivel->max() ?: 1;
            @endphp

            <div class="space-y-3">
                @foreach($nivelConfig as $key => $cfg)
                    @php $count = $porNivel->get($key, 0); @endphp
                    <div>
                        <div class="mb-1 flex justify-between text-sm">
                            <span class="font-medium {{ $cfg['text'] }}">{{ $cfg['label'] }}</span>
                            <span class="text-zinc-600">{{ number_format($count) }}</span>
                        </div>
                        <div class="h-3 w-full overflow-hidden rounded-full bg-zinc-100">
                            <div class="{{ $cfg['color'] }} h-3 rounded-full transition-all"
                                style="width: {{ $maxNivel > 0 ? round($count / $maxNivel * 100) : 0 }}%">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Top estados --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm print:rounded-lg print:p-4">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-500">Top estados</h2>

            @php $maxUf = $porUf->first()?->total ?: 1; @endphp

            <div class="space-y-2">
                @forelse($porUf as $row)
                    <div>
                        <div class="mb-1 flex justify-between text-sm">
                            <span class="font-medium text-zinc-800">{{ $row->uf }}</span>
                            <span class="text-zinc-500">{{ number_format($row->total) }}</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100">
                            <div class="h-2 rounded-full bg-violet-500"
                                style="width: {{ round($row->total / $maxUf * 100) }}%">
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-400">Nenhum dado disponível.</p>
                @endforelse
            </div>
        </div>

        {{-- Top cidades --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm print:rounded-lg print:p-4">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-500">Top cidades</h2>

            @php $maxCidade = $porCidade->first()?->total ?: 1; @endphp

            <div class="space-y-2">
                @forelse($porCidade as $row)
                    <div>
                        <div class="mb-1 flex justify-between text-sm">
                            <span class="font-medium text-zinc-800 truncate pr-2">
                                {{ $row->cidade }}
                                @if($row->uf)
                                    <span class="text-zinc-400 font-normal">{{ $row->uf }}</span>
                                @endif
                            </span>
                            <span class="shrink-0 text-zinc-500">{{ number_format($row->total) }}</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100">
                            <div class="h-2 rounded-full bg-blue-500"
                                style="width: {{ round($row->total / $maxCidade * 100) }}%">
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-400">Nenhum dado disponível.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Ocorrências por dia (últimos 30 dias) --}}
    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm print:mt-4 print:rounded-lg print:p-4">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-500">
            Ocorrências detectadas — últimos 30 dias
        </h2>

        @php
            $days    = collect();
            $maxDay  = 0;
            for ($i = 29; $i >= 0; $i--) {
                $d     = now()->subDays($i)->format('Y-m-d');
                $label = now()->subDays($i)->format('d/m');
                $count = $porDia->get($d, 0);
                $days->push(['label' => $label, 'count' => $count]);
                if ($count > $maxDay) $maxDay = $count;
            }
            $maxDay = max($maxDay, 1);
        @endphp

        <div class="flex h-32 items-end gap-1 print:h-24">
            @foreach($days as $day)
                <div class="group relative flex flex-1 flex-col items-center justify-end">
                    <div class="w-full rounded-t bg-violet-500 transition-all print:bg-violet-400"
                        style="height: {{ round($day['count'] / $maxDay * 100) }}%"
                        title="{{ $day['label'] }}: {{ $day['count'] }}">
                    </div>
                </div>
            @endforeach
        </div>

        {{-- labels de data (a cada 5 dias) --}}
        <div class="mt-1 flex gap-1 text-[10px] text-zinc-400">
            @foreach($days as $i => $day)
                <div class="flex-1 text-center {{ $i % 5 !== 0 ? 'invisible' : '' }}">
                    {{ $day['label'] }}
                </div>
            @endforeach
        </div>
    </div>

    {{-- Metodologia --}}
    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm print:mt-4 print:rounded-lg print:p-4">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-zinc-500">Metodologia</h2>
        <div class="grid gap-4 text-sm text-zinc-600 sm:grid-cols-3 print:grid-cols-3">
            <div>
                <div class="font-semibold text-zinc-800">Coleta de dados</div>
                <p class="mt-1">Feeds RSS regionais do G1 cobrindo todos os 27 estados brasileiros, monitorados automaticamente a cada 10 minutos.</p>
            </div>
            <div>
                <div class="font-semibold text-zinc-800">Extração de localização</div>
                <p class="mt-1">Processamento com IA (Claude Haiku) para identificar cidade, bairro e estado a partir do texto das notícias, com fallback para expressões regulares.</p>
            </div>
            <div>
                <div class="font-semibold text-zinc-800">Geocodificação</div>
                <p class="mt-1">Coordenadas geográficas obtidas via Nominatim (OpenStreetMap), restrito ao Brasil, com fallback progressivo de endereço completo para apenas cidade.</p>
            </div>
        </div>
    </div>

    {{-- Rodapé de impressão --}}
    <div class="mt-4 hidden text-center text-xs text-zinc-400 print:block">
        FloodTrack · {{ config('app.url') }} · Gerado em {{ now()->format('d/m/Y H:i') }}
    </div>
@endsection

@push('seo')
<style>
    @media print {
        header, footer, nav { display: none !important; }
        body { background: white !important; }
        main { padding: 0 !important; max-width: 100% !important; }
        .shadow-sm { box-shadow: none !important; }
    }
</style>
@endpush
