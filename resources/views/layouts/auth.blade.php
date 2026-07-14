<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#070605">
    @include('partials.seo-meta')

    <link rel="icon" type="image/png" href="{{ asset('assets/vikinger/img/favicon-96x96.png') }}" sizes="96x96">
    <link rel="stylesheet" href="{{ asset('assets/socialite/css/tailwind.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/socialite/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/socialite/css/hnt-auth-palette.css') }}?v=503">
    <link rel="stylesheet" href="{{ asset('assets/vikinger/fonts/phosphor/regular/style.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@200;300;400;500;600;700;800&display=swap" rel="stylesheet">
    @stack('head')
</head>
<body class="@yield('body_class', 'hh-auth-socialite-form-page hh-auth-hnt-palette')" data-page="@yield('body_page', 'auth')" data-hnt-theme="locked">
    @yield('content')

    @include('partials.cookie-consent')

    <script src="{{ asset('assets/socialite/js/uikit.min.js') }}"></script>
    <script src="{{ asset('assets/socialite/js/script.js') }}"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script>
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'hnt');
        document.documentElement.dataset.hntTheme = 'locked';
    </script>
    @stack('scripts')
</body>
</html>
