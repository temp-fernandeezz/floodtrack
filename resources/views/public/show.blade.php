@extends('layouts.app')

@section('title', $floodPoint->cidade . ($floodPoint->uf ? ', ' . $floodPoint->uf : ''))
@section('description', $floodPoint->descricao ?? 'Detalhes da ocorrência de alagamento registrada no FloodTrack.')

@php
    $nivelConfig = [
        'alto'  => ['label' => 'Alto',  'text' => 'text-red-300',     'bg' => 'bg-red-950 border-red-800'],
        'medio' => ['label' => 'Médio', 'text' => 'text-yellow-300', 'bg' => 'bg-yellow-950 border-yellow-800'],
        'baixo' => ['label' => 'Baixo', 'text' => 'text-emerald-300', 'bg' => 'bg-emerald-950 border-emerald-800'],
    ];
    $cfg = $nivelConfig[$floodPoint->nivel] ?? $nivelConfig['baixo'];
    $temCoordenadas = $floodPoint->latitude && $floodPoint->longitude;
@endphp

@section('content')
    <section class="mx-auto max-w-2xl space-y-4">
        <a href="{{ route('home') }}"
            class="inline-flex items-center gap-1 text-sm text-zinc-400 hover:text-zinc-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400">
            ← Voltar pro mapa
        </a>

        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-white">
                        {{ $floodPoint->cidade }}
                        @if($floodPoint->uf)
                            <span class="font-normal text-zinc-500">{{ $floodPoint->uf }}</span>
                        @endif
                    </h1>
                    <p class="mt-1 text-sm text-zinc-400">
                        {{ $floodPoint->bairro ?? 'Bairro não informado' }}
                        @if($floodPoint->logradouro)
                            · {{ $floodPoint->logradouro }}
                        @endif
                    </p>
                </div>

                <span class="shrink-0 rounded-lg border px-2 py-1 text-xs {{ $cfg['bg'] }} {{ $cfg['text'] }}">
                    {{ $cfg['label'] }}
                </span>
            </div>

            <p class="mt-4 text-sm text-zinc-300">
                {{ $floodPoint->descricao ?? 'Sem descrição.' }}
            </p>

            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-zinc-500">Status</dt>
                    <dd class="mt-0.5 text-zinc-200">{{ $floodPoint->status === 'ativo' ? 'Ativo' : 'Resolvido' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-zinc-500">Ocorrência</dt>
                    <dd class="mt-0.5 text-zinc-200">
                        {{ $floodPoint->data_ocorrencia?->format('d/m/Y H:i') ?? 'Não informada' }}
                    </dd>
                </div>
                @if($floodPoint->confidence)
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-zinc-500">Confiança</dt>
                        <dd class="mt-0.5 text-zinc-200">{{ $floodPoint->confidence }}%</dd>
                    </div>
                @endif
                @if($floodPoint->merged_sources_count > 1)
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-zinc-500">Fontes confirmando</dt>
                        <dd class="mt-0.5 text-zinc-200">{{ $floodPoint->merged_sources_count }}</dd>
                    </div>
                @endif
            </dl>

            @if($floodPoint->source_url)
                <a href="{{ $floodPoint->source_url }}" target="_blank" rel="noopener noreferrer"
                    class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-violet-400 underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400">
                    Ver notícia original
                </a>
            @endif
        </div>

        @if($temCoordenadas)
            <div id="point-map" role="application"
                aria-label="Mapa com a localização de {{ $floodPoint->cidade }}"
                class="h-[320px] w-full overflow-hidden rounded-2xl shadow-sm ring-1 ring-zinc-800"
                data-lat="{{ $floodPoint->latitude }}" data-lng="{{ $floodPoint->longitude }}"
                data-nivel="{{ $floodPoint->nivel }}"
                data-popup="{{ $floodPoint->cidade }}{{ $floodPoint->bairro ? ' — ' . $floodPoint->bairro : '' }}"></div>
        @else
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-4 text-sm text-zinc-400">
                Esta ocorrência ainda não tem coordenadas — não aparece no mapa até ser revisada.
            </div>
        @endif
    </section>
@endsection

@if($temCoordenadas)
    @push('scripts')
        @vite(['resources/js/point-map.js'])
    @endpush
@endif
