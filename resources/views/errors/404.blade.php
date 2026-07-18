@php
    $errorFeedUrl = \Illuminate\Support\Facades\Route::has('feed.index')
        ? route('feed.index')
        : url('/');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() === 'en' ? 'en' : 'de' }}">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1" name="viewport"/>
<meta content="noindex,nofollow,noarchive" name="robots"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>{{ __('errors.page_title') }}</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&amp;display=swap" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/common.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/feed.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/errors/error-page.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/errors/error-page.css')) ?: time() }}" rel="stylesheet"/>
</head>
<body data-error-page="404" data-page="error">
@include('themes.hnt_preview.partials.icons')
<div aria-hidden="true" class="feed-shell" hidden style="display:none!important"></div>
<main class="app-shell hnt-error-shell">
@include('themes.hnt_preview.partials.header')
<section aria-labelledby="hntErrorTitle" class="hnt-error-stage">
<article class="hnt-error-card">
<div class="hnt-error-copy">
<span class="hnt-error-eyebrow">{{ __('errors.eyebrow') }}</span>
<h1 id="hntErrorTitle">{{ __('errors.title') }}</h1>
<p>{{ __('errors.description') }}</p>
<small>{{ __('errors.privacy_note') }}</small>
<div class="hnt-error-actions">
<a class="hnt-error-primary" href="{{ $errorFeedUrl }}">
<span>{{ __('errors.back_to_feed') }}</span>
<svg aria-hidden="true"><use href="#i-arrow"></use></svg>
</a>
<button class="hnt-error-secondary" data-error-back type="button">{{ __('errors.go_back') }}</button>
</div>
</div>
<div aria-hidden="true" class="hnt-error-visual">
<div class="hnt-error-code-ring">
<span>404</span>
<small>{{ __('errors.not_found') }}</small>
</div>
<i class="hnt-error-track track-one"></i>
<i class="hnt-error-track track-two"></i>
</div>
</article>
</section>
</main>
<script>
window.HNT_DASHBOARD_HEADER_ENDPOINT = {{ \Illuminate\Support\Js::from($errorFeedUrl) }};
document.querySelector('[data-error-back]')?.addEventListener('click', () => {
    if (window.history.length > 1) {
        window.history.back();
        return;
    }

    window.location.assign({{ \Illuminate\Support\Js::from($errorFeedUrl) }});
});
</script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
@if(auth()->check())
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js')) ?: time() }}"></script>
@endif
</body>
</html>
