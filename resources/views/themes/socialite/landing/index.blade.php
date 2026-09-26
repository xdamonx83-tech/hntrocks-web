<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('ui.landing_meta_title') }}</title>
    @include('partials.favicon')
    @include('react.landing-seo', ['mode' => 'head'])
</head>
<body class="antialiased">
    <div id="root">
        @include('react.landing-seo', ['mode' => 'fallback'])
    </div>
</body>
</html>
