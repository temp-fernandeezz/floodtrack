<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

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

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <x-header />

    <main class="mx-auto max-w-[1600px] px-4 py-6 lg:px-8">
        @yield('content')
    </main>

    <x-footer />
</body>

</html>
