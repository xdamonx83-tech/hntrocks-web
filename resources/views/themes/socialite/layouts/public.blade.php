<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('themes.socialite.partials.head')
    @stack('head')
</head>
<body class="hh-hnt-web-palette hnt-public-landing-body">
    <div id="wrapper" class="hnt-public-wrapper">
        <header class="hnt-public-header">
            <div class="hnt-public-header__inner">
                <a href="{{ route('home') }}" class="hnt-public-logo" aria-label="HNT.rocks">
                    <img src="{{ asset('assets/socialite/images/logo-light.png') }}" alt="HNT.rocks">
                </a>

                <nav class="hnt-public-nav" aria-label="{{ __('ui.landing_nav_aria') }}">
                    <a href="{{ route('cups.index') }}">{{ __('ui.cups') }}</a>
                    <a href="{{ route('loadout-challenges.index') }}">{{ __('ui.loadout_nav') }}</a>
                    <a href="{{ route('cup-ideas.index') }}">{{ __('ui.cup_ideas_nav') }}</a>
                    <a href="{{ route('app-beta.index') }}">{{ __('ui.android_app') }}</a>
                </nav>

                <div class="hnt-public-actions">
                    <a href="{{ route('locale.switch', app()->getLocale() === 'en' ? 'de' : 'en') }}" class="hnt-public-language">{{ app()->getLocale() === 'en' ? 'DE' : 'EN' }}</a>
                    <a href="{{ route('login') }}" class="hnt-public-login">{{ __('ui.login') }}</a>
                    <a href="{{ route('register') }}" class="hnt-public-register">{{ __('ui.register') }}</a>
                </div>
            </div>
        </header>

        <main>
            @yield('content')
        </main>

        <footer class="hnt-public-footer">
            <div class="hnt-public-footer__inner">
                <p>© {{ now()->year }} HNT.rocks</p>
                <nav aria-label="{{ __('ui.legal_navigation') }}">
                    <a href="{{ route('legal.impressum') }}">{{ __('ui.legal_impressum') }}</a>
                    <a href="{{ route('legal.datenschutz') }}">{{ __('ui.legal_datenschutz') }}</a>
                    <a href="{{ route('legal.nutzungsbedingungen') }}">{{ __('ui.legal_nutzungsbedingungen') }}</a>
                </nav>
            </div>
        </footer>
    </div>

    <script src="/assets/socialite/js/uikit.min.js"></script>
    <script src="/assets/socialite/js/simplebar.js"></script>
    <script src="/assets/socialite/js/script.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
    @stack('scripts')
</body>
</html>
