<header class="sticky top-0 z-[9999999999] border-b border-white/10 bg-zinc-950/70 backdrop-blur">
    <div class="mx-auto flex max-w-[1600px] items-center justify-between px-4 py-4 lg:px-8">
        <a href="{{ route('home') }}"
           class="flex items-center gap-2 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-violet-600/20 ring-1 ring-violet-500/30" aria-hidden="true">
                🌧️
            </span>

            <div class="leading-tight">
                <div class="text-sm font-semibold text-zinc-100">FloodTrack</div>
                <div class="text-xs text-zinc-400">Mapa de alagamentos urbanos</div>
            </div>
        </a>

        <nav class="hidden items-center gap-3 md:flex" aria-label="Principal">
            <a href="{{ route('home') }}"
               aria-current="{{ request()->routeIs('home') ? 'page' : 'false' }}"
               class="rounded-xl px-3 py-2 text-sm text-zinc-300 hover:bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950 aria-[current=page]:bg-white/10 aria-[current=page]:text-white">
                Mapa
            </a>

            <a href="{{ route('stats') }}"
               aria-current="{{ request()->routeIs('stats') ? 'page' : 'false' }}"
               class="rounded-xl px-3 py-2 text-sm text-zinc-300 hover:bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950 aria-[current=page]:bg-white/10 aria-[current=page]:text-white">
                Estatísticas
            </a>

            <a href="{{ route('flood-points.report.create') }}"
               aria-current="{{ request()->routeIs('flood-points.report.*') ? 'page' : 'false' }}"
               class="rounded-xl px-3 py-2 text-sm text-zinc-300 hover:bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950 aria-[current=page]:bg-white/10 aria-[current=page]:text-white">
                Reportar
            </a>

            <button
                type="button"
                @click="$store.ui.toggleLegend()"
                :aria-pressed="$store.ui.legendOpen"
                class="rounded-xl bg-violet-600/20 px-3 py-2 text-sm font-medium text-violet-100 ring-1 ring-violet-500/30 hover:bg-violet-600/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950">
                Legenda
            </button>
        </nav>

        <!-- Mobile -->
        <button
            type="button"
            class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 p-2 text-zinc-200 hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950 md:hidden"
            @click="$store.ui.mobileMenuOpen = ! $store.ui.mobileMenuOpen"
            :aria-expanded="$store.ui.mobileMenuOpen"
            aria-controls="menu-mobile"
            aria-label="Abrir menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
    </div>

    <!-- Mobile dropdown -->
    <nav
        id="menu-mobile"
        aria-label="Principal (mobile)"
        x-show="$store.ui.mobileMenuOpen"
        x-transition
        @click.outside="$store.ui.mobileMenuOpen = false"
        class="border-t border-white/10 bg-zinc-950/90 md:hidden">
        <div class="mx-auto grid max-w-[1600px] gap-2 px-4 py-3 lg:px-8">
            <a href="{{ route('home') }}" aria-current="{{ request()->routeIs('home') ? 'page' : 'false' }}"
               class="rounded-xl px-3 py-2 text-sm text-zinc-200 hover:bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 aria-[current=page]:bg-white/10">
                Mapa
            </a>
            <a href="{{ route('stats') }}" aria-current="{{ request()->routeIs('stats') ? 'page' : 'false' }}"
               class="rounded-xl px-3 py-2 text-sm text-zinc-200 hover:bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 aria-[current=page]:bg-white/10">
                Estatísticas
            </a>
            <a href="{{ route('flood-points.report.create') }}" aria-current="{{ request()->routeIs('flood-points.report.*') ? 'page' : 'false' }}"
               class="rounded-xl px-3 py-2 text-sm text-zinc-200 hover:bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 aria-[current=page]:bg-white/10">
                Reportar
            </a>
            <button
                type="button"
                @click="$store.ui.toggleLegend(); $store.ui.mobileMenuOpen = false"
                class="rounded-xl bg-violet-600/20 px-3 py-2 text-left text-sm font-medium text-violet-100 ring-1 ring-violet-500/30 hover:bg-violet-600/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400">
                Legenda
            </button>
        </div>
    </nav>
</header>
