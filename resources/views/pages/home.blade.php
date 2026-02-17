@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-6xl">
        <div x-data="window.floodHome()" class="space-y-5">
            <div class="space-y-1">
                <h1 class="text-3xl font-semibold text-black">
                    Mapa de pontos de alagamento
                </h1>
                <p class="text-xl text-zinc-700">
                    Visualize ocorrências recentes por cidade, bairro e nível de severidade. <br>
                    <span class="italic">Nosso mapa é atualizado diariamente.</span>
                </p>
            </div>

            <!-- Filtros -->
            <div class="grid gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-zinc-200 md:grid-cols-12">
                <div class="md:col-span-5">
                    <label class="mb-1 block text-xs font-medium text-zinc-700">Cidade</label>
                    <input x-model="filters.cidade" type="text" placeholder="Ex.: São José dos Campos"
                        class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-violet-300" />
                </div>

                <div class="md:col-span-3">
                    <label class="mb-1 block text-xs font-medium text-zinc-700">Nível</label>
                    <select x-model="filters.nivel"
                        class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-violet-300">
                        <option value="">Todos</option>
                        <option value="baixo">Baixo</option>
                        <option value="medio">Médio</option>
                        <option value="alto">Alto</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-zinc-700">Status</label>
                    <select x-model="filters.status"
                        class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-violet-300">
                        <option value="">Todos</option>
                        <option value="ativo">Ativo</option>
                        <option value="resolvido">Resolvido</option>
                    </select>
                </div>

                <div class="flex items-end gap-2 md:col-span-2">
                    <button type="button" @click="applyFilters()"
                        class="w-full rounded-xl bg-violet-600 px-3 py-2 text-sm font-semibold text-white hover:bg-violet-700">
                        Aplicar
                    </button>

                    <button type="button" @click="resetFilters()"
                        class="rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50"
                        title="Limpar">
                        Limpar
                    </button>
                </div>

                <div class="md:col-span-12">
                    <div class="flex items-center justify-between text-xs text-zinc-500">
                        <span x-text="statusText"></span>

                        <span class="inline-flex items-center gap-2">
                            <span class="inline-flex items-center gap-1">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Baixo
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span class="h-2 w-2 rounded-full bg-yellow-500"></span> Médio
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span class="h-2 w-2 rounded-full bg-red-500"></span> Alto
                            </span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Mapa -->
            <div id="map" class="h-[72vh] w-full overflow-hidden rounded-2xl shadow-sm ring-1 ring-zinc-200"
                data-api-url="{{ route('flood-points.api') }}" data-default-lat="-23.1896" data-default-lng="-45.8841"
                data-default-zoom="10"></div>
        </div>
    </section>

    <section x-data="pendingSwiper('{{ route('flood-points.apiPending') }}')" x-init="init()" class="mt-8 space-y-3">
        <div class="flex items-end justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-black">Ocorrências sem localização</h2>
                <p class="text-sm text-zinc-700">
                    Registros que ainda não têm coordenadas (Latitude e Longitude) não aparecem no mapa.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs text-zinc-500" x-text="metaText"></span>

                <button type="button"
                    class="rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm hover:bg-zinc-50 disabled:opacity-50"
                    @click="prev()" :disabled="loading">
                    ←
                </button>
                <button type="button"
                    class="rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm hover:bg-zinc-50 disabled:opacity-50"
                    @click="next()" :disabled="loading">
                    →
                </button>
            </div>
        </div>

        <!-- loading -->
        <template x-if="loading">
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 text-sm text-zinc-600">
                Carregando ocorrências…
            </div>
        </template>

        <!-- vazio -->
        <template x-if="!loading && items.length === 0">
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 text-sm text-zinc-600">
                Nenhuma ocorrência pendente no momento ✅
            </div>
        </template>

        <!-- swiper -->
        <div class="relative" x-show="!loading && items.length > 0">
            <div x-ref="track" class="flex gap-4 overflow-x-auto scroll-smooth snap-x snap-mandatory pb-2"
                style="-webkit-overflow-scrolling: touch;">
                <template x-for="item in items" :key="item.id">
                    <article
                        class="min-w-[280px] max-w-[280px] snap-start rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-zinc-900" x-text="formatPlace(item)">
                                </div>
                                <div class="text-xs text-zinc-600" x-text="item.bairro || 'Bairro não informado'">
                                </div>
                            </div>

                            <span class="rounded-lg px-2 py-1 text-xs border" :class="badgeClass(item.nivel)"
                                x-text="(item.nivel || '').toUpperCase()">
                            </span>
                        </div>

                        <div class="mt-2 line-clamp-3 text-sm text-zinc-700" x-text="item.descricao || 'Sem descrição'">
                        </div>

                        <div class="mt-3 flex items-center justify-between text-xs text-zinc-500">
                            <span x-text="formatDate(item.data_ocorrencia)"></span>

                            <template x-if="item.confidence !== undefined && item.confidence !== null">
                                <span class="rounded-lg border border-zinc-200 bg-zinc-50 px-2 py-1">
                                    Confiança: <b x-text="item.confidence"></b>
                                </span>
                            </template>
                        </div>

                        <div class="mt-3 flex items-center gap-2">
                            <template x-if="item.source_url">
                                <a class="text-sm font-semibold text-violet-700 underline" :href="item.source_url"
                                    target="_blank" rel="noreferrer">
                                    Ver notícia
                                </a>
                            </template>

                            <a class="ml-auto text-sm font-semibold text-zinc-900 underline" :href="`/pontos/${item.id}`">
                                Detalhes
                            </a>
                        </div>
                    </article>
                </template>
            </div>
        </div>
    </section>
@endsection
