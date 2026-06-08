<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="stylesheet" href="{{ asset('assets/vikinger/css/vendor/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vikinger/css/vendor/simplebar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vikinger/css/vendor/tiny-slider.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vikinger/css/hnt-local-fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vikinger/css/styles.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vikinger/fonts/phosphor/regular/style.css') }}">
</head>
