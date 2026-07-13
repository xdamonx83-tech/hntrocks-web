@php
    abort_unless(auth()->check() && auth()->user()->isAdmin(), 404);

    $hntMomentsPreviewKey = \App\Support\HntTheme::PREVIEW_SESSION_KEY;
    $hntMomentsPreviewHadSession = session()->has($hntMomentsPreviewKey);
    $hntMomentsPreviewAllowAnyAdmin = (bool) config('hunthub.theme.preview.allow_any_admin', false);

    session()->put($hntMomentsPreviewKey, true);
    config(['hunthub.theme.preview.allow_any_admin' => true]);
@endphp

@include('themes.hnt_preview.moments.demo-feed')

@php
    config(['hunthub.theme.preview.allow_any_admin' => $hntMomentsPreviewAllowAnyAdmin]);

    if (! $hntMomentsPreviewHadSession) {
        session()->forget($hntMomentsPreviewKey);
    }
@endphp
