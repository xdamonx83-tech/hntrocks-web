<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@include('partials.head')
<body>
    @include('partials.page-loader')
    @include('partials.header')
    @include('partials.sidebar')
    @include('partials.chat-dock')
    <main class="content-grid hh-main-content" id="hh-main-content">
        @yield('content')
    </main>
    @include('partials.mobile-nav')
    @include('partials.achievement-toasts')
    @include('partials.report-modal')
    @include('partials.app-promo-modal')
    @include('partials.footer')
    @include('partials.cookie-consent')
</body>
</html>
