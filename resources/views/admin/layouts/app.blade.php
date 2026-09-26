<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#181b1e">
    <title>@yield('title', 'HNT-ACP · hnt.rocks')</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin/admin.css') }}?v=521">
    <link rel="stylesheet" href="{{ asset('assets/admin/hnt-acp.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('assets/admin/hnt-acp-demo6.css') }}?v=1">
    @stack('head')
</head>
<body class="hnt-admin-shell @yield('body_class')">
    @php
        $acpAdmin = auth()->user();
        $acpAvatar = $acpAdmin && method_exists($acpAdmin, 'avatarUrl')
            ? $acpAdmin->avatarUrl()
            : asset('assets/vikinger/img/default-avatar.svg');
        $acpName = $acpAdmin?->name ?: ($acpAdmin?->username ?: 'Admin');
    @endphp

    <header class="hnt-demo6-mobile-header">
        <a class="hnt-demo6-mobile-brand" href="{{ route('admin.index') }}" aria-label="HNT-ACP Dashboard">
            <img src="{{ asset('assets/socialite/images/logo-light.png') }}" alt="">
            <span>HNT-ACP</span>
        </a>
        <button class="hnt-demo6-mobile-toggle" type="button" data-admin-sidebar-toggle aria-label="Admin-Navigation öffnen">
            <svg aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#category"></use></svg>
        </button>
    </header>

    <div class="hnt-admin-layout">
        <aside class="hnt-admin-sidebar" id="admin-sidebar" aria-label="HNT-ACP Navigation">
            <div class="hnt-admin-sidebar-head">
                <a class="hnt-admin-brand" href="{{ route('admin.index') }}" aria-label="HNT-ACP Dashboard">
                    <img src="{{ asset('assets/socialite/images/logo-light.png') }}" alt="">
                    <span class="hnt-admin-brand-copy">
                        <strong>HNT.ROCKS</strong>
                        <small>Admin Center</small>
                    </span>
                </a>

                <form class="hnt-admin-sidebar-search" action="{{ route('admin.users.index') }}" method="get" role="search">
                    <svg aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#search-normal-1"></use></svg>
                    <input type="search" name="q" value="{{ request()->routeIs('admin.users.*') ? request('q') : '' }}" placeholder="Nutzer suchen..." aria-label="Nutzer suchen">
                    <kbd>⌘ /</kbd>
                </form>
            </div>

            <div class="hnt-admin-sidebar-scroll">
                @include('admin.partials.nav')
            </div>

            <div class="hnt-admin-sidebar-footer">
                <a class="hnt-admin-account" href="{{ route('feed.index') }}" title="Zur Website">
                    <img src="{{ $acpAvatar }}" alt="" loading="lazy">
                    <span>
                        <strong>{{ $acpName }}</strong>
                        <small>{{ $acpAdmin?->email ?: ($acpAdmin?->username ? '@'.$acpAdmin->username : 'Administrator') }}</small>
                    </span>
                </a>

                <div class="hnt-admin-sidebar-actions">
                    <a class="hnt-admin-sidebar-action" href="{{ route('admin.reports.index') }}" aria-label="Reports öffnen" title="Reports">
                        <svg aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#notification"></use></svg>
                    </a>
                    <a class="hnt-admin-sidebar-action" href="{{ route('feed.index') }}" aria-label="Website öffnen" title="Website">
                        <svg aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#monitor"></use></svg>
                    </a>
                </div>
            </div>
        </aside>

        <button class="hnt-admin-overlay" type="button" data-admin-sidebar-close aria-label="Navigation schließen"></button>

        <div class="hnt-admin-main">
            <div class="hnt-admin-frame">
                <div class="hnt-admin-scroll">
                    <header class="hnt-admin-topbar">
                        <div class="hnt-admin-topbar-context">
                            <strong>@yield('admin_heading', 'Dashboard')</strong>
                            <span class="hnt-admin-context-folder">
                                <svg aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#category"></use></svg>
                                HNT Administration
                            </span>
                        </div>

                        <div class="hnt-admin-topbar-actions">
                            <a class="hnt-admin-icon-link" href="{{ route('admin.reports.index') }}" aria-label="Reports öffnen" title="Reports">
                                <svg aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#notification"></use></svg>
                            </a>
                            <a class="hnt-admin-site-link" href="{{ route('feed.index') }}">
                                <svg aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#monitor"></use></svg>
                                <span>Website</span>
                            </a>
                        </div>
                    </header>

                    <main class="hnt-admin-content" id="admin-content">
                        @yield('content')
                    </main>

                    <footer class="hnt-admin-footer">
                        <span>{{ now()->year }} © HNT.ROCKS</span>
                        <a href="{{ route('feed.index') }}">Zur Website</a>
                    </footer>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/admin/hnt-acp-demo6.js') }}?v=1"></script>
    @stack('scripts')
</body>
</html>