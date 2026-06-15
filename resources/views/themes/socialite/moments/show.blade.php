@php
    $viewer = auth()->user();
    $caption = $moment->caption ?: __('ui.moments_default_caption');
    $authorName = $moment->user->name ?: $moment->user->username;
    $authorHandle = '@'.$moment->user->username;
    $isLiked = $moment->isLikedBy($viewer);
    $isBookmarked = $moment->isBookmarkedBy($viewer);
    $videoUrl = $moment->mediaUrl();
    $videoMime = $moment->media?->mime_type ?: 'video/mp4';
    $videoPoster = $moment->cover?->thumbnailUrl() ?: ($moment->media?->thumbnail_path ? $moment->media->thumbnailUrl() : null);
    $canManageMoment = $moment->canBeManagedBy($viewer);
    $mediaMetadata = is_array($moment->media?->metadata) ? $moment->media->metadata : [];
    $aspectRatioLabel = $mediaMetadata['aspect_ratio_label'] ?? $mediaMetadata['format'] ?? null;
    if (! in_array($aspectRatioLabel, ['9:16', '16:9', '1:1'], true)) {
        $mediaWidth = (int) ($moment->media?->width ?? 0);
        $mediaHeight = (int) ($moment->media?->height ?? 0);
        $aspectRatioLabel = $mediaWidth > 0 && $mediaHeight > 0 && abs(($mediaWidth / $mediaHeight) - 1) <= 0.08
            ? '1:1'
            : ($mediaWidth > $mediaHeight ? '16:9' : '9:16');
    }
    $isLandscape = $aspectRatioLabel === '16:9';
    $isContained = $aspectRatioLabel !== '9:16';
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $caption }} · HNT.rocks Moments</title>
    <link href="/assets/socialite/images/favicon.png" rel="icon" type="image/png">
    <link rel="stylesheet" href="/assets/socialite/css/tailwind.css">
    <link rel="stylesheet" href="/assets/socialite/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@200;300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        html,
        body {
            width: 100%;
            height: 100%;
            overflow: hidden;
            background: #000;
        }

        body {
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #fff;
        }

        .hh-socialite-moment-shell {
            position: fixed;
            inset: 0;
            z-index: 999999;
            display: grid;
            grid-template-columns: minmax(64px, 1fr) minmax(320px, 520px) minmax(96px, 1fr);
            align-items: center;
            gap: 32px;
            background: radial-gradient(circle at 50% 12%, rgba(42, 44, 48, .92), rgba(0, 0, 0, 1) 54%), #000;
            overflow: hidden;
            padding: max(18px, env(safe-area-inset-top)) max(18px, env(safe-area-inset-right)) max(18px, env(safe-area-inset-bottom)) max(18px, env(safe-area-inset-left));
        }

        .hh-socialite-moment-topbar {
            position: fixed;
            top: max(18px, env(safe-area-inset-top));
            left: max(18px, env(safe-area-inset-left));
            right: max(18px, env(safe-area-inset-right));
            z-index: 30;
            display: flex;
            align-items: center;
            justify-content: space-between;
            pointer-events: none;
        }

        .hh-socialite-moment-back,
        .hh-socialite-moment-topbrand {
            pointer-events: auto;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 44px;
            border-radius: 999px;
            background: rgba(18, 18, 20, .72);
            border: 1px solid rgba(255, 255, 255, .12);
            color: #fff;
            padding: 0 16px;
            backdrop-filter: blur(18px);
            box-shadow: 0 14px 36px rgba(0, 0, 0, .36);
            font-weight: 700;
            text-decoration: none;
        }

        .hh-socialite-moment-top-actions {
            pointer-events: auto;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .hh-socialite-moment-topbrand {
            font-size: 13px;
            color: rgba(255, 255, 255, .74);
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .hh-socialite-moment-top-icon,
        .hh-socialite-moment-create {
            pointer-events: auto;
            width: 44px;
            height: 44px;
            border-radius: 999px;
            background: rgba(18, 18, 20, .72);
            border: 1px solid rgba(255, 255, 255, .14);
            color: #fff;
            display: grid;
            place-items: center;
            backdrop-filter: blur(18px);
            box-shadow: 0 14px 36px rgba(0, 0, 0, .36);
            text-decoration: none;
            transition: transform .16s ease, background .16s ease;
        }

        .hh-socialite-moment-top-icon:hover,
        .hh-socialite-moment-create:hover {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, .14);
        }

        .hh-socialite-moment-create ion-icon {
            font-size: 24px;
        }

        .hh-socialite-moment-top-icon svg {
            width: 21px;
            height: 21px;
            stroke-width: 2.4;
        }

        .hh-socialite-moment-back ion-icon {
            font-size: 22px;
        }

        .hh-socialite-moment-stage {
            grid-column: 2;
            position: relative;
            width: min(520px, calc(100vw - 160px));
            aspect-ratio: 9 / 16;
            max-height: calc(100vh - 56px);
            justify-self: center;
            border-radius: 18px;
            overflow: hidden;
            background: #050505;
            box-shadow: 0 32px 90px rgba(0, 0, 0, .72);
        }

        .hh-socialite-moment-shell.is-landscape {
            grid-template-columns: minmax(64px, 1fr) minmax(520px, 960px) minmax(96px, 1fr);
        }

        .hh-socialite-moment-stage.is-landscape {
            width: min(960px, calc(100vw - 160px));
            aspect-ratio: 16 / 9;
        }

        .hh-socialite-moment-stage.is-square {
            aspect-ratio: 1 / 1;
        }

        .hh-socialite-moment-video {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            background: #000;
        }

        .hh-socialite-moment-video.is-contained {
            object-fit: contain;
        }

        .hh-socialite-moment-video::-webkit-media-controls,
        .hh-socialite-moment-video::-webkit-media-controls-enclosure,
        .hh-socialite-moment-video::-webkit-media-controls-panel {
            display: none !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }

        .hh-socialite-moment-vignette {
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: linear-gradient(180deg, rgba(0,0,0,.25) 0%, rgba(0,0,0,0) 32%, rgba(0,0,0,.10) 58%, rgba(0,0,0,.76) 100%);
        }

        .hh-socialite-moment-copy {
            position: absolute;
            left: 18px;
            right: 72px;
            bottom: 18px;
            z-index: 4;
            display: flex;
            flex-direction: column;
            gap: 10px;
            text-shadow: 0 2px 12px rgba(0,0,0,.7);
        }

        .hh-socialite-moment-author {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #fff;
            text-decoration: none;
            font-weight: 800;
            width: max-content;
            max-width: 100%;
        }

        .hh-socialite-moment-author img {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,.82);
        }

        .hh-socialite-moment-caption {
            margin: 0;
            font-size: 15px;
            line-height: 1.35;
            font-weight: 700;
        }

        .hh-socialite-moment-description {
            margin: 0;
            color: rgba(255,255,255,.84);
            font-size: 13px;
            line-height: 1.45;
        }

        .hh-socialite-moment-actions {
            position: absolute;
            z-index: 5;
            right: 18px;
            bottom: 28px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .hh-socialite-moment-action-wrap {
            appearance: none;
            border: 0;
            background: transparent;
            padding: 0;
            margin: 0;
            cursor: pointer;
            font: inherit;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            text-shadow: 0 1px 7px rgba(0,0,0,.75);
        }

        .hh-socialite-moment-action {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,.16);
            background: rgba(20,20,23,.72);
            color: #fff;
            display: grid;
            place-items: center;
            backdrop-filter: blur(16px);
            box-shadow: 0 16px 30px rgba(0,0,0,.32);
            transition: transform .16s ease, background .16s ease;
        }

        .hh-socialite-moment-action:hover {
            transform: translateY(-2px);
            background: rgba(255,255,255,.14);
        }

        .hh-socialite-moment-action.is-active {
            background: rgba(37, 99, 235, .92);
            border-color: rgba(96, 165, 250, .75);
        }

        .hh-socialite-moment-action ion-icon {
            font-size: 20px;
        }

        .hh-socialite-moment-action svg {
            width: 19px;
            height: 19px;
            stroke-width: 2.35;
        }

        .hh-socialite-moment-more-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1000003;
            background: rgba(0,0,0,.42);
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s ease;
        }

        .hh-socialite-moment-more-backdrop.is-open {
            opacity: 1;
            pointer-events: auto;
        }

        .hh-socialite-moment-more-sheet {
            position: fixed;
            right: max(18px, env(safe-area-inset-right));
            bottom: max(18px, env(safe-area-inset-bottom));
            z-index: 1000004;
            width: min(420px, calc(100vw - 36px));
            max-height: calc(100vh - 36px);
            overflow: auto;
            border-radius: 24px;
            background: rgba(18, 18, 20, .96);
            color: #fff;
            border: 1px solid rgba(255,255,255,.12);
            box-shadow: 0 28px 90px rgba(0,0,0,.58);
            backdrop-filter: blur(20px);
            transform: translateY(20px) scale(.98);
            opacity: 0;
            pointer-events: none;
            transition: opacity .18s ease, transform .18s ease;
        }

        .hh-socialite-moment-more-sheet.is-open {
            transform: translateY(0) scale(1);
            opacity: 1;
            pointer-events: auto;
        }

        .hh-socialite-moment-more-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 18px 18px 12px;
            border-bottom: 1px solid rgba(255,255,255,.10);
        }

        .hh-socialite-moment-more-head h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 900;
        }

        .hh-socialite-moment-more-close {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background: rgba(255,255,255,.10);
            color: #fff;
        }

        .hh-socialite-moment-more-content {
            padding: 16px 18px 18px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .hh-socialite-moment-more-action,
        .hh-socialite-moment-more-content button.hh-socialite-moment-more-action {
            min-height: 44px;
            border-radius: 16px;
            padding: 0 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,.08);
            color: #fff;
            text-decoration: none;
            font-weight: 800;
            border: 1px solid rgba(255,255,255,.08);
            width: 100%;
            text-align: left;
        }

        .hh-socialite-moment-more-action svg {
            width: 18px;
            height: 18px;
        }

        .hh-socialite-moment-more-action.is-danger {
            color: #fecaca;
            background: rgba(239, 68, 68, .12);
            border-color: rgba(248, 113, 113, .18);
        }

        .hh-socialite-moment-edit-form {
            display: none;
            flex-direction: column;
            gap: 10px;
            padding: 12px;
            border-radius: 18px;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.08);
        }

        .hh-socialite-moment-edit-form.is-open {
            display: flex;
        }

        .hh-socialite-moment-edit-form label {
            font-size: 12px;
            font-weight: 900;
            color: rgba(255,255,255,.72);
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .hh-socialite-moment-edit-form textarea {
            min-height: 120px;
            resize: vertical;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(0,0,0,.30);
            color: #fff;
            padding: 12px;
            outline: none;
        }

        .hh-socialite-moment-edit-form button {
            min-height: 42px;
            border-radius: 999px;
            background: #2563eb;
            color: #fff;
            font-weight: 900;
        }

        .hh-socialite-moment-side-nav {
            grid-column: 3;
            justify-self: start;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .hh-socialite-moment-nav-button {
            width: 54px;
            height: 54px;
            display: grid;
            place-items: center;
            border-radius: 999px;
            border: 2px solid rgba(255,255,255,.42);
            color: rgba(255,255,255,.86);
            background: rgba(18, 18, 20, .62);
            backdrop-filter: blur(12px);
            text-decoration: none;
        }

        .hh-socialite-moment-nav-button.is-disabled {
            opacity: .28;
            pointer-events: none;
        }

        .hh-socialite-moment-nav-button ion-icon {
            font-size: 30px;
        }

        .hh-socialite-moment-comments {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            z-index: 1000002;
            width: min(440px, 100vw);
            transform: translateX(105%);
            transition: transform .22s ease;
            background: #fff;
            color: #111827;
            box-shadow: -28px 0 70px rgba(0,0,0,.45);
            display: flex;
            flex-direction: column;
        }

        .hh-socialite-moment-comments.is-open {
            transform: translateX(0);
        }

        .hh-socialite-moment-comments-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .hh-socialite-moment-comments-head h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
        }

        .hh-socialite-moment-close {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #111827;
            display: grid;
            place-items: center;
        }

        .hh-socialite-moment-comment-list {
            flex: 1;
            overflow-y: auto;
            padding: 18px 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .hh-socialite-moment-comment {
            display: grid;
            grid-template-columns: 40px minmax(0, 1fr);
            gap: 12px;
        }

        .hh-socialite-moment-comment img {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            object-fit: cover;
        }

        .hh-socialite-moment-comment-body {
            border-radius: 18px;
            background: #f1f5f9;
            padding: 11px 13px;
        }

        .hh-socialite-moment-comment-body header {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            font-size: 13px;
            margin-bottom: 4px;
        }

        .hh-socialite-moment-comment-body strong {
            font-weight: 800;
        }

        .hh-socialite-moment-comment-body small {
            color: #64748b;
        }

        .hh-socialite-moment-comment-body p {
            margin: 0;
            color: #334155;
            line-height: 1.42;
            font-size: 14px;
        }

        .hh-socialite-moment-comment-form {
            border-top: 1px solid #e5e7eb;
            padding: 14px 16px calc(14px + env(safe-area-inset-bottom));
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
        }

        .hh-socialite-moment-comment-form textarea {
            min-height: 46px;
            max-height: 110px;
            resize: vertical;
            border-radius: 18px;
            background: #f1f5f9;
            border: 1px solid transparent;
            padding: 12px 14px;
            outline: none;
            color: #111827;
        }

        .hh-socialite-moment-comment-form button {
            align-self: end;
            min-height: 46px;
            border-radius: 999px;
            background: #2563eb;
            color: #fff;
            padding: 0 18px;
            font-weight: 800;
        }

        .hh-socialite-moment-comments-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1000001;
            background: rgba(0,0,0,.38);
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s ease;
        }

        .hh-socialite-moment-comments-backdrop.is-open {
            opacity: 1;
            pointer-events: auto;
        }

        @media (max-width: 900px) {
            .hh-socialite-moment-shell {
                display: block;
                padding: 0;
                background: #000;
            }

            .hh-socialite-moment-topbar {
                top: max(12px, env(safe-area-inset-top));
                left: max(12px, env(safe-area-inset-left));
                right: max(12px, env(safe-area-inset-right));
            }

            .hh-socialite-moment-topbrand {
                display: none;
            }

            .hh-socialite-moment-top-actions {
                gap: 8px;
            }

            .hh-socialite-moment-top-icon,
            .hh-socialite-moment-create {
                width: 40px;
                height: 40px;
            }

            .hh-socialite-moment-create ion-icon {
                font-size: 22px;
            }

            .hh-socialite-moment-back {
                min-height: 40px;
                width: 40px;
                padding: 0;
                justify-content: center;
            }

            .hh-socialite-moment-back span {
                display: none;
            }

            .hh-socialite-moment-stage {
                width: 100vw;
                height: 100vh;
                max-height: none;
                aspect-ratio: auto;
                border-radius: 0;
            }

            .hh-socialite-moment-stage.is-landscape,
            .hh-socialite-moment-stage.is-square {
                display: grid;
                place-items: center;
                aspect-ratio: auto;
            }

            .hh-socialite-moment-side-nav {
                display: none !important;
            }

            .hh-socialite-moment-actions {
                right: max(12px, env(safe-area-inset-right));
                bottom: calc(92px + env(safe-area-inset-bottom));
                gap: 10px;
            }

            .hh-socialite-moment-action {
                width: 38px;
                height: 38px;
            }

            .hh-socialite-moment-copy {
                left: max(14px, env(safe-area-inset-left));
                right: 76px;
                bottom: calc(20px + env(safe-area-inset-bottom));
            }

            .hh-socialite-moment-comments {
                top: auto;
                left: 0;
                width: 100vw;
                max-height: min(78vh, 720px);
                border-radius: 24px 24px 0 0;
                transform: translateY(105%);
            }

            .hh-socialite-moment-comments.is-open {
                transform: translateY(0);
            }

            .hh-socialite-moment-more-sheet {
                left: 0;
                right: 0;
                bottom: 0;
                width: 100vw;
                max-height: min(82vh, 720px);
                border-radius: 24px 24px 0 0;
                transform: translateY(105%);
            }

            .hh-socialite-moment-more-sheet.is-open {
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <main class="hh-socialite-moment-shell {{ $isLandscape ? 'is-landscape' : '' }}" data-socialite-moment-viewer>
        <div class="hh-socialite-moment-topbar">
            <a class="hh-socialite-moment-back" href="{{ route('feed.index') }}" data-socialite-moment-back aria-label="{{ __('ui.back') }}">
                <ion-icon name="arrow-back-outline" aria-hidden="true"></ion-icon>
                <span>{{ __('ui.back') }}</span>
            </a>
            <div class="hh-socialite-moment-top-actions">
                <button class="hh-socialite-moment-top-icon" type="button" data-socialite-moment-mute aria-label="{{ __('ui.unmute') }}" title="{{ __('ui.unmute') }}" data-muted-label="{{ __('ui.unmute') }}" data-unmuted-label="{{ __('ui.mute') }}">
                    <svg data-socialite-moment-mute-on xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><line x1="22" y1="9" x2="16" y2="15"></line><line x1="16" y1="9" x2="22" y2="15"></line></svg>
                    <svg data-socialite-moment-mute-off hidden xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path><path d="M19.07 4.93a10 10 0 0 1 0 14.14"></path></svg>
                </button>
                @auth
                    <a class="hh-socialite-moment-create" href="{{ route('moments.create') }}" aria-label="{{ __('ui.moment_add') }}" title="{{ __('ui.moment_add') }}">
                        <ion-icon name="add-outline" aria-hidden="true"></ion-icon>
                    </a>
                @endauth
                <div class="hh-socialite-moment-topbrand">HNT.rocks Moments</div>
            </div>
        </div>

        <section class="hh-socialite-moment-stage {{ $isLandscape ? 'is-landscape' : ($aspectRatioLabel === '1:1' ? 'is-square' : '') }}" aria-label="{{ __('ui.moment_reel_aria') }}">
            <video class="hh-socialite-moment-video {{ $isContained ? 'is-contained' : '' }}" autoplay muted loop playsinline webkit-playsinline preload="auto" disablepictureinpicture controlslist="nodownload noplaybackrate noremoteplayback" @if ($videoPoster) poster="{{ $videoPoster }}" @endif data-socialite-moment-video>
                <source src="{{ $videoUrl }}" type="{{ $videoMime }}">
                {{ __('ui.moment_video_not_supported') }}
            </video>

            <div class="hh-socialite-moment-vignette" aria-hidden="true"></div>

            <div class="hh-socialite-moment-copy">
                <a class="hh-socialite-moment-author" href="{{ route('profile.public', $moment->user) }}">
                    <img src="{{ $moment->user->avatarUrl() }}" alt="">
                    <span>{{ $authorHandle }}</span>
                </a>
                <p class="hh-socialite-moment-caption">{!! \App\Support\Hashtag::renderText($caption) !!}</p>
                <p class="hh-socialite-moment-description" data-socialite-moment-description @if (! $moment->description) hidden @endif>{!! \App\Support\Hashtag::renderText(\Illuminate\Support\Str::limit($moment->description ?? '', 150)) !!}</p>
            </div>

            <div class="hh-socialite-moment-actions" aria-label="{{ __('ui.moment_actions') }}">
                <form class="hh-socialite-moment-action-wrap" method="post" action="{{ route('moments.reactions.toggle', $moment) }}">
                    @csrf
                    <button class="hh-socialite-moment-action {{ $isLiked ? 'is-active' : '' }}" type="submit" aria-label="{{ $isLiked ? __('ui.moment_unlike') : __('ui.moment_like') }}">
                        <ion-icon name="thumbs-up-outline" aria-hidden="true"></ion-icon>
                    </button>
                    <span>{{ $moment->likes_count }}</span>
                </form>

                <button class="hh-socialite-moment-action-wrap" type="button" data-socialite-moment-comments-open aria-label="{{ __('ui.moment_comments') }}">
                    <span class="hh-socialite-moment-action"><ion-icon name="chatbubble-outline" aria-hidden="true"></ion-icon></span>
                    <span>{{ $moment->comments_count }}</span>
                </button>

                <form class="hh-socialite-moment-action-wrap" method="post" action="{{ route('moments.bookmarks.toggle', $moment) }}">
                    @csrf
                    <button class="hh-socialite-moment-action {{ $isBookmarked ? 'is-active' : '' }}" type="submit" aria-label="{{ $isBookmarked ? __('ui.moment_unsave') : __('ui.moment_save') }}">
                        <ion-icon name="bookmark-outline" aria-hidden="true"></ion-icon>
                    </button>
                    <span>{{ $moment->bookmarks_count }}</span>
                </form>

                <button class="hh-socialite-moment-action-wrap" type="button" data-socialite-moment-share aria-label="{{ __('ui.share') }}">
                    <span class="hh-socialite-moment-action"><ion-icon name="arrow-redo-outline" aria-hidden="true"></ion-icon></span>
                    <span>{{ __('ui.share') }}</span>
                </button>

                <button class="hh-socialite-moment-action-wrap" type="button" data-socialite-moment-more-open aria-label="{{ __('ui.more_options') }}">
                    <span class="hh-socialite-moment-action"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg></span>
                    <span>Mehr</span>
                </button>
            </div>
        </section>

        <nav class="hh-socialite-moment-side-nav" aria-label="{{ __('ui.moment_reel_navigation') }}">
            @if (! empty($previousMoment))
                <a class="hh-socialite-moment-nav-button" href="{{ route('moments.show', $previousMoment) }}" aria-label="{{ __('ui.moment_previous') }}">
                    <ion-icon name="chevron-up-outline" aria-hidden="true"></ion-icon>
                </a>
            @else
                <span class="hh-socialite-moment-nav-button is-disabled"><ion-icon name="chevron-up-outline" aria-hidden="true"></ion-icon></span>
            @endif

            @if (! empty($nextMoment))
                <a class="hh-socialite-moment-nav-button" href="{{ route('moments.show', $nextMoment) }}" aria-label="{{ __('ui.moment_next') }}">
                    <ion-icon name="chevron-down-outline" aria-hidden="true"></ion-icon>
                </a>
            @else
                <span class="hh-socialite-moment-nav-button is-disabled"><ion-icon name="chevron-down-outline" aria-hidden="true"></ion-icon></span>
            @endif
        </nav>
    </main>

    <div class="hh-socialite-moment-comments-backdrop" data-socialite-moment-comments-backdrop data-socialite-moment-comments-close></div>
    <aside class="hh-socialite-moment-comments" data-socialite-moment-comments aria-hidden="true">
        <header class="hh-socialite-moment-comments-head">
            <div>
                <h2>{{ __('ui.moment_comments') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ trans_choice('ui.comment_count', (int) $moment->comments_count, ['count' => (int) $moment->comments_count]) }}</p>
            </div>
            <button class="hh-socialite-moment-close" type="button" data-socialite-moment-comments-close aria-label="{{ __('ui.close') }}">
                <ion-icon name="close-outline" aria-hidden="true"></ion-icon>
            </button>
        </header>

        <div class="hh-socialite-moment-comment-list">
            @forelse ($moment->comments as $comment)
                <article class="hh-socialite-moment-comment">
                    <img src="{{ $comment->user->avatarUrl() }}" alt="">
                    <div class="hh-socialite-moment-comment-body">
                        <header>
                            <strong>{{ $comment->user->username }}</strong>
                            <small>{{ $comment->created_at->diffForHumans() }}</small>
                        </header>
                        <p>{{ $comment->body }}</p>
                    </div>
                </article>
            @empty
                <p class="text-sm text-slate-500">{{ __('ui.moment_no_comments') }}</p>
            @endforelse
        </div>

        @auth
            <form class="hh-socialite-moment-comment-form" method="post" action="{{ route('moments.comments.store', $moment) }}">
                @csrf
                <textarea name="body" rows="1" placeholder="{{ __('ui.moment_comment_placeholder') }}" required>{{ old('body') }}</textarea>
                <button type="submit">{{ __('ui.moment_comment_submit') }}</button>
            </form>
        @else
            <div class="p-4 border-t border-slate-200 text-sm text-slate-500">
                {{ __('ui.login_to_comment') }}
            </div>
        @endauth
    </aside>

    <div class="hh-socialite-moment-more-backdrop" data-socialite-moment-more-backdrop data-socialite-moment-more-close></div>
    <aside class="hh-socialite-moment-more-sheet" data-socialite-moment-more aria-hidden="true">
        <header class="hh-socialite-moment-more-head">
            <h2>{{ __('ui.moment_options') }}</h2>
            <button class="hh-socialite-moment-more-close" type="button" data-socialite-moment-more-close aria-label="{{ __('ui.close') }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
            </button>
        </header>
        <div class="hh-socialite-moment-more-content">
            <a class="hh-socialite-moment-more-action" href="{{ $videoUrl }}" download>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                {{ __('ui.download') }}
            </a>

            @if ($canManageMoment)
                <button class="hh-socialite-moment-more-action" type="button" data-socialite-moment-edit-toggle>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
                    {{ __('ui.edit_description') }}
                </button>

                <form class="hh-socialite-moment-edit-form" method="post" action="{{ route('moments.update', $moment) }}" data-socialite-moment-edit-form>
                    @csrf
                    @method('PATCH')
                    <label for="moment-description-{{ $moment->id }}">{{ __('ui.description') }}</label>
                    <textarea id="moment-description-{{ $moment->id }}" name="description" maxlength="2000" placeholder="{{ __('ui.description_placeholder') }}">{{ old('description', $moment->description) }}</textarea>
                    <button type="submit" data-saving-label="{{ __('ui.saving') }}">{{ __('ui.save') }}</button>
                </form>

                <form method="post" action="{{ route('moments.destroy', $moment) }}" onsubmit="return confirm('{{ __('ui.moment_delete_confirm') }}');">
                    @csrf
                    @method('DELETE')
                    <button class="hh-socialite-moment-more-action is-danger" type="submit">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><path d="m19 6-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg>
                        {{ __('ui.delete') }}
                    </button>
                </form>
            @endif
        </div>
    </aside>

    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
    <script src="{{ asset('assets/socialite/js/hnt-socialite-moments-viewer.js') }}?v=478" defer></script>
</body>
</html>
