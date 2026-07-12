<header class="sticky top-0 z-[9999999999] border-b border-white/10 bg-zinc-950/70 backdrop-blur">
    <div class="mx-auto flex max-w-[1600px] items-center justify-between px-4 py-4 lg:px-8">
        <a href="{{ route('home') }}"
            class="flex items-center gap-2 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950">
            <img src="{{ asset('favicon.svg') }}" alt="" width="36" height="36"
                class="h-9 w-9 rounded-xl ring-1 ring-violet-500/30"
                aria-hidden="true">

            <div class="leading-tight">
                <div class="text-sm font-semibold text-zinc-100">FloodTrack</div>
                <div class="text-xs text-zinc-400">Mapa de alagamentos urbanos</div>
            </div>
        </a>

        <nav class="hidden items-center gap-3 md:flex" aria-label="Principal">
            <a href="{{ route('home') }}" aria-current="{{ request()->routeIs('home') ? 'page' : 'false' }}"
                class="rounded-xl px-3 py-2 text-sm text-zinc-300 hover:bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950 aria-[current=page]:bg-white/10 aria-[current=page]:text-white">
                Mapa
            </a>

            <a href="{{ route('stats') }}" aria-current="{{ request()->routeIs('stats') ? 'page' : 'false' }}"
                class="rounded-xl px-3 py-2 text-sm text-zinc-300 hover:bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950 aria-[current=page]:bg-white/10 aria-[current=page]:text-white">
                Estatísticas
            </a>

            <a href="{{ route('flood-points.report.create') }}"
                aria-current="{{ request()->routeIs('flood-points.report.*') ? 'page' : 'false' }}"
                class="rounded-xl px-3 py-2 text-sm text-zinc-300 hover:bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950 aria-[current=page]:bg-white/10 aria-[current=page]:text-white">
                Reportar
            </a>

            <button type="button" @click="$store.ui.toggleAbout()" :aria-pressed="$store.ui.aboutOpen"
                class="rounded-xl bg-violet-600/20 px-3 py-2 text-sm font-medium text-violet-100 ring-1 ring-violet-500/30 hover:bg-violet-600/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950">
                Sobre
            </button>
        </nav>

        <!-- Mobile -->
        <button type="button"
            class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 p-2 text-zinc-200 hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950 md:hidden"
            @click="$store.ui.mobileMenuOpen = ! $store.ui.mobileMenuOpen" :aria-expanded="$store.ui.mobileMenuOpen"
            aria-controls="menu-mobile" aria-label="Abrir menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <!-- Mobile dropdown -->
    <nav id="menu-mobile" aria-label="Principal (mobile)" x-show="$store.ui.mobileMenuOpen" x-transition
        @click.outside="$store.ui.mobileMenuOpen = false" class="border-t border-white/10 bg-zinc-950/90 md:hidden">
        <div class="mx-auto grid max-w-[1600px] gap-2 px-4 py-3 lg:px-8">
            <a href="{{ route('home') }}" aria-current="{{ request()->routeIs('home') ? 'page' : 'false' }}"
                class="rounded-xl px-3 py-2 text-sm text-zinc-200 hover:bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 aria-[current=page]:bg-white/10">
                Mapa
            </a>
            <a href="{{ route('stats') }}" aria-current="{{ request()->routeIs('stats') ? 'page' : 'false' }}"
                class="rounded-xl px-3 py-2 text-sm text-zinc-200 hover:bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 aria-[current=page]:bg-white/10">
                Estatísticas
            </a>
            <a href="{{ route('flood-points.report.create') }}"
                aria-current="{{ request()->routeIs('flood-points.report.*') ? 'page' : 'false' }}"
                class="rounded-xl px-3 py-2 text-sm text-zinc-200 hover:bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 aria-[current=page]:bg-white/10">
                Reportar
            </a>
            <button type="button" @click="$store.ui.toggleAbout(); $store.ui.mobileMenuOpen = false"
                class="rounded-xl bg-violet-600/20 px-3 py-2 text-left text-sm font-medium text-violet-100 ring-1 hover:cursor-pointer ring-violet-500/30 hover:bg-violet-600/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400">
                Sobre
            </button>
        </div>
    </nav>
</header>

<div x-show="$store.ui.aboutOpen" @keydown.escape.window="$store.ui.aboutOpen = false"
    class="fixed inset-0 z-[999999999999] flex items-center justify-center p-4" style="display: none;">

    <div x-show="$store.ui.aboutOpen" x-transition.opacity @click="$store.ui.aboutOpen = false"
        class="absolute inset-0 bg-black/70 backdrop-blur-sm" aria-hidden="true"></div>

    <div x-show="$store.ui.aboutOpen" x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95" role="dialog" aria-modal="true" aria-labelledby="about-modal-title"
        class="relative w-full max-w-lg rounded-2xl border border-white/10 bg-zinc-900 p-5 shadow-xl">

        <div class="flex items-start justify-between gap-3">
            <h2 id="about-modal-title" class="text-lg font-semibold text-zinc-100">Sobre o FloodTrack</h2>

            <button type="button" @click="$store.ui.aboutOpen = false" aria-label="Fechar"
                class="rounded-lg p-1 text-zinc-400 hover:bg-white/5 hover:text-zinc-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <p class="mt-2 text-base text-zinc-400">
            O FloodTrack mapeia ocorrências de alagamento urbano em tempo (quase) real, cruzando
            notícias de veículos monitorados por RSS com reportes enviados pela própria comunidade.
        </p>

        <div class="mt-4 space-y-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Nível de severidade no mapa</p>

            <div class="flex items-center gap-2 text-base text-zinc-300">
                <span class="h-3 w-3 shrink-0 rounded-full bg-emerald-500" aria-hidden="true"></span>
                Baixo — atenção, sem grande impacto no trânsito
            </div>
            <div class="flex items-center gap-2 text-base text-zinc-300">
                <span class="h-3 w-3 shrink-0 rounded-full bg-yellow-500" aria-hidden="true"></span>
                Médio — vias parcialmente afetadas
            </div>
            <div class="flex items-center gap-2 text-base text-zinc-300">
                <span class="h-3 w-3 shrink-0 rounded-full bg-red-500" aria-hidden="true"></span>
                Alto — risco elevado, evite a região
            </div>
        </div>

        <p class="mt-4 text-base text-zinc-500">
            Ocorrências pendentes passam por revisão antes de aparecer no mapa público, e são marcadas
            como resolvidas automaticamente depois de um tempo sem confirmação. As informações têm caráter
            informativo — em emergência, procure a Defesa Civil ou o 199/193.
        </p>
    </div>
</div>
