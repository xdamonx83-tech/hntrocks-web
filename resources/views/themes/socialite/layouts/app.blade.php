<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('themes.socialite.partials.head')
    @stack('head')
</head>
<body class="hh-hnt-web-palette">

    <div id="wrapper">

        @include('themes.socialite.partials.header')
        @include('themes.socialite.partials.sidebar')

        <!-- main contents -->
        <main id="site__main" class="2xl:ml-[--w-side]  xl:ml-[--w-side-sm] p-2.5 h-[calc(100vh-var(--m-top))] mt-[--m-top]">
            @yield('content')
        </main>


    @include('themes.socialite.partials.crowns-claim-modal')

    @include('themes.socialite.partials.tail')
    @stack('scripts')

</body>
</html>
