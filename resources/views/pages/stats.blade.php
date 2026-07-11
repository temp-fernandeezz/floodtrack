@extends('layouts.app')

@section('title', 'Estatísticas')
@section('description', 'Dados e estatísticas do sistema FloodTrack — pontos de alagamento detectados, cidades monitoradas e distribuição por severidade.')

@push('seo')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <div class="mx-auto max-w-6xl">
    {{-- Cabeçalho --}}
    <div class="mb-6 flex items-start justify-between gap-4 print:mb-4">
        <div>
            <h1 class="text-3xl font-semibold text-white print:text-2xl print:text-black">Relatório de Ocorrências</h1>
            <p class="mt-1 text-zinc-400 print:text-sm print:text-zinc-600">
                FloodTrack · Gerado em {{ now()->format('d/m/Y \à\s H:i') }}
                @if($primeira && $ultima)
                    · Período: {{ \Carbon\Carbon::parse($primeira)->format('d/m/Y') }}
                    a {{ \Carbon\Carbon::parse($ultima)->format('d/m/Y') }}
                @endif
            </p>
        </div>

        <button onclick="window.print()"
            class="flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950 print:hidden">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
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
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5 shadow-sm print:rounded-lg print:border-zinc-200 print:bg-white print:p-3">
                <div class="text-xs font-medium uppercase tracking-wide text-zinc-400 print:text-[10px] print:text-zinc-500">
                    {{ $card['label'] }}
                </div>
                <div class="mt-2 text-3xl font-bold text-zinc-100 print:text-2xl print:text-zinc-900">
                    {{ $card['value'] }}
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3 print:grid-cols-3 print:gap-4">

        {{-- Distribuição por nível --}}
        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5 shadow-sm print:rounded-lg print:border-zinc-200 print:bg-white print:p-4">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-400 print:text-zinc-500">Por nível de severidade</h2>

            @php
                $nivelConfig = [
                    'alto'  => ['label' => 'Alto',  'color' => 'bg-red-500',     'text' => 'text-red-400 print:text-red-700'],
                    'medio' => ['label' => 'Médio', 'color' => 'bg-yellow-400',  'text' => 'text-yellow-400 print:text-yellow-700'],
                    'baixo' => ['label' => 'Baixo', 'color' => 'bg-emerald-500', 'text' => 'text-emerald-400 print:text-emerald-700'],
                ];
                $maxNivel = $porNivel->max() ?: 1;
            @endphp

            <div class="space-y-3">
                @foreach($nivelConfig as $key => $cfg)
                    @php $count = $porNivel->get($key, 0); @endphp
                    <div>
                        <div class="mb-1 flex justify-between text-sm">
                            <span class="font-medium {{ $cfg['text'] }}">{{ $cfg['label'] }}</span>
                            <span class="text-zinc-400 print:text-zinc-600">{{ number_format($count) }}</span>
                        </div>
                        <div class="h-3 w-full overflow-hidden rounded-full bg-zinc-800 print:bg-zinc-100">
                            <div class="{{ $cfg['color'] }} h-3 rounded-full transition-all"
                                style="width: {{ $maxNivel > 0 ? round($count / $maxNivel * 100) : 0 }}%">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Top estados --}}
        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5 shadow-sm print:rounded-lg print:border-zinc-200 print:bg-white print:p-4">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-400 print:text-zinc-500">Top estados</h2>

            @php $maxUf = $porUf->first()?->total ?: 1; @endphp

            <div class="space-y-2">
                @forelse($porUf as $row)
                    <div>
                        <div class="mb-1 flex justify-between text-sm">
                            <span class="font-medium text-zinc-200 print:text-zinc-800">{{ $row->uf }}</span>
                            <span class="text-zinc-400 print:text-zinc-500">{{ number_format($row->total) }}</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-800 print:bg-zinc-100">
                            <div class="h-2 rounded-full bg-violet-500"
                                style="width: {{ round($row->total / $maxUf * 100) }}%">
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">Nenhum dado disponível.</p>
                @endforelse
            </div>
        </div>

        {{-- Top cidades --}}
        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5 shadow-sm print:rounded-lg print:border-zinc-200 print:bg-white print:p-4">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-400 print:text-zinc-500">Top cidades</h2>

            @php $maxCidade = $porCidade->first()?->total ?: 1; @endphp

            <div class="space-y-2">
                @forelse($porCidade as $row)
                    <div>
                        <div class="mb-1 flex justify-between text-sm">
                            <span class="truncate pr-2 font-medium text-zinc-200 print:text-zinc-800">
                                {{ $row->cidade }}
                                @if($row->uf)
                                    <span class="font-normal text-zinc-500">{{ $row->uf }}</span>
                                @endif
                            </span>
                            <span class="shrink-0 text-zinc-400 print:text-zinc-500">{{ number_format($row->total) }}</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-800 print:bg-zinc-100">
                            <div class="h-2 rounded-full bg-blue-500"
                                style="width: {{ round($row->total / $maxCidade * 100) }}%">
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">Nenhum dado disponível.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Ocorrências por dia (últimos 30 dias) --}}
    <div class="mt-6 rounded-2xl border border-zinc-800 bg-zinc-900 p-5 shadow-sm print:mt-4 print:rounded-lg print:border-zinc-200 print:bg-white print:p-4">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-400 print:text-zinc-500">
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
        <div class="mt-1 flex gap-1 text-[10px] text-zinc-500">
            @foreach($days as $i => $day)
                <div class="flex-1 text-center {{ $i % 5 !== 0 ? 'invisible' : '' }}">
                    {{ $day['label'] }}
                </div>
            @endforeach
        </div>
    </div>

    {{-- Ranking de risco por cidade --}}
    <div class="mt-6 rounded-2xl border border-zinc-800 bg-zinc-900 p-5 shadow-sm print:mt-4 print:rounded-lg print:border-zinc-200 print:bg-white print:p-4">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-400 print:text-zinc-500">
                Ranking de risco por cidade — últimos 90 dias
            </h2>
            <a href="{{ route('stats.risk-export') }}"
                class="rounded-lg border border-zinc-700 px-2 py-1 text-xs font-medium text-zinc-300 hover:bg-zinc-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 print:hidden">
                Baixar CSV
            </a>
        </div>

        @php
            $riscoConfig = [
                'alto'  => ['label' => 'Alto',  'text' => 'text-red-300 print:text-red-700',     'bg' => 'bg-red-950 border-red-800 print:bg-red-50 print:border-red-200'],
                'medio' => ['label' => 'Médio', 'text' => 'text-yellow-300 print:text-yellow-700',  'bg' => 'bg-yellow-950 border-yellow-800 print:bg-yellow-50 print:border-yellow-200'],
                'baixo' => ['label' => 'Baixo', 'text' => 'text-emerald-300 print:text-emerald-700', 'bg' => 'bg-emerald-950 border-emerald-800 print:bg-emerald-50 print:border-emerald-200'],
            ];
        @endphp

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <caption class="sr-only">Ranking de risco por cidade nos últimos 90 dias</caption>
                <thead>
                    <tr class="border-b border-zinc-800 text-left text-xs uppercase tracking-wide text-zinc-500 print:border-zinc-200">
                        <th scope="col" class="pb-2 pr-2">Cidade</th>
                        <th scope="col" class="pb-2 pr-2">Ocorrências</th>
                        <th scope="col" class="pb-2 pr-2">Pontuação</th>
                        <th scope="col" class="pb-2">Risco</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rankingRisco as $row)
                        @php $cfg = $riscoConfig[$row->nivel_risco] ?? $riscoConfig['baixo']; @endphp
                        <tr class="border-b border-zinc-800 last:border-0 print:border-zinc-100">
                            <td class="py-2 pr-2 font-medium text-zinc-200 print:text-zinc-800">
                                {{ $row->cidade }}
                                @if($row->uf)
                                    <span class="font-normal text-zinc-500">{{ $row->uf }}</span>
                                @endif
                            </td>
                            <td class="py-2 pr-2 text-zinc-400 print:text-zinc-600">{{ number_format($row->ocorrencias) }}</td>
                            <td class="py-2 pr-2 text-zinc-400 print:text-zinc-600">{{ $row->score }}/100</td>
                            <td class="py-2">
                                <span class="rounded-lg border px-2 py-1 text-xs {{ $cfg['bg'] }} {{ $cfg['text'] }}">
                                    {{ $cfg['label'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-4 text-center text-sm text-zinc-500">Sem dados suficientes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Exportar dados --}}
    <div class="mt-6 rounded-2xl border border-zinc-800 bg-zinc-900 p-5 shadow-sm print:hidden">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-400">Exportar dados</h2>
        <p class="mb-4 text-sm text-zinc-400">
            Baixe o histórico completo de ocorrências (inclusive as já resolvidas/expiradas do mapa) em CSV, com filtros opcionais.
        </p>

        <form method="GET" action="{{ route('flood-points.export') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <div class="lg:col-span-2">
                <label for="export-cidade" class="mb-1 block text-xs font-medium text-zinc-400">Cidade</label>
                <input id="export-cidade" type="text" name="cidade" placeholder="Ex.: Campinas"
                    class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 placeholder-zinc-500 outline-none focus-visible:ring-2 focus-visible:ring-violet-400" />
            </div>

            <div>
                <label for="export-uf" class="mb-1 block text-xs font-medium text-zinc-400">UF</label>
                <input id="export-uf" type="text" name="uf" maxlength="2" placeholder="SP"
                    class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 placeholder-zinc-500 outline-none focus-visible:ring-2 focus-visible:ring-violet-400" />
            </div>

            <div>
                <label for="export-nivel" class="mb-1 block text-xs font-medium text-zinc-400">Nível</label>
                <select id="export-nivel" name="nivel"
                    class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus-visible:ring-2 focus-visible:ring-violet-400">
                    <option value="">Todos</option>
                    <option value="baixo">Baixo</option>
                    <option value="medio">Médio</option>
                    <option value="alto">Alto</option>
                </select>
            </div>

            <div>
                <label for="export-data-inicio" class="mb-1 block text-xs font-medium text-zinc-400">De</label>
                <input id="export-data-inicio" type="date" name="data_inicio"
                    class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus-visible:ring-2 focus-visible:ring-violet-400" />
            </div>

            <div>
                <label for="export-data-fim" class="mb-1 block text-xs font-medium text-zinc-400">Até</label>
                <input id="export-data-fim" type="date" name="data_fim"
                    class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus-visible:ring-2 focus-visible:ring-violet-400" />
            </div>

            <div class="flex items-end sm:col-span-2 lg:col-span-6">
                <button type="submit"
                    class="rounded-xl bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-900">
                    Baixar CSV
                </button>
            </div>
        </form>
    </div>

    {{-- Metodologia --}}
    <div class="mt-6 rounded-2xl border border-zinc-800 bg-zinc-900 p-5 shadow-sm print:mt-4 print:rounded-lg print:border-zinc-200 print:bg-white print:p-4">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-zinc-400 print:text-zinc-500">Metodologia</h2>
        <div class="grid gap-4 text-sm text-zinc-400 sm:grid-cols-3 print:grid-cols-3 print:text-zinc-600">
            <div>
                <div class="font-semibold text-zinc-200 print:text-zinc-800">Coleta de dados</div>
                <p class="mt-1">Feeds RSS regionais do G1 cobrindo todos os 27 estados brasileiros, monitorados automaticamente a cada 10 minutos.</p>
            </div>
            <div>
                <div class="font-semibold text-zinc-200 print:text-zinc-800">Extração de localização</div>
                <p class="mt-1">Processamento com IA (Claude Haiku) para identificar cidade, bairro e estado a partir do texto das notícias, com fallback para expressões regulares.</p>
            </div>
            <div>
                <div class="font-semibold text-zinc-200 print:text-zinc-800">Geocodificação</div>
                <p class="mt-1">Coordenadas geográficas obtidas via Nominatim (OpenStreetMap), restrito ao Brasil, com fallback progressivo de endereço completo para apenas cidade.</p>
            </div>
        </div>
    </div>

    {{-- Rodapé de impressão --}}
    <div class="mt-4 hidden text-center text-xs text-zinc-400 print:block">
        FloodTrack · {{ config('app.url') }} · Gerado em {{ now()->format('d/m/Y H:i') }}
    </div>
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
