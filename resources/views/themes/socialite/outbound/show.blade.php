@php
    $previousUrl = url()->previous();
    $currentUrl = url()->current();
    $previousHost = parse_url($previousUrl, PHP_URL_HOST);
    $currentHost = request()->getHost();
    $samePage = rtrim($previousUrl, '/') === rtrim($currentUrl, '/');
    $backUrl = $previousHost && strcasecmp((string) $previousHost, (string) $currentHost) === 0 && ! $samePage
        ? $previousUrl
        : route('feed.index');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,follow">
    <meta name="description" content="{{ __('ui.outbound_meta_description') }}">
    <title>{{ __('ui.outbound_meta_title') }}</title>
    <style>
        :root {
            color-scheme: dark;
            --hnt-bg: #09090a;
            --hnt-paper: #10100f;
            --hnt-paper-soft: #141411;
            --hnt-text: #e5e0d4;
            --hnt-muted: #9b9487;
            --hnt-primary: #a9b673;
            --hnt-primary-strong: #bbc68c;
            --hnt-primary-ink: #11120d;
            --hnt-line: rgba(206, 193, 165, .12);
            --hnt-line-strong: rgba(169, 182, 115, .28);
            --hnt-shadow: 0 28px 80px rgba(0, 0, 0, .42);
        }

        * { box-sizing: border-box; }

        html, body { min-height: 100%; }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at 50% 12%, rgba(169, 182, 115, .08), transparent 28rem),
                radial-gradient(circle at 12% 85%, rgba(146, 118, 83, .05), transparent 25rem),
                var(--hnt-bg);
            color: var(--hnt-text);
            font-family: Urbanist, Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; }

        .out-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .out-card {
            position: relative;
            width: min(680px, 100%);
            border: 1px solid var(--hnt-line);
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(18, 18, 16, .98), rgba(13, 13, 12, .98));
            box-shadow: var(--hnt-shadow);
            overflow: hidden;
        }

        .out-card::before {
            content: "";
            position: absolute;
            inset: 0 0 auto;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(187, 198, 140, .35), transparent);
            pointer-events: none;
        }

        .out-inner { padding: 46px 48px 44px; }

        .out-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 44px;
        }

        .out-logo img {
            display: block;
            width: 190px;
            max-width: 72%;
            height: auto;
        }

        .out-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            color: var(--hnt-primary-strong);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .13em;
            text-transform: uppercase;
        }

        .out-kicker::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: var(--hnt-primary);
            box-shadow: 0 0 16px rgba(169, 182, 115, .45);
        }

        h1 {
            margin: 0;
            font-size: clamp(32px, 5vw, 46px);
            line-height: 1.08;
            letter-spacing: -.035em;
            font-weight: 650;
        }

        .out-intro {
            margin: 14px 0 0;
            color: var(--hnt-muted);
            font-size: 15px;
            line-height: 1.65;
        }

        .out-target {
            margin-top: 30px;
            padding: 20px;
            border: 1px solid var(--hnt-line-strong);
            border-radius: 18px;
            background: rgba(169, 182, 115, .035);
        }

        .out-target-label,
        .out-info-title {
            display: block;
            color: var(--hnt-muted);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .out-domain {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            font-size: 22px;
            line-height: 1.25;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .out-domain svg {
            width: 20px;
            height: 20px;
            flex: 0 0 auto;
            color: var(--hnt-primary);
        }

        .out-link-title {
            display: block;
            margin-top: 9px;
            color: var(--hnt-text);
            font-size: 13px;
            line-height: 1.5;
            opacity: .84;
        }

        .out-link-description {
            margin: 12px 0 0;
            color: var(--hnt-muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .out-info {
            margin-top: 16px;
            padding: 18px 20px;
            border: 1px solid var(--hnt-line);
            border-radius: 18px;
            background: rgba(255, 255, 255, .015);
        }

        .out-info p {
            margin: 8px 0 0;
            color: var(--hnt-muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .out-points {
            display: grid;
            gap: 8px;
            margin: 14px 0 0;
            padding: 0;
            list-style: none;
        }

        .out-points li {
            position: relative;
            padding-left: 18px;
            color: var(--hnt-muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .out-points li::before {
            content: "";
            position: absolute;
            left: 2px;
            top: .62em;
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: var(--hnt-primary);
        }

        .out-actions {
            display: grid;
            grid-template-columns: minmax(0, .85fr) minmax(0, 1.15fr);
            gap: 12px;
            margin-top: 28px;
        }

        .out-button {
            min-height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            border-radius: 18px;
            border: 1px solid transparent;
            padding: 12px 18px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 750;
            transition: transform .16s ease, border-color .16s ease, background .16s ease;
        }

        .out-button:hover { transform: translateY(-1px); }

        .out-button svg { width: 18px; height: 18px; }

        .out-back {
            border-color: var(--hnt-line);
            background: rgba(255, 255, 255, .025);
            color: var(--hnt-text);
        }

        .out-back:hover {
            border-color: rgba(206, 193, 165, .22);
            background: rgba(255, 255, 255, .045);
        }

        .out-continue {
            background: var(--hnt-primary);
            color: var(--hnt-primary-ink);
        }

        .out-continue:hover { background: var(--hnt-primary-strong); }

        .out-note {
            margin: 18px 0 0;
            text-align: center;
            color: rgba(155, 148, 135, .72);
            font-size: 11px;
            line-height: 1.5;
        }

        @media (max-width: 640px) {
            .out-shell { padding: 14px; }
            .out-inner { padding: 34px 24px 28px; }
            .out-logo { margin-bottom: 34px; }
            .out-actions { grid-template-columns: 1fr; }
            .out-domain { font-size: 19px; }
        }
    </style>
</head>
<body>
    <main class="out-shell">
        <section class="out-card" aria-labelledby="outbound-title">
            <div class="out-inner">
                <a class="out-logo" href="{{ route('feed.index') }}" aria-label="HNT.ROCKS">
                    <img src="/app/images/brand/hnt-brand-logo.svg" alt="HNT.ROCKS">
                </a>

                <span class="out-kicker">{{ __('ui.outbound_kicker') }}</span>
                <h1 id="outbound-title">{{ __('ui.outbound_title') }}</h1>
                <p class="out-intro">{{ __('ui.outbound_intro') }}</p>

                <div class="out-target">
                    <span class="out-target-label">{{ __('ui.outbound_destination') }}</span>
                    <div class="out-domain">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="M3 12h18"></path>
                            <path d="M12 3a14 14 0 0 1 0 18"></path>
                            <path d="M12 3a14 14 0 0 0 0 18"></path>
                        </svg>
                        <span>{{ $link->safeDomain() }}</span>
                    </div>
                    <span class="out-link-title">{{ $link->title }}</span>
                    @if($link->description)
                        <p class="out-link-description">{{ $link->description }}</p>
                    @endif
                </div>

                <div class="out-info">
                    <span class="out-info-title">{{ __('ui.outbound_privacy_title') }}</span>
                    <p>{{ __('ui.outbound_privacy_text') }}</p>
                    <ul class="out-points">
                        <li>{{ __('ui.outbound_privacy_point_1') }}</li>
                        <li>{{ __('ui.outbound_privacy_point_2') }}</li>
                        <li>{{ __('ui.outbound_privacy_point_3') }}</li>
                    </ul>
                </div>

                <div class="out-actions">
                    <a href="{{ $backUrl }}" class="out-button out-back">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m15 18-6-6 6-6"></path>
                        </svg>
                        <span>{{ __('ui.outbound_back') }}</span>
                    </a>
                    <a href="{{ $link->continueUrl() }}" class="out-button out-continue" rel="nofollow noopener noreferrer">
                        <span>{{ __('ui.outbound_continue') }}</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14"></path>
                            <path d="m13 6 6 6-6 6"></path>
                        </svg>
                    </a>
                </div>

                <p class="out-note">{{ __('ui.outbound_warning_text') }}</p>
            </div>
        </section>
    </main>
</body>
</html>
