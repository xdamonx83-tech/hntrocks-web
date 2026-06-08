<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ __('ui.moments') }} · HNT.rocks</title>
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

        .hh-socialite-moments-empty {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: radial-gradient(circle at 50% 12%, rgba(42, 44, 48, .92), rgba(0, 0, 0, 1) 54%), #000;
        }

        .hh-socialite-moments-empty-card {
            width: min(520px, 100%);
            border-radius: 28px;
            border: 1px solid rgba(255, 255, 255, .12);
            background: rgba(18, 18, 20, .72);
            box-shadow: 0 28px 80px rgba(0, 0, 0, .48);
            backdrop-filter: blur(18px);
            padding: 34px;
            text-align: center;
        }

        .hh-socialite-moments-empty-card h1 {
            margin: 0 0 10px;
            font-size: clamp(30px, 5vw, 46px);
            line-height: 1;
            font-weight: 900;
        }

        .hh-socialite-moments-empty-card p {
            margin: 0 auto 24px;
            color: rgba(255,255,255,.72);
            max-width: 360px;
            line-height: 1.55;
        }

        .hh-socialite-moments-empty-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
        }

        .hh-socialite-moments-empty-actions a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 46px;
            border-radius: 999px;
            padding: 0 18px;
            font-weight: 800;
            text-decoration: none;
        }

        .hh-socialite-moments-empty-primary {
            background: #2563eb;
            color: #fff;
        }

        .hh-socialite-moments-empty-secondary {
            background: rgba(255,255,255,.10);
            color: #fff;
            border: 1px solid rgba(255,255,255,.12);
        }
    </style>
</head>
<body>
    <main class="hh-socialite-moments-empty">
        <section class="hh-socialite-moments-empty-card" aria-label="HNT.rocks Moments">
            <h1>{{ __('ui.moments') }}</h1>
            <p>{{ __('ui.moments_empty_text') }}</p>
            <div class="hh-socialite-moments-empty-actions">
                @auth
                    <a class="hh-socialite-moments-empty-primary" href="{{ route('moments.create') }}">
                        <ion-icon name="add-outline" aria-hidden="true"></ion-icon>
                        {{ __('ui.moment_add') }}
                    </a>
                @endauth
                <a class="hh-socialite-moments-empty-secondary" href="{{ route('feed.index') }}">
                    <ion-icon name="arrow-back-outline" aria-hidden="true"></ion-icon>
                    {{ __('ui.back_to_feed') }}
                </a>
            </div>
        </section>
    </main>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>
