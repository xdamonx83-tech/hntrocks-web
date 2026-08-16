<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#f6f4ef">
    <title>@yield('title', 'Admin · hnt.rocks')</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('assets/admin/admin.css') }}?v=521">
    @stack('head')
</head>
<body class="hnt-admin-shell">
    <div class="hnt-admin-layout">
        <aside class="hnt-admin-sidebar" aria-label="Admin Navigation">
            <a class="hnt-admin-brand" href="{{ route('admin.index') }}">
                <img src="{{ asset('assets/socialite/images/logo-light.png') }}" alt="HNT.rocks">
                <span>Admin</span>
            </a>

            @include('admin.partials.nav')

            <div class="hnt-admin-sidebar-footer">
                <a href="{{ route('feed.index') }}">Zur Website</a>
                <span>{{ auth()->user()?->username ? '@'.auth()->user()->username : 'Admin' }}</span>
            </div>
        </aside>

        <div class="hnt-admin-main">
            <header class="hnt-admin-topbar">
                <div>
                    <p class="hnt-admin-eyebrow">hnt.rocks Verwaltung</p>
                    <strong>@yield('admin_heading', 'Adminbereich')</strong>
                </div>
                <div class="hnt-admin-topbar-actions">
                    <a class="hnt-admin-topbar-link" href="{{ route('feed.index') }}">Website öffnen</a>
                    <div class="hnt-admin-user-chip">
                        <span>{{ strtoupper(substr(auth()->user()?->name ?? 'A', 0, 1)) }}</span>
                        <div>
                            <strong>{{ auth()->user()?->name ?? 'Admin' }}</strong>
                            <small>Administrator</small>
                        </div>
                    </div>
                </div>
            </header>

            <main class="hnt-admin-content" id="admin-content">
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
