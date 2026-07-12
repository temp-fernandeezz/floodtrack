<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#09090b">
    <meta name="color-scheme" content="dark">

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <title>@yield('title', 'FloodTrack') — Mapa de alagamentos urbanos</title>

    <meta name="description" content="@yield('description', 'Visualize em tempo real os pontos de alagamento urbano detectados por notícias e relatos. Filtre por cidade, bairro e nível de severidade.')">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="{{ url()->current() }}">
    <meta property="og:site_name"   content="FloodTrack">
    <meta property="og:title"       content="@yield('title', 'FloodTrack') — Mapa de alagamentos urbanos">
    <meta property="og:description" content="@yield('description', 'Visualize em tempo real os pontos de alagamento urbano detectados por notícias e relatos.')">
    <meta property="og:locale"      content="pt_BR">

    {{-- Extra meta por página (ex.: noindex em pontos pendentes) --}}
    @stack('seo')

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Scripts extras por página (ex.: reCAPTCHA só em /reportar) --}}
    @stack('scripts')
</head>

<body x-data class="bg-zinc-950 text-zinc-100 antialiased">
    <a href="#conteudo-principal"
        class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[999999999999] focus:rounded-xl focus:bg-violet-600 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
        Pular para o conteúdo
    </a>

    <x-header />

    <main id="conteudo-principal" class="mx-auto max-w-[1600px] px-4 py-6 lg:px-8">
        @yield('content')
    </main>

    <x-footer />
</body>

</html>
