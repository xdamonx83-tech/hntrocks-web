<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>HNT.rocks — Kurze Wartung</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #141412;
            --surface: #20201e;
            --surface-2: #292925;
            --border: #3a382f;
            --text: #f2e8d8;
            --muted: #a79c8e;
            --accent: #d6a84f;
        }

        * { box-sizing: border-box; }

        html, body {
            min-height: 100%;
            margin: 0;
        }

        body {
            display: grid;
            place-items: center;
            padding: 24px;
            overflow: hidden;
            background:
                radial-gradient(circle at 85% 12%, rgba(214, 168, 79, .17), transparent 34%),
                radial-gradient(circle at 12% 88%, rgba(255, 255, 255, .04), transparent 38%),
                var(--bg);
            color: var(--text);
            font-family: Inter, Urbanist, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .shell {
            width: min(760px, 100%);
            position: relative;
        }

        .eyebrow {
            margin: 0 0 14px;
            color: var(--accent);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .card {
            position: relative;
            overflow: hidden;
            padding: clamp(30px, 7vw, 64px);
            border: 1px solid var(--border);
            border-radius: 22px;
            background: linear-gradient(145deg, rgba(32, 32, 30, .98), rgba(24, 24, 22, .98));
        }

        .card::after {
            content: "";
            position: absolute;
            width: 230px;
            height: 230px;
            top: -125px;
            right: -95px;
            border: 1px solid rgba(214, 168, 79, .28);
            border-radius: 50%;
        }

        h1 {
            max-width: 620px;
            margin: 0;
            font-size: clamp(38px, 8vw, 72px);
            font-weight: 600;
            line-height: .98;
            letter-spacing: -.045em;
        }

        .lead {
            max-width: 590px;
            margin: 26px 0 0;
            color: var(--muted);
            font-size: clamp(16px, 2.3vw, 20px);
            line-height: 1.6;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 32px;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: var(--surface-2);
            color: var(--text);
            font-size: 14px;
        }

        .dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 0 6px rgba(214, 168, 79, .12);
            animation: pulse 1.8s ease-in-out infinite;
        }

        .foot {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            margin-top: 18px;
            padding: 0 4px;
            color: #756e63;
            font-size: 12px;
        }

        @keyframes pulse {
            50% { transform: scale(.72); opacity: .65; }
        }

        @media (max-width: 560px) {
            body { padding: 14px; }
            .card { border-radius: 16px; }
            .foot { flex-direction: column; gap: 5px; }
        }
    </style>
</head>
<body>
<main class="shell">
    <p class="eyebrow">HNT.rocks · System Update</p>
    <section class="card" aria-labelledby="maintenance-title">
        <h1 id="maintenance-title">Wir sind gleich wieder da.</h1>
        <p class="lead">
            HNT.rocks bekommt gerade ein technisches Update. Deine Inhalte bleiben erhalten.
            Bitte versuche es in wenigen Minuten erneut.
        </p>
        <div class="status">
            <span class="dot" aria-hidden="true"></span>
            Wartung läuft
        </div>
    </section>
    <footer class="foot">
        <span>HNT.rocks</span>
        <span>Danke für deine Geduld.</span>
    </footer>
</main>
</body>
</html>
