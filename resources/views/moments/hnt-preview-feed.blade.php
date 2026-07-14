@php
    abort_unless(auth()->check() && auth()->user()->isAdmin(), 404);

    $hntMomentsPreviewKey = \App\Support\HntTheme::PREVIEW_SESSION_KEY;
    $hntMomentsPreviewHadSession = session()->has($hntMomentsPreviewKey);
    $hntMomentsPreviewAllowAnyAdmin = (bool) config('hunthub.theme.preview.allow_any_admin', false);

    session()->put($hntMomentsPreviewKey, true);
    config(['hunthub.theme.preview.allow_any_admin' => true]);

    $hntMomentsViewData = get_defined_vars();
    $hntMomentsHtml = view('themes.hnt_preview.moments.demo-feed', $hntMomentsViewData)->render();

    $hntMomentAspectMap = \App\Models\Moment::query()
        ->with('media')
        ->published()
        ->latest('published_at')
        ->latest('id')
        ->limit(30)
        ->get()
        ->mapWithKeys(function ($item): array {
            $ratio = data_get($item->media?->metadata, 'aspect_ratio');
            return [(string) $item->id => in_array($ratio, ['9:16', '16:9'], true) ? $ratio : null];
        })
        ->filter()
        ->all();

    if (isset($moment) && $moment instanceof \App\Models\Moment) {
        $moment->loadMissing('media');
        $ratio = data_get($moment->media?->metadata, 'aspect_ratio');
        if (in_array($ratio, ['9:16', '16:9'], true)) {
            $hntMomentAspectMap[(string) $moment->id] = $ratio;
        }
    }

    $hntThemeColorsVersion = @filemtime(public_path('assets/themes/hnt_preview/theme-colors.css')) ?: time();
    $hntThemePolishVersion = @filemtime(public_path('assets/themes/hnt_preview/theme-page-polish.css')) ?: time();
    $hntMomentsLiveFinalVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-moments/moments-live-final.css')) ?: time();
    $hntMomentsLiveFinalJsVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-moments/moments-live-final.js')) ?: time();
    $hntMomentsActionsVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-moments/moments-actions.css')) ?: time();
    $hntMomentsActionsCompatCssVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-moments/moments-actions-compat.css')) ?: time();
    $hntMomentsActionsJsVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-moments/moments-actions.js')) ?: time();
    $hntMomentsActionsCompatJsVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-moments/moments-actions-compat.js')) ?: time();
    $hntMomentsSharedFinalVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-moments/moments-shared-final.css')) ?: time();
    $hntSharedVideoCssVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/shared-video-player.css')) ?: time();
    $hntSharedVideoJsVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/shared-video-player.js')) ?: time();
    $hntMomentsVerticalCanvasCssVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-moments/moments-vertical-canvas.css')) ?: time();
    $hntMomentsVerticalCanvasJsVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-moments/moments-vertical-canvas.js')) ?: time();
    $hntHeaderVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js')) ?: time();
    $hntHeaderLiveVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js')) ?: time();

    $hntMomentsHeadAssets =
        '<link data-hnt-theme-colors href="'.asset('assets/themes/hnt_preview/theme-colors.css').'?v='.$hntThemeColorsVersion.'" rel="stylesheet">'.
        '<link data-hnt-theme-page-polish href="'.asset('assets/themes/hnt_preview/theme-page-polish.css').'?v='.$hntThemePolishVersion.'" rel="stylesheet">'.
        '<link data-hnt-moments-live-final href="'.asset('assets/themes/hnt_preview/dashboard-moments/moments-live-final.css').'?v='.$hntMomentsLiveFinalVersion.'" rel="stylesheet">'.
        '<link data-hnt-moments-actions href="'.asset('assets/themes/hnt_preview/dashboard-moments/moments-actions.css').'?v='.$hntMomentsActionsVersion.'" rel="stylesheet">'.
        '<link data-hnt-moments-actions-compat href="'.asset('assets/themes/hnt_preview/dashboard-moments/moments-actions-compat.css').'?v='.$hntMomentsActionsCompatCssVersion.'" rel="stylesheet">'.
        '<link data-hnt-shared-video-player href="'.asset('assets/themes/hnt_preview/dashboard-feed/shared-video-player.css').'?v='.$hntSharedVideoCssVersion.'" rel="stylesheet">'.
        '<link data-hnt-moments-vertical-canvas href="'.asset('assets/themes/hnt_preview/dashboard-moments/moments-vertical-canvas.css').'?v='.$hntMomentsVerticalCanvasCssVersion.'" rel="stylesheet">';

    $hntMomentsFinalCssUrl = asset('assets/themes/hnt_preview/dashboard-moments/moments-shared-final.css').'?v='.$hntMomentsSharedFinalVersion;

    $hntMomentsBodyAssets =
        '<script>window.HNT_DASHBOARD_HEADER_ENDPOINT='.
            \Illuminate\Support\Js::from(route('feed.index')).
            ';window.HNT_MOMENTS_VIEWER_HANDLE='.
            \Illuminate\Support\Js::from('@'.auth()->user()->username).
            ';window.HNT_MOMENT_ASPECTS='.
            \Illuminate\Support\Js::from($hntMomentAspectMap).
            ';</script>'.
        '<script src="'.asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js').'?v='.$hntHeaderVersion.'"></script>'.
        '<script src="'.asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js').'?v='.$hntHeaderLiveVersion.'"></script>'.
        '<script>(()=>{const install=()=>{if(document.querySelector(\'link[data-hnt-moments-shared-final]\'))return;const link=document.createElement(\'link\');link.rel=\'stylesheet\';link.href='.
            \Illuminate\Support\Js::from($hntMomentsFinalCssUrl).
            ';link.dataset.hntMomentsSharedFinal=\'1\';document.head.appendChild(link)};if(document.readyState===\'complete\')install();else window.addEventListener(\'load\',install,{once:true})})();</script>'.
        '<script src="'.asset('assets/themes/hnt_preview/dashboard-moments/moments-live-final.js').'?v='.$hntMomentsLiveFinalJsVersion.'"></script>'.
        '<script src="'.asset('assets/themes/hnt_preview/dashboard-moments/moments-actions.js').'?v='.$hntMomentsActionsJsVersion.'"></script>'.
        '<script src="'.asset('assets/themes/hnt_preview/dashboard-moments/moments-actions-compat.js').'?v='.$hntMomentsActionsCompatJsVersion.'"></script>'.
        '<script src="'.asset('assets/themes/hnt_preview/dashboard-feed/shared-video-player.js').'?v='.$hntSharedVideoJsVersion.'"></script>'.
        '<script src="'.asset('assets/themes/hnt_preview/dashboard-moments/moments-vertical-canvas.js').'?v='.$hntMomentsVerticalCanvasJsVersion.'"></script>';

    $hntMomentsHtml = str_replace('</head>', $hntMomentsHeadAssets.'</head>', $hntMomentsHtml);
    $hntMomentsHtml = str_replace('</body>', $hntMomentsBodyAssets.'</body>', $hntMomentsHtml);

    config(['hunthub.theme.preview.allow_any_admin' => $hntMomentsPreviewAllowAnyAdmin]);

    if (! $hntMomentsPreviewHadSession) {
        session()->forget($hntMomentsPreviewKey);
    }
@endphp

{!! $hntMomentsHtml !!}
