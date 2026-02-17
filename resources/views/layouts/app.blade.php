<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    {{-- <link rel="icon" type="icon" sizes="16x16" href="{{ Vite::asset('resources/images/new-ico.ico') }}">
    <meta name="description" content="A UrbanEye é um site voltado para a segurança de todos os cidadões"> --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>


<body>
    <x-header />

    <main class="mx-auto max-w-6xl px-4 py-6">
        @yield('content')
    </main>

    <x-footer />
</body>

</html>
