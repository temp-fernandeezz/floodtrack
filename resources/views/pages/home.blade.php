@extends('layouts.app')

@section('title', 'Mapa de alagamentos')
@section('description', 'Visualize os pontos de alagamento urbano detectados por notícias. Filtre por cidade, bairro e nível de severidade e acompanhe ocorrências em tempo real.')

@section('content')
    <section class="mx-auto max-w-full">
        <div x-data="window.floodHome()" class="space-y-5">
            <div class="space-y-1">
                <h1 class="text-3xl font-semibold text-white">
                    Mapa de pontos de alagamento
                </h1>
                <p class="text-xl text-zinc-400">
                    Visualize ocorrências recentes por cidade, bairro e nível de severidade. <br>
                    <span class="italic">Nosso mapa é atualizado diariamente.</span>
                </p>
            </div>

             <!-- Chuva na região (aparece só quando a OpenWeatherMap está configurada) -->
            <div id="rain-widget" role="status" class="hidden items-center gap-2 rounded-2xl bg-zinc-900 px-4 py-2 text-sm text-zinc-300 shadow-sm ring-1 ring-zinc-800">
                🌧️ Chuva recente: <b id="rain-widget-mm" class="text-zinc-100"></b>
                <span class="text-zinc-500" id="rain-widget-local"></span>
            </div>

            <!-- Filtros -->
            <form @submit.prevent="applyFilters()" class="grid gap-3 rounded-2xl bg-zinc-900 p-4 shadow-sm ring-1 ring-zinc-800 md:grid-cols-12">
                <div class="md:col-span-4">
                    <label for="filtro-cidade" class="mb-1 block pl-0.75 text-xs font-medium text-zinc-400">Cidade</label>
                    <input id="filtro-cidade" x-model="filters.cidade" type="text" placeholder="Ex.: São José dos Campos"
                        class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 placeholder-zinc-500 outline-none focus-visible:ring-2 focus-visible:ring-violet-400" />
                </div>

                <div class="md:col-span-2">
                    <label for="filtro-uf" class="mb-1 block text-xs pl-0.75 font-medium text-zinc-400">Estado</label>
                    <select id="filtro-uf" x-model="filters.uf"
                        class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus-visible:ring-2 focus-visible:ring-violet-400">
                        <option value="">Todos</option>
                        @foreach($ufsDisponiveis as $ufOption)
                            <option value="{{ $ufOption }}">{{ $ufOption }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="filtro-nivel" class="mb-1 block text-xs pl-0.75 font-medium text-zinc-400">Nível</label>
                    <select id="filtro-nivel" x-model="filters.nivel"
                        class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus-visible:ring-2 focus-visible:ring-violet-400">
                        <option value="">Todos</option>
                        <option value="baixo">Baixo</option>
                        <option value="medio">Médio</option>
                        <option value="alto">Alto</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="filtro-status" class="mb-1 block text-xs pl-0.75 font-medium text-zinc-400">Status</label>
                    <select id="filtro-status" x-model="filters.status"
                        class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus-visible:ring-2 focus-visible:ring-violet-400">
                        <option value="">Ativos (padrão)</option>
                        <option value="todos">Todos (incl. resolvidos)</option>
                        <option value="resolvido">Só resolvidos</option>
                    </select>
                </div>

                <div class="flex items-end gap-2 md:col-span-2">
                    <button type="submit"
                        class="w-full hover:cursor-pointer rounded-xl bg-violet-600 px-3 py-2 text-sm font-semibold text-white hover:bg-violet-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-900">
                        Aplicar
                    </button>

                    <button type="button" @click="resetFilters()"
                        class="rounded-xl hover:cursor-pointer border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-300 hover:bg-zinc-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-900"
                        title="Limpar">
                        Limpar
                    </button>
                </div>

                <div class="md:col-span-12">
                    <div class="flex items-center justify-between text-xs text-zinc-500">
                        <span role="status" x-text="statusText"></span>

                        <span class="inline-flex items-center gap-2">
                            <span class="inline-flex items-center gap-1">
                                <span class="h-2 w-2 rounded-full bg-emerald-500" aria-hidden="true"></span> Baixo
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span class="h-2 w-2 rounded-full bg-yellow-400" aria-hidden="true"></span> Médio
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span class="h-2 w-2 rounded-full bg-red-500" aria-hidden="true"></span> Alto
                            </span>
                        </span>
                    </div>
                </div>
            </form>

            <!-- Sem resultados para o filtro aplicado -->
            <div x-show="noResults" x-cloak x-transition role="status"
                class="flex items-start gap-3 rounded-2xl border border-amber-800 bg-amber-950 p-4 text-sm text-amber-300">
                <span class="text-lg leading-none" aria-hidden="true">⚠️</span>
                <div>
                    <p class="font-semibold">Nenhuma ocorrência encontrada</p>
                    <p class="mt-0.5 text-amber-300/80">Tente outra cidade ou estado, ou limpe os filtros para ver todas as ocorrências ativas.</p>
                </div>
            </div>

            <!-- Resultados encontrados para o filtro aplicado -->
            <div x-show="foundResults" x-cloak x-transition role="status"
                class="flex items-start gap-3 rounded-2xl border border-emerald-800 bg-emerald-950 p-4 text-sm text-emerald-300">
                <span class="text-lg leading-none" aria-hidden="true">✅</span>
                <div>
                    <p class="font-semibold" x-text="`${foundCount} ocorrência${foundCount === 1 ? '' : 's'} encontrada${foundCount === 1 ? '' : 's'}`"></p>
                    <p class="mt-0.5 text-emerald-300/80">Confira os marcadores destacados no mapa abaixo.</p>
                </div>
            </div>

            <!-- Mapa -->
            <div id="map" role="application" aria-label="Mapa interativo com os pontos de alagamento ativos"
                class="h-[70vh] min-h-[480px] w-full overflow-hidden rounded-2xl shadow-sm ring-1 ring-zinc-800 lg:h-[78vh] xl:h-[82vh]"
                data-api-url="{{ route('flood-points.api') }}" data-default-lat="-23.1896" data-default-lng="-45.8841"
                data-default-zoom="10"></div>
        </div>
    </section>

    <section x-data="pendingSwiper('{{ route('flood-points.apiPending') }}')" x-init="init()" class="mt-8 space-y-3">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-white">Ocorrências sem localização</h2>
                <p class="text-sm text-zinc-400">
                    Registros que ainda não têm coordenadas (Latitude e Longitude) não aparecem no mapa.
                </p>
            </div>

            <div class="flex items-center justify-between gap-2 sm:justify-end">
                <span class="text-xs text-zinc-500" x-text="metaText"></span>

                <div class="flex items-center gap-2">
                    <button type="button"
                        class="rounded-xl border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-200 hover:bg-zinc-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 disabled:opacity-50"
                        @click="prev()" :disabled="loading" aria-label="Ver ocorrências anteriores">
                        ←
                    </button>
                    <button type="button"
                        class="rounded-xl border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-200 hover:bg-zinc-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 disabled:opacity-50"
                        @click="next()" :disabled="loading" aria-label="Ver próximas ocorrências">
                        →
                    </button>
                </div>
            </div>
        </div>

        <!-- loading -->
        <template x-if="loading">
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-4 text-sm text-zinc-400" role="status">
                Carregando ocorrências…
            </div>
        </template>

        <!-- vazio -->
        <template x-if="!loading && items.length === 0">
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-4 text-sm text-zinc-400">
                Nenhuma ocorrência pendente no momento ✅
            </div>
        </template>

        <!-- swiper -->
        <div class="relative" x-show="!loading && items.length > 0">
            <div x-ref="track" class="flex gap-4 overflow-x-auto scroll-smooth snap-x snap-mandatory pb-2"
                style="-webkit-overflow-scrolling: touch;">
                <template x-for="item in items" :key="item.id">
                    <article
                        class="min-w-[85vw] max-w-[85vw] snap-start rounded-2xl border border-zinc-800 bg-zinc-900 p-4 shadow-sm sm:min-w-[280px] sm:max-w-[280px]">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-zinc-100" x-text="formatPlace(item)">
                                </div>
                                <div class="text-xs text-zinc-500" x-text="item.bairro || 'Bairro não informado'">
                                </div>
                            </div>

                            <span class="rounded-lg px-2 py-1 text-xs border" :class="badgeClass(item.nivel)"
                                x-text="(item.nivel || '').toUpperCase()">
                            </span>
                        </div>

                        <div class="mt-2 line-clamp-3 text-sm text-zinc-300" x-text="item.descricao || 'Sem descrição'">
                        </div>

                        <div class="mt-3 flex items-center justify-between text-xs text-zinc-500">
                            <span x-text="formatDate(item.data_ocorrencia)"></span>

                            <template x-if="item.confidence !== undefined && item.confidence !== null">
                                <span class="rounded-lg border border-zinc-800 bg-zinc-950 px-2 py-1">
                                    Confiança: <b x-text="item.confidence"></b>
                                </span>
                            </template>
                        </div>

                        <div class="mt-3 flex items-center gap-2">
                            <template x-if="item.source_url">
                                <a class="text-sm font-semibold text-violet-400 underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400"
                                    :href="item.source_url" target="_blank" rel="noopener noreferrer">
                                    Ver notícia
                                </a>
                            </template>

                            <a class="ml-auto text-sm font-semibold text-zinc-100 underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400"
                                :href="`/pontos/${item.id}`">
                                Detalhes
                            </a>
                        </div>
                    </article>
                </template>
            </div>
        </div>
    </section>
@endsection
