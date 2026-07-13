@php
    abort_unless(auth()->check() && auth()->user()->isAdmin(), 404);

    $hntMomentsPreviewKey = \App\Support\HntTheme::PREVIEW_SESSION_KEY;
    $hntMomentsPreviewHadSession = session()->has($hntMomentsPreviewKey);
    $hntMomentsPreviewAllowAnyAdmin = (bool) config('hunthub.theme.preview.allow_any_admin', false);

    session()->put($hntMomentsPreviewKey, true);
    config(['hunthub.theme.preview.allow_any_admin' => true]);

    $hntMomentsViewData = get_defined_vars();
    $hntMomentsHtml = view('themes.hnt_preview.moments.demo-feed', $hntMomentsViewData)->render();

    $hntThemeColorsVersion = @filemtime(public_path('assets/themes/hnt_preview/theme-colors.css')) ?: time();
    $hntThemePolishVersion = @filemtime(public_path('assets/themes/hnt_preview/theme-page-polish.css')) ?: time();
    $hntMomentsPolishVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-moments/moments-polish.css')) ?: time();
    $hntHeaderVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js')) ?: time();
    $hntHeaderLiveVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js')) ?: time();

    $hntMomentsHeadAssets =
        '<link data-hnt-theme-colors href="'.asset('assets/themes/hnt_preview/theme-colors.css').'?v='.$hntThemeColorsVersion.'" rel="stylesheet">'.
        '<link data-hnt-theme-page-polish href="'.asset('assets/themes/hnt_preview/theme-page-polish.css').'?v='.$hntThemePolishVersion.'" rel="stylesheet">'.
        '<link data-hnt-moments-polish href="'.asset('assets/themes/hnt_preview/dashboard-moments/moments-polish.css').'?v='.$hntMomentsPolishVersion.'" rel="stylesheet">';

    $hntMomentsBodyAssets =
        '<script>window.HNT_DASHBOARD_HEADER_ENDPOINT='.\Illuminate\Support\Js::from(route('feed.index')).';</script>'.
        '<script src="'.asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js').'?v='.$hntHeaderVersion.'"></script>'.
        '<script src="'.asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js').'?v='.$hntHeaderLiveVersion.'"></script>';

    $hntMomentsHtml = str_replace('</head>', $hntMomentsHeadAssets.'</head>', $hntMomentsHtml);
    $hntMomentsHtml = str_replace('</body>', $hntMomentsBodyAssets.'</body>', $hntMomentsHtml);

    config(['hunthub.theme.preview.allow_any_admin' => $hntMomentsPreviewAllowAnyAdmin]);

    if (! $hntMomentsPreviewHadSession) {
        session()->forget($hntMomentsPreviewKey);
    }
@endphp

{!! $hntMomentsHtml !!}
