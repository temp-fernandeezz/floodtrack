<header class="sticky top-0 z-[9999999999] border-b border-white/10 bg-zinc-950/70 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-violet-600/20 ring-1 ring-violet-500/30">
                🌧️
            </span>

            <div class="leading-tight">
                <div class="text-sm font-semibold text-zinc-100">FloodTrack</div>
                <div class="text-xs text-zinc-400">Mapa de alagamentos urbanos</div>
            </div>
        </a>

        <div class="hidden items-center gap-3 md:flex">
            <a href="{{ route('home') }}"
               class="rounded-xl px-3 py-2 text-sm text-zinc-300 hover:bg-white/5">
                Mapa
            </a>

            <a href="#"
               class="rounded-xl px-3 py-2 text-sm text-zinc-300 hover:bg-white/5">
                Sobre
            </a>

            <button
                type="button"
                @click="$store.ui.toggleLegend()"
                class="rounded-xl bg-violet-600/20 px-3 py-2 text-sm font-medium text-violet-100 ring-1 ring-violet-500/30 hover:bg-violet-600/30">
                Legenda
            </button>
        </div>

        <!-- Mobile -->
        <button
            type="button"
            class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 p-2 text-zinc-200 hover:bg-white/10 md:hidden"
            @click="$store.ui.mobileMenuOpen = ! $store.ui.mobileMenuOpen"
            aria-label="Abrir menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
    </div>

    <!-- Mobile dropdown -->
    <div
        x-show="$store.ui.mobileMenuOpen"
        x-transition
        @click.outside="$store.ui.mobileMenuOpen = false"
        class="border-t border-white/10 bg-zinc-950/90 md:hidden">
        <div class="mx-auto grid max-w-6xl gap-2 px-4 py-3">
            <a href="{{ route('home') }}" class="rounded-xl px-3 py-2 text-sm text-zinc-200 hover:bg-white/5">
                Mapa
            </a>
            <a href="#" class="rounded-xl px-3 py-2 text-sm text-zinc-200 hover:bg-white/5">
                Sobre
            </a>
            <button
                type="button"
                @click="$store.ui.toggleLegend(); $store.ui.mobileMenuOpen = false"
                class="rounded-xl bg-violet-600/20 px-3 py-2 text-left text-sm font-medium text-violet-100 ring-1 ring-violet-500/30 hover:bg-violet-600/30">
                Legenda
            </button>
        </div>
    </div>
</header>
