@php
    $isEnglish = app()->getLocale() === 'en';
    $copy = $isEnglish ? [
        'meta_title' => 'HNT.ROCKS — Hunt: Showdown Community',
        'meta_description' => 'Find Hunters, share posts and Moments, play Cups and plan your matches with HNT.ROCKS.',
        'start' => 'Start', 'community' => 'Community', 'lfg' => 'LFG', 'moments' => 'Moments', 'cups' => 'Cups', 'teams' => 'Teams', 'more' => 'More',
        'login' => 'Log in', 'register' => 'Register', 'kicker' => 'HNT.ROCKS COMMUNITY',
        'title' => 'Welcome to the Bayou.',
        'intro' => 'Your Hunt: Showdown community for posts, Moments, LFG, Ready Lobbies, Teams, Cups, Guides and interactive Huntmaps.',
        'register_free' => 'Register for free', 'discover' => 'Discover the community',
        'members' => 'Members', 'posts' => 'Posts', 'posts_week' => 'Posts this week', 'active_today' => 'active today',
        'community_title' => 'Hunt together.', 'community_text' => 'Hunters recently active on HNT.ROCKS.',
        'progress_title' => 'Your activity matters.', 'progress_text' => 'Level, badges, Rocks and quests grow with your community activity.',
        'all_one_place' => 'Everything in one place.', 'get_started' => 'Get started',
        'feed_title' => 'Community Feed', 'feed_text' => 'Posts, polls, loadouts and discussions.',
        'lfg_title' => 'LFG & Ready Lobbies', 'lfg_text' => 'Find matching Hunters and start immediately.',
        'teams_title' => 'Teams', 'teams_text' => 'Team profiles, recruiting, sessions and progression.',
        'messages_title' => 'Messages', 'messages_text' => 'Direct messages and live conversations.',
        'activity_title' => 'What is happening now.', 'for_you' => 'For you', 'following' => 'Following',
        'no_post' => 'The first public community post is still waiting.', 'join_community' => 'Join the community',
        'open_lfgs' => 'open LFGs', 'active_teams' => 'active Teams', 'community_cups' => 'Community Cups', 'huntmaps' => 'Huntmaps',
        'status_title' => 'Community status', 'explore' => 'Explore', 'directly_in' => 'Jump right in.',
        'moments_title' => 'The best seconds from the Bayou.', 'all_moments' => 'All Moments', 'no_moments' => 'No public Moments yet.',
        'cup_title' => 'Community Cup', 'registration_open' => 'Current Cup', 'view_cup' => 'View Cup', 'no_cup' => 'No public Cup is currently available.',
        'maps_title' => 'Four maps. One community knowledge base.', 'open_maps' => 'Open maps',
        'cta_title' => 'Your team is already waiting.', 'cta_text' => 'Register for free and start with Feed, LFG, Ready Lobbies, Moments and Cups.',
        'footer' => 'HNT.ROCKS · Fan project for the Hunt: Showdown community',
        'imprint' => 'Imprint', 'privacy' => 'Privacy', 'terms' => 'Terms', 'netiquette' => 'Netiquette',
        'online' => 'online now', 'level' => 'Level', 'badges' => 'Badges', 'rocks' => 'Rocks', 'live' => 'Live',
        'platform' => 'Platform', 'region' => 'Region', 'team' => 'Team', 'start_time' => 'Start', 'join' => 'Join',
    ] : [
        'meta_title' => 'HNT.ROCKS — Hunt: Showdown Community',
        'meta_description' => 'Finde Hunter, teile Beiträge und Moments, spiele Cups und plane deine Runden mit HNT.ROCKS.',
        'start' => 'Start', 'community' => 'Community', 'lfg' => 'LFG', 'moments' => 'Moments', 'cups' => 'Cups', 'teams' => 'Teams', 'more' => 'Mehr',
        'login' => 'Anmelden', 'register' => 'Registrieren', 'kicker' => 'HNT.ROCKS COMMUNITY',
        'title' => 'Willkommen im Bayou.',
        'intro' => 'Deine Hunt: Showdown Community für Beiträge, Moments, LFG, Ready Lobbys, Teams, Cups, Guides und interaktive Huntmaps.',
        'register_free' => 'Kostenlos registrieren', 'discover' => 'Community entdecken',
        'members' => 'Mitglieder', 'posts' => 'Beiträge', 'posts_week' => 'Beiträge diese Woche', 'active_today' => 'heute aktiv',
        'community_title' => 'Gemeinsam jagen.', 'community_text' => 'Hunter, die zuletzt auf HNT.ROCKS aktiv waren.',
        'progress_title' => 'Deine Aktivität zählt.', 'progress_text' => 'Level, Badges, Rocks und Aufgaben wachsen mit deiner Community-Aktivität.',
        'all_one_place' => 'Alles an einem Ort.', 'get_started' => 'Jetzt starten',
        'feed_title' => 'Community Feed', 'feed_text' => 'Beiträge, Umfragen, Loadouts und Diskussionen.',
        'lfg_title' => 'LFG & Ready Lobbys', 'lfg_text' => 'Passende Hunter finden und direkt losspielen.',
        'teams_title' => 'Teams', 'teams_text' => 'Teamprofile, Recruiting, Sessions und Fortschritt.',
        'messages_title' => 'Nachrichten', 'messages_text' => 'Direktnachrichten und Live-Unterhaltungen.',
        'activity_title' => 'Was gerade passiert.', 'for_you' => 'Für dich', 'following' => 'Folge ich',
        'no_post' => 'Der erste öffentliche Community-Beitrag wartet noch.', 'join_community' => 'Community beitreten',
        'open_lfgs' => 'offene LFGs', 'active_teams' => 'aktive Teams', 'community_cups' => 'Community Cups', 'huntmaps' => 'Huntmaps',
        'status_title' => 'Community Status', 'explore' => 'Entdecken', 'directly_in' => 'Direkt rein.',
        'moments_title' => 'Die besten Sekunden aus dem Bayou.', 'all_moments' => 'Alle Moments', 'no_moments' => 'Noch keine öffentlichen Moments vorhanden.',
        'cup_title' => 'Community Cup', 'registration_open' => 'Aktueller Cup', 'view_cup' => 'Cup ansehen', 'no_cup' => 'Aktuell ist kein öffentlicher Cup verfügbar.',
        'maps_title' => 'Vier Karten. Ein Community-Wissen.', 'open_maps' => 'Karten öffnen',
        'cta_title' => 'Dein Team wartet schon.', 'cta_text' => 'Kostenlos registrieren und direkt mit Feed, LFG, Ready Lobbys, Moments und Cups starten.',
        'footer' => 'HNT.ROCKS · Fanprojekt für die Hunt: Showdown Community',
        'imprint' => 'Impressum', 'privacy' => 'Datenschutz', 'terms' => 'Nutzungsbedingungen', 'netiquette' => 'Netiquette',
        'online' => 'jetzt online', 'level' => 'Level', 'badges' => 'Badges', 'rocks' => 'Rocks', 'live' => 'Live',
        'platform' => 'Plattform', 'region' => 'Region', 'team' => 'Team', 'start_time' => 'Start', 'join' => 'Mitmachen',
    ];

    $nf = static fn (int $value): string => number_format($value, 0, ',', '.');
    $members = (int) ($stats['members'] ?? 0);
    $posts = (int) ($stats['posts'] ?? 0);
    $postsWeek = (int) ($stats['posts_week'] ?? 0);
    $momentsCount = (int) ($stats['moments'] ?? 0);
    $cupsCount = (int) ($stats['cups'] ?? 0);
    $activeToday = (int) ($stats['active_today'] ?? 0);
    $onlineNow = (int) ($stats['online_now'] ?? 0);
    $openLfgs = (int) ($stats['open_lfgs'] ?? 0);
    $activeTeams = (int) ($stats['active_teams'] ?? 0);
    $postAuthor = $featuredPost?->user;
    $cupCover = $featuredCup?->cover_path
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($featuredCup->cover_path)
        : asset('assets/vikinger/img/cover/01.jpg');
@endphp
<!DOCTYPE html>
<html lang="{{ $isEnglish ? 'en' : 'de' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="{{ $copy['meta_description'] }}">
<meta name="robots" content="index,follow">
<link rel="canonical" href="{{ route('home') }}">
<title>{{ $copy['meta_title'] }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/common.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/global-radius-10.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/global-radius-10.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/landing/landing.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/landing/landing.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/landing/landing-header-fix.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/landing/landing-header-fix.css')) ?: time() }}" rel="stylesheet">
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'url' => route('home'),
    'name' => $copy['meta_title'],
    'description' => $copy['meta_description'],
    'about' => ['@type' => 'VideoGame', 'name' => 'Hunt: Showdown'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
</head>
<body data-page="landing">
@include('themes.hnt_preview.partials.icons')
<main class="app-shell landing-shell">
<header class="landing-header">
<a class="landing-brand" href="{{ route('home') }}" aria-label="HNT.ROCKS">
<svg aria-hidden="true" class="landing-brand-mark" viewBox="0 0 44 34"><path d="M8.2 5.5c4.4-4.4 10.8-4.2 14.5.1-1 4.7-4.2 8-8.8 9.3-3.9-1.4-6.2-4.7-5.7-9.4Z"></path><path d="M22.1 8.1c5.9-.2 10.2 4 10.4 9.2-3.4 3.2-8 4-12.3 2.1-2-3.8-1.3-8.2 1.9-11.3Z"></path><path d="M14.3 18.3c3.5-3.5 8.5-3.8 12.2-.9.4 4.7-1.8 8.5-6.1 10.5-4-.7-6.6-4.1-6.1-9.6Z"></path><circle cx="18.8" cy="15.5" r="3.4"></circle></svg>
<span>HNT.ROCKS</span>
</a>
<nav class="landing-nav" aria-label="{{ $copy['community'] }}">
<a class="active" href="#start">{{ $copy['start'] }}</a><a href="#community">{{ $copy['community'] }}</a><a href="#lfg">{{ $copy['lfg'] }}</a><a href="#moments">{{ $copy['moments'] }}</a><a href="#cups">{{ $copy['cups'] }}</a><a href="#teams">{{ $copy['teams'] }}</a><a href="#more">{{ $copy['more'] }}</a>
</nav>
<div class="landing-header-actions">
<a class="landing-login" href="{{ route('login') }}"><svg><use href="#i-user"></use></svg><span>{{ $copy['login'] }}</span></a>
<div class="landing-language" aria-label="Language"><a href="{{ route('locale.switch', ['locale' => 'de']) }}" class="{{ $isEnglish ? '' : 'active' }}">DE</a><a href="{{ route('locale.switch', ['locale' => 'en']) }}" class="{{ $isEnglish ? 'active' : '' }}">EN</a></div>
<a class="landing-register" href="{{ route('register') }}" aria-label="{{ $copy['register'] }}"><svg><use href="#i-plus"></use></svg></a>
</div>
</header>

<section class="landing-hero" id="start">
<div><span class="landing-kicker">{{ $copy['kicker'] }}</span><h1>{{ $copy['title'] }}</h1><p>{{ $copy['intro'] }}</p><div class="landing-actions"><a class="landing-button dark" href="{{ route('register') }}">{{ $copy['register_free'] }} <svg><use href="#i-arrow"></use></svg></a><a class="landing-button light" href="#community">{{ $copy['discover'] }}</a></div></div>
<div class="landing-hero-stats"><article><strong>{{ $nf($members) }}</strong><span>{{ $copy['members'] }}</span></article><article><strong>{{ $nf($posts) }}</strong><span>{{ $copy['posts'] }}</span></article><article><strong>{{ $nf($momentsCount) }}</strong><span>{{ $copy['moments'] }}</span></article></div>
</section>

<section class="landing-progress" aria-label="HNT.ROCKS">
<article class="dark"><span>{{ $copy['members'] }}</span><div><i style="width:{{ min(100, max(8, $members ? 72 : 8)) }}%"></i><strong>{{ $nf($members) }}</strong></div></article>
<article><span>{{ $copy['posts_week'] }}</span><div><i style="width:{{ min(100, max(8, $postsWeek)) }}%"></i><strong>{{ $nf($postsWeek) }}</strong></div></article>
<article class="striped"><span>{{ $copy['moments'] }}</span><div><strong>{{ $nf($momentsCount) }}</strong></div></article>
<article class="outline"><span>{{ $copy['cups'] }}</span><div><strong>{{ $nf($cupsCount) }}</strong></div></article>
</section>

<section class="landing-grid" id="community">
<aside class="landing-stack">
<article class="landing-card community-card">
<header><div><span class="landing-kicker">{{ $copy['community'] }}</span><h2>{{ $copy['community_title'] }}</h2></div><a class="round-link" href="{{ route('register') }}"><svg><use href="#i-arrow"></use></svg></a></header>
<div class="community-summary"><div class="avatar-stack">@foreach($recentMembers as $member)<img src="{{ $member->avatarUrl() }}" alt="">@endforeach</div><div><strong>{{ $nf($members) }} Hunter</strong><span>{{ $nf($activeToday) }} {{ $copy['active_today'] }}</span></div></div>
<div class="community-list">@forelse($recentMembers as $member)<article><img src="{{ $member->avatarUrl() }}" alt=""><div><strong>{{ $member->name ?: $member->username }}</strong><span>{{ $member->username ? '@'.$member->username : 'HNT Hunter' }}</span></div><em>{{ optional($member->last_seen_at)->diffForHumans() ?: $copy['community_text'] }}</em></article>@empty<p class="landing-empty">{{ $copy['community_text'] }}</p>@endforelse</div>
</article>
<article class="landing-card progress-card" id="more"><header><div><span class="landing-kicker">ROCKS & FORTSCHRITT</span><h2>{{ $copy['progress_title'] }}</h2></div><span class="live-pill">{{ $copy['live'] }}</span></header><div class="progress-visual"><div class="progress-ring"><strong>XP</strong><span>{{ $copy['community'] }}</span></div><div><article><strong>{{ $copy['level'] }}</strong><span>Community-Aktivität</span></article><article><strong>{{ $copy['badges'] }}</strong><span>Erfolge & Cups</span></article><article><strong>{{ $copy['rocks'] }}</strong><span>Belohnungen</span></article></div></div><p>{{ $copy['progress_text'] }}</p></article>
</aside>

<div class="landing-stack landing-main-column">
<article class="landing-card feature-card"><header><div><span class="landing-kicker">HNT.ROCKS</span><h2>{{ $copy['all_one_place'] }}</h2></div><a class="pill-link" href="{{ route('register') }}">{{ $copy['get_started'] }}</a></header><div class="feature-grid"><a href="{{ route('register') }}"><span>01</span><div><strong>{{ $copy['feed_title'] }}</strong><p>{{ $copy['feed_text'] }}</p></div><svg><use href="#i-comment"></use></svg></a><a href="{{ route('register') }}" id="lfg"><span>02</span><div><strong>{{ $copy['lfg_title'] }}</strong><p>{{ $copy['lfg_text'] }}</p></div><svg><use href="#i-users"></use></svg></a><a href="{{ route('register') }}" id="teams"><span>03</span><div><strong>{{ $copy['teams_title'] }}</strong><p>{{ $copy['teams_text'] }}</p></div><svg><use href="#i-briefcase"></use></svg></a><a href="{{ route('register') }}"><span>04</span><div><strong>{{ $copy['messages_title'] }}</strong><p>{{ $copy['messages_text'] }}</p></div><svg><use href="#i-send"></use></svg></a></div></article>

<article class="landing-card feed-card"><header><div><span class="landing-kicker">COMMUNITY FEED</span><h2>{{ $copy['activity_title'] }}</h2></div><div class="landing-tabs"><button class="active" type="button">{{ $copy['for_you'] }}</button><button type="button">{{ $copy['following'] }}</button></div></header>
@if($featuredPost)
<article class="landing-post"><header><img src="{{ $postAuthor?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}" alt=""><div><strong>{{ $postAuthor?->name ?: ($postAuthor?->username ?: 'HNT Hunter') }}</strong><span>{{ $postAuthor?->username ? '@'.$postAuthor->username.' · ' : '' }}{{ $featuredPost->created_at?->diffForHumans() }}</span></div><span class="post-tag">POST</span></header><p>{{ \Illuminate\Support\Str::limit(trim(strip_tags((string) $featuredPost->body)), 260) }}</p>
@if($featuredLfg)<div class="lfg-preview"><div><strong>{{ $featuredLfg->title }}</strong><span>{{ implode(' · ', array_slice($featuredLfg->displayTags(), 0, 3)) }}</span></div><div><strong>{{ $featuredLfg->preferred_time ?: '—' }}</strong><span>{{ $copy['start_time'] }}</span></div><div><strong>{{ (int) $featuredLfg->slots_filled }} / {{ (int) $featuredLfg->slots_total }}</strong><span>{{ $copy['team'] }}</span></div><a href="{{ route('login') }}">{{ $copy['join'] }} <svg><use href="#i-arrow"></use></svg></a></div>@endif
<footer><span><svg><use href="#i-heart"></use></svg>{{ $nf((int) $featuredPost->reactions_count) }}</span><span><svg><use href="#i-comment"></use></svg>{{ $nf((int) $featuredPost->comments_count) }}</span></footer></article>
@else<div class="landing-empty large"><strong>{{ $copy['no_post'] }}</strong><a class="landing-button dark" href="{{ route('register') }}">{{ $copy['join_community'] }}</a></div>@endif
</article>
</div>

<aside class="landing-stack">
<article class="landing-card status-card"><header><div><span class="landing-kicker">LIVE</span><h2>{{ $copy['status_title'] }}</h2></div><span class="live-pill">{{ $copy['live'] }}</span></header><div class="status-ring"><strong>{{ $nf($onlineNow) }}</strong><span>{{ $copy['online'] }}</span></div><div class="status-grid"><article><strong>{{ $nf($openLfgs) }}</strong><span>{{ $copy['open_lfgs'] }}</span></article><article><strong>{{ $nf($activeTeams) }}</strong><span>{{ $copy['active_teams'] }}</span></article><article><strong>{{ $nf($cupsCount) }}</strong><span>{{ $copy['community_cups'] }}</span></article><article><strong>4</strong><span>{{ $copy['huntmaps'] }}</span></article></div></article>
<article class="landing-card quick-card"><header><div><span class="landing-kicker">{{ $copy['explore'] }}</span><h2>{{ $copy['directly_in'] }}</h2></div></header><nav><a href="{{ route('register') }}"><span><svg><use href="#i-image"></use></svg>{{ $copy['moments'] }}</span><svg><use href="#i-arrow"></use></svg></a><a href="{{ route('register') }}"><span><svg><use href="#i-trophy"></use></svg>{{ $copy['cups'] }}</span><svg><use href="#i-arrow"></use></svg></a><a href="{{ route('maps.index') }}"><span><svg><use href="#i-map"></use></svg>{{ $copy['huntmaps'] }}</span><svg><use href="#i-arrow"></use></svg></a><a href="{{ route('guides.index') }}"><span><svg><use href="#i-folder"></use></svg>Guides</span><svg><use href="#i-arrow"></use></svg></a></nav></article>
</aside>
</section>

<section class="landing-showcase" id="moments">
<article class="landing-card moments-card"><header><div><span class="landing-kicker">MOMENTS</span><h2>{{ $copy['moments_title'] }}</h2></div><a class="pill-link" href="{{ route('register') }}">{{ $copy['all_moments'] }}</a></header><div class="moments-grid">@forelse($recentMoments as $index => $moment)<article><a class="moment-tile moment-{{ $index + 1 }}" href="{{ route('register') }}"><span>{{ gmdate('i:s', max(0, (int) $moment->duration_seconds)) }}</span><i><svg><use href="#i-plus"></use></svg></i></a><strong>{{ $moment->caption ?: 'HNT Moment' }}</strong><small>{{ $nf((int) $moment->likes_count) }} Likes · {{ $nf((int) $moment->comments_count) }} Kommentare</small></article>@empty<div class="landing-empty large"><strong>{{ $copy['no_moments'] }}</strong></div>@endforelse</div></article>

<article class="landing-card cup-card" id="cups"><header><div><span class="landing-kicker">{{ $copy['cup_title'] }}</span><h2>{{ $featuredCup?->title ?: $copy['cups'] }}</h2></div><span class="live-pill">{{ $copy['registration_open'] }}</span></header>@if($featuredCup)<div class="cup-content"><img src="{{ $cupCover }}" alt=""><div><p>{{ $featuredCup->displaySummary() }}</p><div class="cup-stats"><article><strong>{{ $nf((int) $featuredCup->active_teams_count) }}</strong><span>{{ $copy['teams'] }}</span></article><article><strong>{{ (int) $featuredCup->team_size }}</strong><span>Hunter / Team</span></article><article><strong>{{ $nf((int) $featuredCup->submissions_count) }}</strong><span>Scores</span></article></div><a class="landing-button dark" href="{{ route('login') }}">{{ $copy['view_cup'] }} <svg><use href="#i-arrow"></use></svg></a></div></div>@else<div class="landing-empty large"><strong>{{ $copy['no_cup'] }}</strong></div>@endif</article>
</section>

<section class="landing-card maps-card" id="maps"><header><div><span class="landing-kicker">HUNTMAPS</span><h2>{{ $copy['maps_title'] }}</h2></div><a class="pill-link" href="{{ route('maps.index') }}">{{ $copy['open_maps'] }}</a></header><div class="maps-layout"><div class="map-visual"><i class="map-route one"></i><i class="map-route two"></i><button class="map-dot one" type="button">C</button><button class="map-dot two" type="button">S</button><button class="map-dot three" type="button">B</button><article><span>CASH SPOT</span><strong>Stillwater Bayou</strong><small>Community bestätigt</small></article></div><div class="map-list"><a href="{{ route('maps.show', ['slug' => 'stillwater-bayou']) }}"><strong>Stillwater Bayou</strong><span>Interaktive Marker & Community-Daten</span></a><a href="{{ route('maps.show', ['slug' => 'lawson-delta']) }}"><strong>Lawson Delta</strong><span>Interaktive Marker & Community-Daten</span></a><a href="{{ route('maps.show', ['slug' => 'desalle']) }}"><strong>DeSalle</strong><span>Interaktive Marker & Community-Daten</span></a><a href="{{ route('maps.show', ['slug' => 'mammons-gulch']) }}"><strong>Mammon's Gulch</strong><span>Interaktive Marker & Community-Daten</span></a></div></div></section>

<section class="landing-cta"><div><span class="landing-kicker">{{ $copy['kicker'] }}</span><h2>{{ $copy['cta_title'] }}</h2><p>{{ $copy['cta_text'] }}</p></div><div><a class="landing-button dark" href="{{ route('register') }}">{{ $copy['register_free'] }} <svg><use href="#i-arrow"></use></svg></a><a class="landing-button light" href="{{ route('login') }}">{{ $copy['login'] }}</a></div></section>
<footer class="landing-footer"><span>{{ $copy['footer'] }}</span><nav><a href="{{ route('legal.impressum') }}">{{ $copy['imprint'] }}</a><a href="{{ route('legal.datenschutz') }}">{{ $copy['privacy'] }}</a><a href="{{ route('legal.nutzungsbedingungen') }}">{{ $copy['terms'] }}</a><a href="{{ route('legal.netiquette') }}">{{ $copy['netiquette'] }}</a></nav></footer>
</main>
<div class="landing-toast" id="landingToast"></div>
<script src="{{ asset('assets/themes/hnt_preview/landing/landing.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/landing/landing.js')) ?: time() }}"></script>
</body>
</html>
