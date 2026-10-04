<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#141518">
    <title>@yield('title', 'HNT-ACP · hnt.rocks')</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('assets/admin/admin.css') }}?v=521">
    <link rel="stylesheet" href="{{ asset('assets/admin/hnt-acp.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('assets/admin/admin-collaboration-v2.css') }}?v=1">
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
    <div class="hnt-admin-layout">
        <aside class="hnt-admin-sidebar" aria-label="HNT-ACP Navigation">
            <a class="hnt-admin-brand" href="{{ route('admin.index') }}" aria-label="HNT-ACP Dashboard">
                <img src="{{ asset('assets/socialite/images/logo-light.png') }}" alt="">
                <span>HNT-ACP</span>
            </a>

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
                    <svg aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#more"></use></svg>
                </a>
            </div>
        </aside>

        <div class="hnt-admin-main">
            <header class="hnt-admin-topbar">
                <div class="hnt-admin-topbar-context">
                    <strong>@yield('admin_heading', 'Dashboard')</strong>
                    <span class="hnt-admin-context-divider" aria-hidden="true"></span>
                    <span class="hnt-admin-context-folder">
                        <svg aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#category"></use></svg>
                        HNT Administration
                    </span>
                </div>

                <div class="hnt-admin-topbar-actions">
                    <form class="hnt-admin-search" action="{{ route('admin.users.index') }}" method="get" role="search">
                        <svg aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#search-normal-1"></use></svg>
                        <input type="search" name="q" value="{{ request()->routeIs('admin.users.*') ? request('q') : '' }}" placeholder="Nutzer suchen..." aria-label="Nutzer suchen">
                    </form>
                    <span class="hnt-admin-topbar-divider" aria-hidden="true"></span>
                    <a class="hnt-admin-icon-link" href="{{ route('admin.reports.index') }}" aria-label="Reports öffnen" title="Reports">
                        <svg aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#notification"></use></svg>
                    </a>
                    <span class="hnt-admin-icon-link is-static" aria-hidden="true">
                        <svg><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#moon"></use></svg>
                    </span>
                    <span class="hnt-admin-topbar-divider" aria-hidden="true"></span>
                    <details class="hnt-admin-user-dropdown">
                        <summary aria-label="Admin-Menü öffnen">
                            <img src="{{ $acpAvatar }}" alt="" loading="lazy">
                            <span>{{ $acpAdmin?->username ? '@'.$acpAdmin->username : $acpName }}</span>
                            <svg aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#more"></use></svg>
                        </summary>
                        <nav class="hnt-admin-user-dropdown-menu" aria-label="Admin- und Teamwerkzeuge">
                            <div class="hnt-admin-user-dropdown-identity">
                                <strong>{{ $acpName }}</strong>
                                <small>{{ $acpAdmin?->email ?: 'HNT Administration' }}</small>
                            </div>
                            <a href="{{ route('admin.collaboration.chat') }}">
                                <svg aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#message-question"></use></svg>
                                Interner Admin-Chat
                                <small>Vorschau</small>
                            </a>
                            <a href="{{ route('admin.collaboration.projects') }}">
                                <svg aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#document-text"></use></svg>
                                Aufgaben & To-do
                                <small>Vorschau</small>
                            </a>
                            <div class="hnt-admin-user-dropdown-separator" aria-hidden="true"></div>
                            <a href="{{ route('admin.index') }}">
                                <svg aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#category"></use></svg>
                                Bisheriges Admin Center
                            </a>
                            <a href="{{ route('feed.index') }}">
                                <svg aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#monitor"></use></svg>
                                Zur Website
                            </a>
                        </nav>
                    </details>
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
