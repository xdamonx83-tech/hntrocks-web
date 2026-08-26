<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="referrer" content="no-referrer">
    <title>HNT.ROCKS – Zur App zurückkehren</title>
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: #090a0a;
            color: #f4f4f2;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .card {
            width: min(100%, 460px);
            padding: 32px;
            border: 1px solid rgba(255,255,255,.09);
            border-radius: 18px;
            background: rgba(16,17,17,.94);
            box-shadow: 0 24px 70px rgba(0,0,0,.42);
        }
        .brand { margin: 0 0 18px; font-size: 13px; letter-spacing: .16em; color: #d5ac45; font-weight: 700; }
        h1 { margin: 0 0 12px; font-size: 25px; line-height: 1.18; font-weight: 650; }
        p { margin: 0; color: #aaa; font-size: 14px; line-height: 1.55; }
        .actions { display: grid; gap: 10px; margin-top: 26px; }
        .button {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 18px;
            border: 1px solid rgba(213,172,69,.48);
            border-radius: 10px;
            color: #fff;
            background: #111212;
            text-decoration: none;
            font-size: 14px;
            font-weight: 650;
        }
        .secondary { color: #8f8f8b; text-align: center; text-decoration: none; font-size: 12px; padding: 8px; }
        .meta { margin-top: 18px; color: #696a67; font-size: 11px; text-align: center; }
    </style>
</head>
<body>
<main class="card">
    <div class="brand">HNT.ROCKS</div>
    <h1>{{ $success ? 'Anmeldung abgeschlossen' : 'Zur HNT.ROCKS App zurückkehren' }}</h1>
    <p>
        {{ $success
            ? 'Deine Anmeldung wurde abgeschlossen. Kehre jetzt mit einem Tippen sicher zur App zurück.'
            : 'Kehre zur HNT.ROCKS App zurück, um den Social-Login dort abzuschließen.' }}
    </p>
    <div class="actions">
        <a class="button" href="{{ $intentUrl }}" rel="noreferrer">Zur HNT.ROCKS App zurückkehren</a>
        <a class="secondary" href="{{ $fallbackUrl }}" rel="noreferrer">Zur Web-Anmeldung</a>
    </div>
    @if ($provider)
        <div class="meta">Provider: {{ ucfirst($provider) }}</div>
    @endif
</main>
</body>
</html>
