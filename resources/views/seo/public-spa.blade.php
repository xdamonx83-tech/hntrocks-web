<!DOCTYPE html>
<html lang="{{ app()->getLocale() ?: 'de' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#0d1114">
    <title>{{ $page['title'] }} | HNT.ROCKS</title>
    <meta name="description" content="{{ $page['description'] }}">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <link rel="canonical" href="{{ $page['canonical'] }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="HNT.ROCKS">
    <meta property="og:title" content="{{ $page['title'] }} | HNT.ROCKS">
    <meta property="og:description" content="{{ $page['description'] }}">
    <meta property="og:url" content="{{ $page['canonical'] }}">
    <meta name="twitter:card" content="summary">
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@400;500;600;700;800&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {color-scheme:dark;--bg:#0d1114;--panel:#151b20;--border:#29353c;--text:#e4e9ea;--muted:#9ba9af;--accent:#91ac84}
        *{box-sizing:border-box}
        body{margin:0;background:var(--bg);color:var(--text);font-family:"Exo 2",sans-serif;font-size:15px;line-height:1.6}
        body:before{content:"";position:fixed;inset:0;z-index:-1;pointer-events:none;background:radial-gradient(ellipse 100% 70% at 80% -30%,#34483724,transparent 65%)}
        a{color:inherit;text-decoration:none}
        a:hover{color:#c1deb6}
        .skip{position:absolute;top:-100px;left:12px;padding:12px;background:var(--panel);z-index:10}
        .skip:focus{top:8px}
        .top{border-bottom:1px solid #283137;background:#0d1114dd}
        .top-inner{max-width:1160px;margin:auto;display:flex;align-items:center;justify-content:space-between;gap:18px;padding:15px 26px}
        .logo{font-size:21px;line-height:1;letter-spacing:.025em;font-weight:800}
        .logo span{color:var(--accent)}
        nav{display:flex;gap:23px;align-items:center;font-family:Rajdhani,sans-serif;font-weight:600;font-size:16px}
        .login{border:1px solid #536455;padding:7px 13px;border-radius:5px}
        main{max-width:1160px;margin:0 auto;padding:35px 26px 90px}
        .crumb{font:600 14px Rajdhani,sans-serif;letter-spacing:.065em;text-transform:uppercase;color:var(--accent)}
        h1{font-size:clamp(27px,4vw,43px);letter-spacing:-.025em;line-height:1.2;margin:21px 0 17px}
        h2{font-size:21px;margin:0 0 15px}
        .intro{font-size:17px;color:#c2ced0;max-width:820px;margin-bottom:25px;white-space:pre-line}
        .hero{border:1px solid var(--border);border-radius:10px;background:linear-gradient(110deg,#192222,#151a20);padding:30px 33px;margin-bottom:20px}
        .eyebrow{font:700 14px Rajdhani,sans-serif;color:var(--accent);letter-spacing:.12em;text-transform:uppercase}
        .actions{display:flex;gap:11px;flex-wrap:wrap;margin-top:25px}
        .btn{display:inline-flex;align-items:center;justify-content:center;min-height:39px;background:#8ba881;color:#0d1510;font-weight:700;border-radius:5px;padding:9px 17px}
        .btn:hover{background:#bdd4b2;color:#0d1510}
        .btn-alt{background:#1e282b;color:#d7e1e1;border:1px solid #38464d}
        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:13px;margin-top:20px}
        .panel{background:var(--panel);border:1px solid var(--border);border-radius:8px;padding:20px 21px}
        .panel h2{margin-top:0}
        .panel p{white-space:pre-line;overflow-wrap:anywhere;margin:0;color:#c3ced2}
        .cards a{display:block}
        .cards .panel{height:100%;transition:border-color .15s}
        .cards a:hover .panel{border-color:#75966e}
        .name{font-size:19px;font-weight:700}
        .small{font:600 14px Rajdhani,sans-serif;color:#a7b6bb;letter-spacing:.05em}
        .stats{display:flex;flex-wrap:wrap;gap:10px;margin-top:13px}
        .stat{border:1px solid var(--border);border-radius:5px;color:#afbbbf;padding:7px 12px;font-size:13px}
        .detail{display:grid;grid-template-columns:minmax(0,1fr) 260px;gap:17px}
        .section{margin-bottom:16px}
        footer{border-top:1px solid var(--border);margin-top:54px;padding-top:19px;color:var(--muted);font:500 14px Rajdhani,sans-serif}
        @media(max-width:710px){.top-inner{padding:14px 17px}nav{gap:10px;font-size:14px}nav .low{display:none}main{padding:25px 16px 65px}.hero{padding:23px 18px}.detail{grid-template-columns:1fr}}
    </style>
</head>
<body>
<a class="skip" href="#content">Zum Inhalt</a>
<header class="top">
    <div class="top-inner">
        <a class="logo" href="{{ url('/') }}" aria-label="HNT.ROCKS Startseite">HNT<span>.</span>ROCKS</a>
        <nav aria-label="Hauptnavigation">
            <a href="{{ url('/maps') }}">Maps</a>
            <a class="low" href="{{ url('/news') }}">News</a>
            <a href="{{ url('/loadout-challenges') }}">Challenges</a>
            <a class="login" href="{{ url('/login') }}">Anmelden</a>
        </nav>
    </div>
</header>
<main id="content">
    <div class="crumb"><a href="{{ url('/') }}">HNT.ROCKS</a> / <a href="{{ $page['parent_url'] }}">{{ $page['parent'] }}</a> @if($page['parent'] !== $page['name']) / {{ $page['name'] }} @endif</div>
    <section class="hero">
        <span class="eyebrow">{{ $page['kicker'] }}</span>
        <h1>{{ $page['name'] }}</h1>
        <p class="intro">{{ $page['description'] }}</p>
        @if(!empty($page['facts']))
            <div class="stats">
                @foreach($page['facts'] as $fact)
                    <span class="stat">{{ $fact }}</span>
                @endforeach
            </div>
        @endif
        <div class="actions">
            <a class="btn" href="{{ url('/login') }}">Anmelden und mitmachen</a>
            <a class="btn btn-alt" href="{{ $page['parent_url'] }}">{{ $page['parent'] }} ansehen</a>
        </div>
    </section>
    @if(!empty($page['sections']))
        <div class="detail">
            <div>
                @foreach($page['sections'] as $section)
                    @if(trim($section['body']) !== '')
                        <section class="panel section">
                            <h2>{{ $section['heading'] }}</h2>
                            <p>{{ $section['body'] }}</p>
                        </section>
                    @endif
                @endforeach
            </div>
            <aside>
                <section class="panel">
                    <h2>HNT.ROCKS Community</h2>
                    <p>Hunt: Showdown mit anderen Huntern erleben. Entdecke die interaktiven Karten und die Community.</p>
                    <div class="actions"><a class="btn btn-alt" href="{{ url('/maps') }}">Maps entdecken</a></div>
                </section>
            </aside>
        </div>
    @endif
    @if(!empty($page['links']))
        <section style="margin-top:29px">
            <h2>{{ $page['links_title'] ?? 'Weitere Community-Inhalte' }}</h2>
            <div class="grid cards">
                @foreach($page['links'] as $entry)
                    <a href="{{ $entry['url'] }}">
                        <div class="panel">
                            <div class="small">{{ $entry['kicker'] ?? 'HNT.ROCKS' }}</div>
                            <div class="name">{{ $entry['title'] }}</div>
                            @if(!empty($entry['summary']))<p>{{ $entry['summary'] }}</p>@endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
    <footer><a href="{{ url('/impressum') }}">Impressum</a> · <a href="{{ url('/datenschutz') }}">Datenschutz</a> · HNT.ROCKS – Hunt: Showdown Community</footer>
</main>
<script type="application/ld+json">{!! json_encode($page['schema'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
</body>
</html>
