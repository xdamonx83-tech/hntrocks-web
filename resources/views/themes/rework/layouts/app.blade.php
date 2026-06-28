@php
    $currentLocale = app()->getLocale() === 'en' ? 'en' : 'de';
    $reworkStyleVersion = $reworkStyleVersion ?? (@filemtime(public_path('assets/themes/rework/styles.css')) ?: time());
    $reworkScriptVersion = $reworkScriptVersion ?? (@filemtime(public_path('assets/themes/rework/script.js')) ?: time());
    $reworkBodyClass = trim($__env->yieldContent('body_class'));
@endphp
<!DOCTYPE html>
<html lang="{{ $currentLocale }}">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1" name="viewport"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>@yield('title', 'HNT.rocks')</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Bai+Jamjuree:wght@400;500;600;700&amp;family=Bakbak+One&amp;family=Montserrat:wght@300;400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/>
<link href="https://unpkg.com/@phosphor-icons/web@2.1.2/src/regular/style.css" rel="stylesheet"/>
<link href="https://unpkg.com/@phosphor-icons/web@2.1.2/src/bold/style.css" rel="stylesheet"/>
<link href="https://unpkg.com/@phosphor-icons/web@2.1.2/src/fill/style.css" rel="stylesheet"/>
<link href="{{ \App\Support\HntTheme::asset('styles.css', 'rework') }}?v={{ $reworkStyleVersion }}" rel="stylesheet"/>
@stack('rework-head')
</head>
<body @if($reworkBodyClass !== '') class="{{ $reworkBodyClass }}" @endif>
<div class="app">
@include('themes.rework.partials.sidebar')
<main class="main">
@include('themes.rework.partials.topbar')
<section class="content-grid">
<div class="left-col">
@yield('content')
</div>
@include('themes.rework.partials.right-widgets')
</section>
</main>
</div>
@stack('rework-modals')
<script src="{{ \App\Support\HntTheme::asset('script.js', 'rework') }}?v={{ $reworkScriptVersion }}" defer></script>
@stack('rework-scripts')
</body>
</html>
