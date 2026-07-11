@extends('layouts.app')

@section('title', 'Reportar alagamento')
@section('description', 'Viu um alagamento agora? Reporte o local — depois de revisado, o ponto aparece no mapa público do FloodTrack.')

@push('seo')
    <meta name="robots" content="noindex">
@endpush

@if(config('services.recaptcha.site_key'))
    @push('scripts')
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endpush
@endif

@section('content')
    <section class="mx-auto max-w-2xl">
        <div class="space-y-1">
            <h1 class="text-3xl font-semibold text-white">Reportar alagamento</h1>
            <p class="text-zinc-400">
                Viu um alagamento agora? Conta pra gente. Seu reporte passa por uma revisão rápida antes de
                aparecer no mapa público.
            </p>
        </div>

        @if(session('reported'))
            <div class="mt-4 rounded-2xl border border-emerald-800 bg-emerald-950 p-4 text-sm text-emerald-300" role="status">
                Reporte recebido! Obrigado — ele será revisado antes de aparecer no mapa.
            </div>
        @endif

        @if($errors->any())
            <div class="mt-4 rounded-2xl border border-red-800 bg-red-950 p-4 text-sm text-red-300" role="alert">
                <p class="font-semibold">Corrija os campos abaixo:</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('flood-points.report.store') }}" x-data="floodReport()"
            class="mt-5 grid gap-3 rounded-2xl bg-zinc-900 p-4 shadow-sm ring-1 ring-zinc-800 md:grid-cols-2">
            @csrf

            {{-- Honeypot — invisível pra humanos --}}
            <div class="hidden" aria-hidden="true">
                <label for="website">Não preencha este campo</label>
                <input id="website" type="text" name="website" tabindex="-1" autocomplete="off" value="{{ old('website') }}" />
            </div>

            <div class="md:col-span-2">
                <label for="cidade" class="mb-1 block text-xs font-medium text-zinc-400">Cidade *</label>
                <input id="cidade" name="cidade" type="text" required value="{{ old('cidade') }}" placeholder="Ex.: São José dos Campos"
                    class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 placeholder-zinc-500 outline-none focus-visible:ring-2 focus-visible:ring-violet-400" />
            </div>

            <div>
                <label for="uf" class="mb-1 block text-xs font-medium text-zinc-400">UF</label>
                <input id="uf" name="uf" type="text" maxlength="2" value="{{ old('uf') }}" placeholder="SP"
                    class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 placeholder-zinc-500 outline-none focus-visible:ring-2 focus-visible:ring-violet-400" />
            </div>

            <div>
                <label for="bairro" class="mb-1 block text-xs font-medium text-zinc-400">Bairro</label>
                <input id="bairro" name="bairro" type="text" value="{{ old('bairro') }}" placeholder="Ex.: Centro"
                    class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 placeholder-zinc-500 outline-none focus-visible:ring-2 focus-visible:ring-violet-400" />
            </div>

            <div class="md:col-span-2">
                <label for="logradouro" class="mb-1 block text-xs font-medium text-zinc-400">Rua/avenida (opcional)</label>
                <input id="logradouro" name="logradouro" type="text" value="{{ old('logradouro') }}" placeholder="Ex.: Av. Brasil"
                    class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 placeholder-zinc-500 outline-none focus-visible:ring-2 focus-visible:ring-violet-400" />
            </div>

            <div class="md:col-span-2">
                <button type="button" @click="useMyLocation()"
                    class="rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-300 hover:bg-zinc-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400"
                    :disabled="locating" :aria-busy="locating">
                    <span x-show="!locating">📍 Usar minha localização</span>
                    <span x-show="locating">Obtendo localização…</span>
                </button>
                <span class="ml-2 text-xs text-emerald-400" x-show="located" x-cloak role="status">Localização adicionada ✓</span>
                <span class="ml-2 text-xs text-red-400" x-show="locationError" x-text="locationError" x-cloak role="alert"></span>

                <input type="hidden" name="latitude" x-model="latitude" />
                <input type="hidden" name="longitude" x-model="longitude" />
            </div>

            <div>
                <label for="nivel" class="mb-1 block text-xs font-medium text-zinc-400">Nível *</label>
                <select id="nivel" name="nivel" required
                    class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus-visible:ring-2 focus-visible:ring-violet-400">
                    <option value="">Selecione</option>
                    <option value="baixo" {{ old('nivel') === 'baixo' ? 'selected' : '' }}>Baixo — acúmulo de água</option>
                    <option value="medio" {{ old('nivel') === 'medio' ? 'selected' : '' }}>Médio — rua alagada</option>
                    <option value="alto" {{ old('nivel') === 'alto' ? 'selected' : '' }}>Alto — intransitável/risco</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label for="descricao" class="mb-1 block text-xs font-medium text-zinc-400">O que está acontecendo? *</label>
                <textarea id="descricao" name="descricao" required rows="3" maxlength="500" placeholder="Ex.: Avenida totalmente alagada, carros parados desde as 14h."
                    class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 placeholder-zinc-500 outline-none focus-visible:ring-2 focus-visible:ring-violet-400">{{ old('descricao') }}</textarea>
            </div>

            @if(config('services.recaptcha.site_key'))
                <div class="md:col-span-2">
                    <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                </div>
            @endif

            <div class="md:col-span-2">
                <button type="submit"
                    class="w-full rounded-xl bg-violet-600 px-3 py-2 text-sm font-semibold text-white hover:bg-violet-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-900 md:w-auto">
                    Enviar reporte
                </button>
            </div>
        </form>
    </section>
@endsection
