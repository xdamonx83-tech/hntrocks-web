<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="@yield('robots', 'noindex,follow')">
<title>@yield('title', __('guides.meta_title'))</title>
<meta name="description" content="@yield('meta_description', __('guides.meta_description'))">
<link rel="canonical" href="@yield('canonical', url()->current())">
<meta property="og:title" content="@yield('og_title', __('guides.meta_title'))">
<meta property="og:description" content="@yield('og_description', __('guides.meta_description'))">
<meta property="og:url" content="@yield('canonical', url()->current())">
@hasSection('og_image')<meta property="og:image" content="@yield('og_image')">@endif
<link href="https://fonts.googleapis.com" rel="preconnect">
<link crossorigin href="https://fonts.gstatic.com" rel="preconnect">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&amp;display=swap" rel="stylesheet">
<link href="{{ asset('assets/vikinger/fonts/phosphor/regular/style.css') }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/common.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/feed.css')) ?: time() }}" rel="stylesheet">
@unless(trim($__env->yieldContent('skip_guides_base_styles')) === '1')
<link href="{{ asset('assets/themes/hnt_preview/guides/guides.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/guides/guides.css')) ?: time() }}" rel="stylesheet">
@endunless
@stack('head')
<link href="{{ asset('assets/themes/hnt_preview/guides/guides-header-dropdown-fix.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/guides/guides-header-dropdown-fix.css')) ?: time() }}" rel="stylesheet">
</head>
<body data-page="guides" class="@yield('body_class')">
@include('themes.hnt_preview.partials.icons')
<main class="app-shell guides-page-shell">
@include('themes.hnt_preview.partials.header')
<section class="guides-stage">
<div class="guides-scroll" id="guidesScroll">
@if(session('status'))
<div class="guides-alert success" role="status">{{ session('status') }}</div>
@endif
@if($errors->any())
<div class="guides-alert error" role="alert">
<strong>{{ __('ui.error') }}</strong>
<ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif
@yield('content')
</div>
</section>
<div class="guide-toast" data-guide-toast role="status" aria-live="polite"></div>
</main>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/guides/guides.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/guides/guides.js')) ?: time() }}" defer></script>
@stack('scripts')
</body>
</html>
