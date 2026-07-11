@php
    $viewer = auth()->user();
    $profile = $profileUser->profile;
    $displayName = trim((string) ($profileUser->name ?: $profileUser->username ?: 'HNT Hunter'));
    $handle = $profileUser->username ? '@'.$profileUser->username : '@hunter';
    $avatarUrl = $profileUser->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $formatCount = fn ($value): string => number_format((int) $value, 0, ',', '.');
    $postsCount = (int) ($profileUser->visible_feed_posts_count ?? ($profilePostsTotal ?? 0));
    $friendsCount = (int) ($profileFriendsCount ?? 0);
    $momentsCount = (int) ($profileUser->moments_count ?? 0);
    $badgesCount = (int) ($profileUser->badges_count ?? 0);
    $rocks = (int) ($profileUser->crownWallet?->balance ?? 0);
    $xpTotal = max(0, (int) ($profileUser->xp_total ?? 0));
    $gamification = app(\App\Services\GamificationService::class);
    $level = $gamification->levelForXp($xpTotal);
    $currentLevelXp = $gamification->xpForCurrentLevel($level);
    $nextLevelXp = $gamification->xpForNextLevel($level);
    $levelProgress = (int) min(100, max(0, round((($xpTotal - $currentLevelXp) / max(1, $nextLevelXp - $currentLevelXp)) * 100)));
    $xpRemaining = max(0, $nextLevelXp - $xpTotal);
    $joinedLabel = $profileUser->created_at?->translatedFormat('M Y') ?: '—';
    $bio = trim((string) ($profile?->bio ?: $profile?->headline ?: 'Noch keine Profilbeschreibung vorhanden.'));
    $quests = collect($profileQuestPreview ?? [])->take(4);
    $posts = collect($profilePosts ?? []);
    $friends = collect($profileFriendsPreview ?? []);
    $moments = collect($profileMomentsPreview ?? []);
    $badges = collect($latestBadges ?? []);
    $profileMessageUrl = (!$isOwnProfile && $profileCanMessage && \Illuminate\Support\Facades\Route::has('messages.with-user'))
        ? route('messages.with-user', $profileUser)
        : null;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() === 'en' ? 'en' : 'de' }}">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1" name="viewport"/>
<meta content="noindex,nofollow,noarchive" name="robots"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>{{ $displayName }} · HNT.ROCKS</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&amp;display=swap" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/common.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-profile/profile-live.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-profile/profile-live.css')) ?: time() }}" rel="stylesheet"/>
</head>
<body data-page="profile">
@include('themes.hnt_preview.partials.icons')
<main class="app-shell feed-shell profile-page-shell">
@include('themes.hnt_preview.partials.header')
<section class="profile-page-heading">
<div><span>HNT.ROCKS</span><h1>Profil</h1></div>
<div class="profile-page-tools">
@if($isOwnProfile)
<a aria-label="Profil bearbeiten" class="profile-edit-main" href="{{ route('profile.edit') }}"><svg><use href="#i-user"></use></svg><span>Profil bearbeiten</span></a>
@elseif($profileMessageUrl)
<a aria-label="Nachricht senden" class="profile-edit-main" href="{{ $profileMessageUrl }}"><svg><use href="#i-comment"></use></svg><span>Nachricht</span></a>
@endif
<button aria-label="Profil teilen" class="circle-button" data-profile-share type="button"><svg><use href="#i-share"></use></svg></button>
<button aria-label="Weitere Optionen" class="circle-button" type="button"><svg><use href="#i-more"></use></svg></button>
</div>
</section>
<section class="profile-page-layout">
<aside class="profile-section-nav profile-browser-column profile-quest-column">
<div class="profile-quest-list">
@forelse($quests as $index => $quest)
@php
    $questProgress = $quest->progress->first();
    $target = max(1, (int) ($quest->target_count ?? 1));
    $current = min($target, max(0, (int) ($questProgress?->progress_count ?? 0)));
    $percent = (int) min(100, round(($current / $target) * 100));
    $questClass = ['quest-weekly', 'quest-daily', 'quest-moments', 'quest-social'][$index] ?? 'quest-weekly';
    $questIcon = ['i-check', 'i-sliders', 'i-image', 'i-users'][$index] ?? 'i-check';
@endphp
<article class="profile-quest-card {{ $questClass }}">
<div class="profile-quest-head"><span class="profile-quest-icon"><svg><use href="#{{ $questIcon }}"></use></svg></span><div><span>{{ $quest->is_weekly_contract ? 'WOCHENAUFTRAG' : 'AUFGABE' }}</span><h2>{{ $quest->displayName(app()->getLocale()) }}</h2></div><a aria-label="Auftrag öffnen" class="profile-quest-arrow" href="{{ route('gamification.index') }}"><svg><use href="#i-arrow"></use></svg></a></div>
<p>{{ $quest->actionLabel() }}</p>
<div class="profile-quest-meta"><strong>{{ $current }} / {{ $target }}</strong><span>{{ $percent }}%</span></div>
<div class="profile-quest-progress"><i style="width:{{ $percent }}%"></i></div>
<footer><span>{{ $questProgress?->completed_at ? 'Erledigt' : 'Offen' }}</span><strong>+{{ (int) $quest->xp_reward }} XP</strong></footer>
</article>
@empty
<article class="profile-quest-card quest-weekly profile-quest-empty"><div class="profile-quest-head"><span class="profile-quest-icon"><svg><use href="#i-check"></use></svg></span><div><span>AUFGABEN</span><h2>Alles erledigt</h2></div></div><p>Aktuell sind keine offenen Aufgaben vorhanden.</p></article>
@endforelse
</div>
<div class="profile-quest-footer"><a href="{{ route('gamification.index') }}"><span><b>{{ $quests->count() }}</b> offene Aufgaben</span><span>Alle ansehen <svg><use href="#i-arrow"></use></svg></span></a></div>
</aside>
<article class="profile-page-card">
<div class="profile-page-left">
<div class="profile-photo-shell"><img alt="{{ $displayName }}" class="profile-page-photo" src="{{ $avatarUrl }}"/><span aria-label="Online" class="profile-online-dot"></span>@if($isOwnProfile)<a aria-label="Profilbild ändern" href="{{ route('profile.edit') }}"><svg><use href="#i-image"></use></svg></a>@endif</div>
<section class="profile-info-card"><div class="profile-info-head"><span>PROFILINFO</span>@if($isOwnProfile)<a href="{{ route('profile.edit') }}"><svg><use href="#i-sliders"></use></svg></a>@endif</div><dl>
<div><dt>Plattform</dt><dd>{{ $profile?->platform ?: '—' }}</dd></div>
<div><dt>Region</dt><dd>{{ $profile?->region ?: '—' }}</dd></div>
<div><dt>Sprache</dt><dd>{{ $profile?->language ?: '—' }}</dd></div>
<div><dt>Spielstil</dt><dd>{{ $profile?->playstyle ?: '—' }}</dd></div>
<div><dt>Mitglied seit</dt><dd>{{ $joinedLabel }}</dd></div>
</dl></section>
<section class="profile-level-card"><div class="profile-level-top"><div><span>LEVEL</span><strong>{{ $level }}</strong></div><small>{{ $levelProgress }}%</small></div><div class="profile-level-progress"><i style="width:{{ $levelProgress }}%"></i></div><p>Noch {{ $formatCount($xpRemaining) }} XP bis Level {{ $level + 1 }}</p><div class="profile-level-stats"><span><strong>{{ $formatCount($rocks) }}</strong><small>Rocks</small></span><span><strong>{{ $formatCount($badgesCount) }}</strong><small>Badges</small></span></div></section>
</div>
<div class="profile-page-main">
<section class="profile-summary-card">
<div class="profile-summary-top"><div><span class="profile-eyebrow">HUNTER PROFIL</span><h2>{{ $displayName }}</h2><p>{{ $handle }} · {{ $profile?->hunt_role ?: 'HNT Hunter' }}</p></div><div class="profile-summary-actions">
@if(! $isOwnProfile)
@if($friendship?->isAccepted())
<form action="{{ route('friends.destroy', $friendship) }}" method="post">@csrf @method('DELETE')<button aria-label="Freund entfernen" type="submit"><svg><use href="#i-check"></use></svg></button></form>
@elseif(!$friendship && $profileCanRequestFriend)
<form action="{{ route('friends.store', $profileUser) }}" method="post">@csrf<button aria-label="Freund hinzufügen" type="submit"><svg><use href="#i-plus"></use></svg></button></form>
@endif
@endif
<button aria-label="Profil teilen" data-profile-share type="button"><svg><use href="#i-share"></use></svg></button></div></div>
<div class="profile-tags">@if($profile?->platform)<span class="yellow">{{ $profile->platform }}</span>@endif @if($profile?->region)<span class="blue">{{ $profile->region }}</span>@endif @if($profile?->playstyle)<span class="purple">{{ $profile->playstyle }}</span>@endif @if($profile?->is_lfg_available)<span class="green">LFG offen</span>@endif</div>
<div class="profile-summary-body"><div class="profile-bio"><p>{{ $bio }}</p><div class="profile-bio-points"><span>POSTS<b>{{ $formatCount($postsCount) }}</b></span><span>FREUNDE<b>{{ $formatCount($friendsCount) }}</b></span><span>MOMENTS<b>{{ $formatCount($momentsCount) }}</b></span></div></div><div class="profile-level-ring" style="--profile-level-progress:{{ $levelProgress }}%"><div><strong>{{ $level }}</strong><span>LEVEL</span></div></div></div>
<div class="profile-stat-labels"><span>Posts</span><span>Freunde</span><span>Moments</span><span>Badges</span></div><div class="profile-stat-pipeline"><div class="yellow" style="flex:{{ max(1, $postsCount) }}"></div><div class="dark" style="flex:{{ max(1, $friendsCount) }}"></div><div class="hatch" style="flex:{{ max(1, $momentsCount) }}"></div><div class="grey" style="flex:{{ max(1, $badgesCount) }}"></div></div>
</section>
<section class="profile-post-feed">
<header class="profile-feed-head"><div><span class="profile-eyebrow">PROFILINHALTE</span><h2 id="profileTabTitle">Posts</h2></div><div class="feed-tabs profile-tabs" role="tablist"><button class="active" data-profile-tab="posts" data-title="Posts" type="button">Posts</button><button data-profile-tab="info" data-title="Info" type="button">Info</button><button data-profile-tab="friends" data-title="Freunde" type="button">Freunde</button><button data-profile-tab="moments" data-title="Moments" type="button">Moments</button><button data-profile-tab="badges" data-title="Badges" type="button">Badges</button></div></header>
<div class="profile-tab-panel active" data-profile-panel="posts">
<div class="profile-post-list">
@forelse($posts as $post)
<article class="social-post"><header class="post-head"><img alt="{{ $displayName }}" src="{{ $avatarUrl }}"/><div class="post-author"><strong>{{ $displayName }}</strong><span>{{ $handle }} · {{ $post->created_at?->diffForHumans() }}</span></div><span class="post-badge">Beitrag</span><a class="post-more" href="{{ url('/feed/posts/'.$post->id) }}"><svg><use href="#i-arrow"></use></svg></a></header><div class="post-body">@if($post->body)<p>{{ $post->body }}</p>@endif @php $postImage = $post->media->first()?->mediaAsset?->url(); @endphp @if($postImage)<img class="profile-post-media" src="{{ $postImage }}" alt=""/>@endif</div><footer class="post-actions"><span><svg><use href="#i-heart"></use></svg>{{ $formatCount($post->reactions_count ?? 0) }}</span><span><svg><use href="#i-comment"></use></svg>{{ $formatCount($post->comments_count ?? 0) }}</span><a href="{{ url('/feed/posts/'.$post->id) }}">Öffnen</a></footer></article>
@empty
<div class="profile-empty-state"><strong>Noch keine Beiträge</strong><p>Veröffentlichte Posts erscheinen hier.</p></div>
@endforelse
</div></div>
<div class="profile-tab-panel" data-profile-panel="info" hidden><div class="profile-detail-grid"><article><span>Über mich</span><strong>{{ $bio }}</strong></article><article><span>Plattform</span><strong>{{ $profile?->platform ?: '—' }}</strong></article><article><span>Region</span><strong>{{ $profile?->region ?: '—' }}</strong></article><article><span>Sprache</span><strong>{{ $profile?->language ?: '—' }}</strong></article><article><span>Spielstil</span><strong>{{ $profile?->playstyle ?: '—' }}</strong></article><article><span>Rolle</span><strong>{{ $profile?->hunt_role ?: '—' }}</strong></article></div></div>
<div class="profile-tab-panel" data-profile-panel="friends" hidden><div class="profile-card-grid">@forelse($friends as $friend)<a class="profile-mini-card" href="{{ $friend->username ? route('profile.public', $friend) : '#' }}"><img alt="{{ $friend->name ?: $friend->username }}" src="{{ $friend->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"/><span><strong>{{ $friend->name ?: $friend->username }}</strong><small>{{ $friend->username ? '@'.$friend->username : '@hunter' }}</small></span></a>@empty<div class="profile-empty-state"><strong>Keine Freunde sichtbar</strong><p>Hier erscheinen bestätigte Freundschaften.</p></div>@endforelse</div></div>
<div class="profile-tab-panel" data-profile-panel="moments" hidden><div class="profile-moment-grid">@forelse($moments as $moment)<a class="profile-moment-card" href="{{ route('moments.show', $moment) }}"><img alt="{{ $moment->caption ?: 'Moment' }}" src="{{ $moment->coverUrl() }}"/><span><strong>{{ $moment->caption ?: 'HNT Moment' }}</strong><small>{{ $formatCount($moment->views_count ?? 0) }} Views</small></span></a>@empty<div class="profile-empty-state"><strong>Noch keine Moments</strong><p>Veröffentlichte Moments erscheinen hier.</p></div>@endforelse</div></div>
<div class="profile-tab-panel" data-profile-panel="badges" hidden><div class="profile-badge-grid">@forelse($badges as $badge)<article class="profile-badge-card"><span class="profile-badge-emblem">{{ strtoupper(mb_substr($badge->name ?? 'B', 0, 1)) }}</span><div><strong>{{ $badge->name }}</strong><small>{{ $badge->description ?: 'HNT.ROCKS Badge' }}</small></div></article>@empty<div class="profile-empty-state"><strong>Noch keine Badges</strong><p>Freigeschaltete Auszeichnungen erscheinen hier.</p></div>@endforelse</div></div>
</section>
</div>
</article>
</section>
<div class="toast" id="toast"></div>
</main>
<script>window.HNT_DASHBOARD_HEADER_ENDPOINT = window.location.href;</script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
@if(auth()->check())
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js')) ?: time() }}"></script>
@endif
<script src="{{ asset('assets/themes/hnt_preview/dashboard-profile/profile-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-profile/profile-live.js')) ?: time() }}"></script>
</body>
</html>