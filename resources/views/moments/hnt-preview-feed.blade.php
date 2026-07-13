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
    $hntMomentsLiveFinalVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-moments/moments-live-final.css')) ?: time();
    $hntMomentsLiveFinalJsVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-moments/moments-live-final.js')) ?: time();
    $hntMomentsSharedFinalVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-moments/moments-shared-final.css')) ?: time();
    $hntHeaderVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js')) ?: time();
    $hntHeaderLiveVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js')) ?: time();

    $hntMomentsHeadAssets =
        '<link data-hnt-theme-colors href="'.asset('assets/themes/hnt_preview/theme-colors.css').'?v='.$hntThemeColorsVersion.'" rel="stylesheet">'.
        '<link data-hnt-theme-page-polish href="'.asset('assets/themes/hnt_preview/theme-page-polish.css').'?v='.$hntThemePolishVersion.'" rel="stylesheet">'.
        '<link data-hnt-moments-live-final href="'.asset('assets/themes/hnt_preview/dashboard-moments/moments-live-final.css').'?v='.$hntMomentsLiveFinalVersion.'" rel="stylesheet">';

    $hntMomentsFinalCssUrl = asset('assets/themes/hnt_preview/dashboard-moments/moments-shared-final.css').'?v='.$hntMomentsSharedFinalVersion;

    $hntMomentsBodyAssets =
        '<script>window.HNT_DASHBOARD_HEADER_ENDPOINT='.\Illuminate\Support\Js::from(route('feed.index')).';window.HNT_MOMENTS_VIEWER_HANDLE='.\Illuminate\Support\Js::from('@'.auth()->user()->username).';</script>'.
        '<script src="'.asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js').'?v='.$hntHeaderVersion.'"></script>'.
        '<script src="'.asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js').'?v='.$hntHeaderLiveVersion.'"></script>'.
        '<script>(()=>{const install=()=>{if(document.querySelector(\'link[data-hnt-moments-shared-final]\'))return;const link=document.createElement(\'link\');link.rel=\'stylesheet\';link.href='.\Illuminate\Support\Js::from($hntMomentsFinalCssUrl).';link.dataset.hntMomentsSharedFinal=\'1\';document.head.appendChild(link)};if(document.readyState===\'complete\')install();else window.addEventListener(\'load\',install,{once:true})})();</script>'.
        '<script src="'.asset('assets/themes/hnt_preview/dashboard-moments/moments-live-final.js').'?v='.$hntMomentsLiveFinalJsVersion.'"></script>';

    $hntMomentsHtml = str_replace('</head>', $hntMomentsHeadAssets.'</head>', $hntMomentsHtml);
    $hntMomentsHtml = str_replace('</body>', $hntMomentsBodyAssets.'</body>', $hntMomentsHtml);

    config(['hunthub.theme.preview.allow_any_admin' => $hntMomentsPreviewAllowAnyAdmin]);

    if (! $hntMomentsPreviewHadSession) {
        session()->forget($hntMomentsPreviewKey);
    }
@endphp

{!! $hntMomentsHtml !!}
