@extends('layouts.auth')

@section('title', __('ui.login_title'))
@section('robots', 'noindex,follow')
@section('auth_tab', 'login')
@section('body_class', 'hnt-login-body')
@section('body_page', 'auth-login')

@push('head')
    @php
        $hntThemeColorsVersion = @filemtime(public_path('assets/themes/hnt_preview/theme-colors.css')) ?: time();
        $hntLoginCssVersion = @filemtime(public_path('assets/themes/hnt_preview/auth-login.css')) ?: time();
    @endphp
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/theme-colors.css') }}?v={{ $hntThemeColorsVersion }}">
    <link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/auth-login.css') }}?v={{ $hntLoginCssVersion }}">
@endpush

@section('content')
    @include('auth.partials.login-redesign')
@endsection

@push('scripts')
    @php
        $hntLoginJsVersion = @filemtime(public_path('assets/themes/hnt_preview/auth-login.js')) ?: time();
    @endphp
    <script src="{{ asset('assets/themes/hnt_preview/auth-login.js') }}?v={{ $hntLoginJsVersion }}" defer></script>
@endpush
