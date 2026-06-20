<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('themes.hnt_preview.partials.head')
    <link rel="stylesheet" href="{{ asset('assets/vendor/leaflet/leaflet.css') }}?v=1.9.4">
    <link rel="stylesheet" href="{{ asset('assets/hnt/maps/maps.css') }}?v=3">
</head>
<body class="hnt-map-workspace-body">
    @yield('content')

    @include('partials.cookie-consent')

    @if($imageAvailable && ! $dataError)
        <script src="{{ asset('assets/vendor/leaflet/leaflet.js') }}?v=1.9.4" defer></script>
    @endif
    <script src="{{ asset('assets/hnt/maps/maps.js') }}?v=3" defer></script>
</body>
</html>
