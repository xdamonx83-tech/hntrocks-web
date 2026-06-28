@php
    $viewer = auth()->user();
    $defaultAvatar = asset('assets/vikinger/img/default-avatar.svg');
    $defaultCover = asset('assets/vikinger/img/default-cover.svg');
    $demoAvatar = $viewer?->avatarUrl() ?: $defaultAvatar;
    $demoCover = $viewer?->coverUrl() ?: $defaultCover;
    $demoName = $viewer?->name ?: '{{ $demoName }}';
    $demoHandle = $viewer?->username ? '@'.$viewer->username : '{{ $demoHandle }}';
    $reworkAsset = fn (string $path): string => \App\Support\HntTheme::asset($path, 'rework');
    $reworkStyleVersion = @filemtime(public_path('assets/themes/rework/styles.css')) ?: time();
    $demoMark = $reworkAsset('images/bounty-marks.png');
    $demoPostCover = $demoCover;
@endphp
@php
    /* HNT static profile real hero media vars v1 */
    $viewer = auth()->user();
    $profileUser = $profileUser ?? $viewer;
    $profile = $profileUser?->profile;
    $defaultAvatar = asset('assets/vikinger/img/default-avatar.svg');
    $defaultCover = asset('assets/vikinger/img/default-cover.svg');
    $profileAvatarUrl = $profileUser?->avatarUrl() ?: $defaultAvatar;
    $profileCoverUrl = $profileUser?->coverUrl() ?: $defaultCover;
    $profileDisplayName = trim((string) ($profileUser?->name ?: $profileUser?->username ?: 'Krispie'));
    $profileHandle = $profileUser?->username ? '@'.$profileUser->username : '@Krispie';

    /* HNT static profile real stats vars v1 */
    $staticProfileFormatCount = fn ($count): string => number_format((int) $count, 0, ',', '.');
    $profilePostsCount = (int) ($profileUser?->visible_feed_posts_count
        ?? ($profilePostsTotal ?? 0));
    if ($profilePostsCount < 1 && isset($profilePosts) && is_countable($profilePosts)) {
        $profilePostsCount = count($profilePosts);
    }
    $profileFriendsCount = (int) ($profileFriendsCount
        ?? ($profileUser ? $profileUser->friendsCount() : 0));
    $profileMarksBalance = (int) ($profileUser?->crownWallet?->balance
        ?? ($socialiteCrownsSummary['balance'] ?? 0));
    $profileLfgCount = (int) (($profile?->is_lfg_available ?? false) ? 1 : 0);
    $profileMomentsCount = (int) ($profileUser?->moments_count ?? 0);
    $profileBadgesCount = (int) ($profileUser?->badges_count ?? 0);
    /* HNT static profile real level vars v1 */
    $profileLevel = max(1, (int) ($profileUser?->level ?? 1));
    $profileXpTotal = max(0, (int) ($profileUser?->xp_total ?? 0));
    $profileNextLevelXp = max(250, $profileLevel * 250);
    $profileLevelProgress = min(100, (int) round(($profileXpTotal % $profileNextLevelXp) / $profileNextLevelXp * 100));
    /* HNT static profile message button vars v1 */
    $profileMessageEnabled = $profileUser
        && auth()->check()
        && (int) $profileUser->id !== (int) auth()->id()
        && (bool) ($profileCanMessage ?? true)
        && \Illuminate\Support\Facades\Route::has('messages.with-user');
    $profileMessageStartUrl = $profileMessageEnabled ? route('messages.with-user', $profileUser) : '#';
@endphp

<!DOCTYPE html>

<html lang="de">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1" name="viewport"/>
<title>HNT.rocks Profil Template</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Bai+Jamjuree:wght@400;500;600;700&amp;family=Bakbak+One&amp;family=Montserrat:wght@300;400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/>
<link href="https://unpkg.com/@phosphor-icons/web@2.1.2/src/regular/style.css" rel="stylesheet"/>
<link href="https://unpkg.com/@phosphor-icons/web@2.1.2/src/bold/style.css" rel="stylesheet"/>
<link href="https://unpkg.com/@phosphor-icons/web@2.1.2/src/fill/style.css" rel="stylesheet"/>
<link href="{{ \App\Support\HntTheme::asset('styles.css', 'rework') }}?v={{ $reworkStyleVersion }}" rel="stylesheet"/>

<!-- HNT static profile friends tab layout guard v1 -->
<style>
body.profile-page [data-profile-tab-panel="friends"] .profile-list-panel {
  overflow: hidden;
}

body.profile-page [data-profile-tab-panel="friends"] .profile-list-head {
  margin-bottom: 18px;
}

body.profile-page [data-profile-tab-panel="friends"] .profile-list-head span {
  color: var(--accent);
  font-size: 12px;
  font-weight: 800;
  letter-spacing: .08em;
  text-transform: uppercase;
}

body.profile-page [data-profile-tab-panel="friends"] .profile-list-head h2 {
  margin: 6px 0 6px;
  font-size: 25px;
  line-height: 1.15;
}

body.profile-page [data-profile-tab-panel="friends"] .profile-list-head p {
  margin: 0;
  color: var(--muted-warm);
  font-size: 13px;
}

body.profile-page [data-profile-tab-panel="friends"] .profile-friends-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
  align-items: stretch;
}

body.profile-page [data-profile-tab-panel="friends"] .friend-card-v2 {
  min-width: 0;
  min-height: 118px;
  overflow: hidden;
  border-radius: 18px;
  padding: 14px;
  background: rgba(255,255,255,.035);
  border: 1px solid rgba(255,255,255,.055);
  box-shadow: none;
}

body.profile-page [data-profile-tab-panel="friends"] .friend-card-v2 .friend-card-main {
  display: flex;
  align-items: center;
  gap: 13px;
  min-width: 0;
  color: inherit;
  text-decoration: none;
}

body.profile-page [data-profile-tab-panel="friends"] .friend-card-v2 .friend-card-main > img {
  width: 66px !important;
  height: 66px !important;
  min-width: 66px !important;
  max-width: 66px !important;
  max-height: 66px !important;
  aspect-ratio: 1 / 1;
  object-fit: cover;
  display: block;
  border-radius: 17px;
  border: 1px solid rgba(255,255,255,.12);
}

body.profile-page [data-profile-tab-panel="friends"] .friend-card-v2 .friend-card-main > div {
  min-width: 0;
  overflow: hidden;
}

body.profile-page [data-profile-tab-panel="friends"] .friend-card-v2 strong {
  display: block;
  max-width: 100%;
  font-size: 15px;
  line-height: 1.15;
  color: #fff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

body.profile-page [data-profile-tab-panel="friends"] .friend-card-v2 span,
body.profile-page [data-profile-tab-panel="friends"] .friend-card-v2 small {
  display: block;
  max-width: 100%;
  color: var(--muted-warm);
  font-size: 12px;
  line-height: 1.35;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

body.profile-page [data-profile-tab-panel="friends"] .friend-card-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

body.profile-page [data-profile-tab-panel="friends"] .friend-card-meta span {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  width: auto;
  max-width: 100%;
  padding: 7px 9px;
  border-radius: 10px;
  background: rgba(255,255,255,.045);
  color: var(--muted-warm);
  font-size: 11px;
  font-weight: 700;
}

body.profile-page [data-profile-tab-panel="friends"] .friend-card-meta .ph-icon {
  font-size: 13px;
  color: var(--accent);
}

body.profile-page [data-profile-tab-panel="friends"] .profile-empty-state {
  display: grid;
  place-items: center;
  gap: 8px;
  min-height: 190px;
  padding: 26px;
  text-align: center;
  border-radius: 18px;
  background: rgba(255,255,255,.035);
  color: var(--muted-warm);
}

body.profile-page [data-profile-tab-panel="friends"] .profile-empty-state .ph-icon {
  font-size: 30px;
  color: var(--accent);
}

body.profile-page [data-profile-tab-panel="friends"] .profile-empty-state strong {
  color: #fff;
  font-size: 16px;
}

body.profile-page [data-profile-tab-panel="friends"] .profile-empty-state p {
  margin: 0;
  max-width: 320px;
  font-size: 13px;
}

@media (max-width: 760px) {
  body.profile-page [data-profile-tab-panel="friends"] .profile-friends-grid {
    grid-template-columns: 1fr;
  }
}
</style>
<!-- /HNT static profile friends tab layout guard v1 -->


<!-- HNT static profile moments tab layout guard v1 -->
<style>
body.profile-page [data-profile-tab-panel="moments"] .profile-moments-panel-real {
  overflow: hidden;
}

body.profile-page [data-profile-tab-panel="moments"] .profile-list-head {
  margin-bottom: 18px;
}

body.profile-page [data-profile-tab-panel="moments"] .profile-list-head span {
  color: var(--accent);
  font-size: 12px;
  font-weight: 800;
  letter-spacing: .08em;
  text-transform: uppercase;
}

body.profile-page [data-profile-tab-panel="moments"] .profile-list-head h2 {
  margin: 6px 0 6px;
  font-size: 25px;
  line-height: 1.15;
}

body.profile-page [data-profile-tab-panel="moments"] .profile-list-head p {
  margin: 0;
  color: var(--muted-warm);
  font-size: 13px;
}

body.profile-page [data-profile-tab-panel="moments"] .profile-moments-grid-real {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  align-items: stretch;
}

body.profile-page [data-profile-tab-panel="moments"] .profile-moment-card-real {
  min-width: 0;
  overflow: hidden;
  border-radius: 18px;
  background: rgba(255,255,255,.035);
  border: 1px solid rgba(255,255,255,.055);
}

body.profile-page [data-profile-tab-panel="moments"] .profile-moment-thumb-real {
  position: relative;
  display: block;
  width: 100%;
  aspect-ratio: 9 / 13;
  overflow: hidden;
  color: inherit;
  text-decoration: none;
  background: rgba(255,255,255,.04);
}

body.profile-page [data-profile-tab-panel="moments"] .profile-moment-thumb-real > img {
  width: 100% !important;
  height: 100% !important;
  max-width: none !important;
  max-height: none !important;
  object-fit: cover;
  display: block;
}

body.profile-page [data-profile-tab-panel="moments"] .profile-moment-thumb-real::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(0,0,0,.02), rgba(0,0,0,.58));
  pointer-events: none;
}

body.profile-page [data-profile-tab-panel="moments"] .profile-moment-thumb-real > .ph-icon {
  position: absolute;
  left: 50%;
  top: 50%;
  z-index: 2;
  transform: translate(-50%, -50%);
  display: grid;
  place-items: center;
  width: 42px;
  height: 42px;
  border-radius: 999px;
  background: rgba(0,0,0,.45);
  color: #fff;
  font-size: 20px;
  backdrop-filter: blur(8px);
}

body.profile-page [data-profile-tab-panel="moments"] .profile-moment-duration-real {
  position: absolute;
  right: 10px;
  bottom: 10px;
  z-index: 2;
  padding: 5px 8px;
  border-radius: 999px;
  background: rgba(0,0,0,.58);
  color: #fff;
  font-size: 11px;
  font-weight: 800;
}

body.profile-page [data-profile-tab-panel="moments"] .profile-moment-body-real {
  padding: 12px 12px 13px;
  min-width: 0;
}

body.profile-page [data-profile-tab-panel="moments"] .profile-moment-body-real strong {
  display: block;
  max-width: 100%;
  color: #fff;
  font-size: 14px;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

body.profile-page [data-profile-tab-panel="moments"] .profile-moment-body-real > span {
  display: block;
  margin-top: 4px;
  color: var(--muted-warm);
  font-size: 11px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

body.profile-page [data-profile-tab-panel="moments"] .profile-moment-metrics-real {
  display: flex;
  gap: 8px;
  margin-top: 10px;
  flex-wrap: wrap;
}

body.profile-page [data-profile-tab-panel="moments"] .profile-moment-metrics-real em {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-style: normal;
  color: var(--muted-warm);
  font-size: 11px;
  font-weight: 800;
}

body.profile-page [data-profile-tab-panel="moments"] .profile-moment-metrics-real .ph-icon {
  color: var(--accent);
  font-size: 13px;
}

body.profile-page [data-profile-tab-panel="moments"] .profile-moments-empty-real {
  display: grid;
  place-items: center;
  gap: 8px;
  min-height: 190px;
  padding: 26px;
  text-align: center;
  border-radius: 18px;
  background: rgba(255,255,255,.035);
  color: var(--muted-warm);
}

body.profile-page [data-profile-tab-panel="moments"] .profile-moments-empty-real .ph-icon {
  font-size: 30px;
  color: var(--accent);
}

body.profile-page [data-profile-tab-panel="moments"] .profile-moments-empty-real strong {
  color: #fff;
  font-size: 16px;
}

body.profile-page [data-profile-tab-panel="moments"] .profile-moments-empty-real p {
  margin: 0;
  max-width: 320px;
  font-size: 13px;
}

@media (max-width: 900px) {
  body.profile-page [data-profile-tab-panel="moments"] .profile-moments-grid-real {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 620px) {
  body.profile-page [data-profile-tab-panel="moments"] .profile-moments-grid-real {
    grid-template-columns: 1fr;
  }

  body.profile-page [data-profile-tab-panel="moments"] .profile-moment-thumb-real {
    aspect-ratio: 16 / 9;
  }
}
</style>
<!-- /HNT static profile moments tab layout guard v1 -->


<!-- HNT static profile moments play icon guard v1 -->
<style>
body.profile-page [data-profile-tab-panel="moments"] .profile-moment-thumb-real > .ph-icon {
  color: #fff !important;
  line-height: 1 !important;
}

body.profile-page [data-profile-tab-panel="moments"] .profile-moment-thumb-real > .ph-icon::before {
  display: block;
}
</style>
<!-- /HNT static profile moments play icon guard v1 -->








<!-- HNT static profile badges tab layout guard v3 two columns -->
<style>
body.profile-page [data-profile-tab-panel="badges"] .profile-badges-panel {
  overflow: hidden;
}

body.profile-page [data-profile-tab-panel="badges"] .badges-grid.badges-grid-v2 {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 24px;
  align-items: stretch;
}

body.profile-page [data-profile-tab-panel="badges"] .profile-badge-card.badge-card-v2 {
  min-width: 0;
  overflow: hidden;
  height: 100%;
}

body.profile-page [data-profile-tab-panel="badges"] .profile-badge-card.badge-card-v2 .badge-card-avatar {
  width: 88px !important;
  height: 88px !important;
  min-width: 88px !important;
  max-width: 88px !important;
  max-height: 88px !important;
  flex: 0 0 88px;
  object-fit: contain;
}

body.profile-page [data-profile-tab-panel="badges"] i.badge-card-avatar {
  display: grid;
  place-items: center;
  border-radius: 999px;
  background: rgba(0,0,0,.22);
  border: 1px solid rgba(255,255,255,.07);
  color: var(--accent);
  font-size: 38px;
  line-height: 1;
}

body.profile-page [data-profile-tab-panel="badges"] .badge-card-body {
  min-width: 0;
}

body.profile-page [data-profile-tab-panel="badges"] .badge-card-title strong,
body.profile-page [data-profile-tab-panel="badges"] .badge-card-title span,
body.profile-page [data-profile-tab-panel="badges"] .badge-card-meta b,
body.profile-page [data-profile-tab-panel="badges"] .badge-card-meta small {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

body.profile-page [data-profile-tab-panel="badges"] .badge-card-meta {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

body.profile-page [data-profile-tab-panel="badges"] .profile-badges-empty-real {
  display: grid;
  place-items: center;
  gap: 8px;
  min-height: 190px;
  padding: 26px;
  text-align: center;
  border-radius: 18px;
  background: rgba(255,255,255,.035);
  color: var(--muted-warm);
}

body.profile-page [data-profile-tab-panel="badges"] .profile-badges-empty-real .ph-icon {
  font-size: 30px;
  color: var(--accent);
}

body.profile-page [data-profile-tab-panel="badges"] .profile-badges-empty-real strong {
  color: #fff;
  font-size: 16px;
}

body.profile-page [data-profile-tab-panel="badges"] .profile-badges-empty-real p {
  margin: 0;
  max-width: 320px;
  font-size: 13px;
}

@media (max-width: 980px) {
  body.profile-page [data-profile-tab-panel="badges"] .badges-grid.badges-grid-v2 {
    grid-template-columns: 1fr;
  }
}
</style>
<!-- /HNT static profile badges tab layout guard v3 two columns -->


<!-- HNT static profile posts tab layout guard v1 -->
<style>
body.profile-page [data-profile-tab-panel="posts"] .profile-posts-panel-real {
  display: block;
  min-width: 0;
}

body.profile-page [data-profile-tab-panel="posts"] .profile-posts-feed-real {
  display: grid;
  gap: 22px;
  min-width: 0;
}

body.profile-page [data-profile-tab-panel="posts"] .profile-posts-feed-real > .post-card {
  margin: 0;
  width: 100%;
  min-width: 0;
}

body.profile-page [data-profile-tab-panel="posts"] .post-card .post-media img,
body.profile-page [data-profile-tab-panel="posts"] .post-card .post-media video {
  max-width: 100%;
}

body.profile-page [data-profile-tab-panel="posts"] .profile-posts-empty-real,
body.profile-page [data-profile-tab-panel="posts"] .profile-posts-more-real {
  display: grid;
  place-items: center;
  gap: 8px;
  min-height: 150px;
  padding: 26px;
  text-align: center;
  color: var(--muted-warm);
}

body.profile-page [data-profile-tab-panel="posts"] .profile-posts-empty-real .ph-icon {
  font-size: 30px;
  color: var(--accent);
}

body.profile-page [data-profile-tab-panel="posts"] .profile-posts-empty-real strong,
body.profile-page [data-profile-tab-panel="posts"] .profile-posts-more-real strong {
  color: #fff;
  font-size: 16px;
}

body.profile-page [data-profile-tab-panel="posts"] .profile-posts-empty-real p,
body.profile-page [data-profile-tab-panel="posts"] .profile-posts-more-real span {
  margin: 0;
  max-width: 360px;
  font-size: 13px;
}
</style>
<!-- /HNT static profile posts tab layout guard v1 -->



</head>
<body class="profile-page">
<div class="app">
<aside class="sidebar">
<div class="logo"><strong>HNT.</strong><span>ROCKS</span></div>
<button aria-expanded="false" aria-label="Sidebar erweitern" class="sidebar-toggle" data-sidebar-toggle="" type="button"><span></span><span></span></button>
<nav aria-label="Hauptnavigation" class="nav">
<a href="{{ route('feed.index') }}"><i aria-hidden="true" class="ph ph-house ph-icon"></i><span>Feed</span></a>
<a href="#"><i aria-hidden="true" class="ph ph-game-controller ph-icon"></i><span>Games</span></a><a href="{{ \Illuminate\Support\Facades\Route::has('maps.index') ? route('maps.index') : '#' }}"><i aria-hidden="true" class="ph ph-map-trifold ph-icon"></i><span>Maps</span></a>
<a class="thin" href="#"><i aria-hidden="true" class="ph ph-crosshair ph-icon"></i><span>Hunt</span></a>
<a href="{{ \Illuminate\Support\Facades\Route::has('gamification.index') ? route('gamification.index') : '#' }}"><i aria-hidden="true" class="ph ph-chart-bar ph-icon"></i><span>Gamification</span></a>
<a href="{{ \Illuminate\Support\Facades\Route::has('crowns.shop') ? route('crowns.shop') : '#' }}"><i aria-hidden="true" class="ph ph-storefront ph-icon"></i><span>Shop</span></a>
<a href="{{ \Illuminate\Support\Facades\Route::has('cups.index') ? route('cups.index') : '#' }}"><i aria-hidden="true" class="ph ph-trophy ph-icon"></i><span>Cups</span></a>
<a class="active" href="{{ route('profile.show') }}"><i aria-hidden="true" class="ph ph-user ph-icon"></i><span>Profil</span></a>
</nav>
<div class="nav-bottom">
<div class="nav-divider"></div>
<a data-settings-modal-open="" href="#"><i aria-hidden="true" class="ph ph-gear-six ph-icon"></i><span>Einstellungen</span></a>
<a href="#"><i aria-hidden="true" class="ph ph-sign-out ph-icon"></i><span>Logout</span></a>
</div>
</aside>
<main class="main">
<header class="topbar">
<a class="search-box" href="#"><i aria-hidden="true" class="ph ph-magnifying-glass ph-icon"></i><span>Search</span></a>
<div class="top-actions">
<div class="action-menu notification-menu">
<a aria-expanded="false" aria-label="Notifications" class="action-btn has-dot" data-dropdown-toggle="" href="#"><i aria-hidden="true" class="ph ph-bell ph-icon"></i></a>
<div class="top-dropdown notification-dropdown" data-dropdown-panel="">
<div class="dropdown-head">
<div>
<strong>Notifications</strong>
<span>Aktuelles aus deiner Lobby</span>
</div>
<a href="#">Alle</a>
</div>
<div class="dropdown-list">
<a class="dropdown-item unread" href="#">
<img alt="" src="{{ $demoPostCover }}"/>
<span><strong>Summer Cup startet bald</strong><small>Team-Anmeldungen sind jetzt offen.</small></span>
<em>8m</em>
</a>
<a class="dropdown-item" href="#">
<img alt="" src="{{ $defaultAvatar }}"/>
<span><strong>Neuer Kommentar</strong><small>Tina hat auf deinen Feed-Post reagiert.</small></span>
<em>21m</em>
</a>
<a class="dropdown-item" href="#">
<img alt="" src="{{ $demoMark }}"/>
<span><strong>Loadout bewertet</strong><small>Dein Community-Loadout bekommt gerade Likes.</small></span>
<em>1h</em>
</a>
</div>
<a class="dropdown-footer" href="#">Alle Notifications öffnen</a>
</div>
</div>
<div class="action-menu friend-request-menu">
<a aria-expanded="false" aria-label="Freundschaftsanfragen" class="action-btn has-dot" data-dropdown-toggle="" href="#"><i aria-hidden="true" class="ph ph-user-plus ph-icon"></i></a>
<div class="top-dropdown friend-request-dropdown" data-dropdown-panel="">
<div class="dropdown-head">
<div>
<strong>Freundschaftsanfragen</strong>
<span>Neue Hunter wollen sich verbinden</span>
</div>
<a href="#">Alle</a>
</div>
<div class="dropdown-list request-list">
<a class="dropdown-item request-item unread" href="#">
<img alt="{{ $demoName }} Army" src="{{ $defaultAvatar }}"/>
<span class="request-copy"><strong>{{ $demoName }} Army</strong><small>@krispie-1 · 2 gemeinsame Freunde</small><span class="request-actions"><b>Annehmen</b><em>Ablehnen</em></span></span>
</a>
<a class="dropdown-item request-item" href="#">
<img alt="Babybel" src="{{ $defaultAvatar }}"/>
<span class="request-copy"><strong>Babybel</strong><small>@Babybel · spielt EU / Xbox</small><span class="request-actions"><b>Annehmen</b><em>Ablehnen</em></span></span>
</a>
<a class="dropdown-item request-item" href="#">
<img alt="Faraz Tariq" src="{{ $defaultAvatar }}"/>
<span class="request-copy"><strong>Faraz Tariq</strong><small>Hat dich über Members gefunden.</small><span class="request-actions"><b>Annehmen</b><em>Ablehnen</em></span></span>
</a>
</div>
<a class="dropdown-footer" href="#">Alle Anfragen öffnen</a>
</div>
</div>
<div class="action-menu message-menu">
<a aria-expanded="false" aria-label="Messages" class="action-btn" data-dropdown-toggle="" href="#"><i aria-hidden="true" class="ph ph-chat-circle-dots ph-icon"></i></a>
<div class="top-dropdown message-dropdown" data-dropdown-panel="">
<div class="dropdown-head">
<div>
<strong>Messages</strong>
<span>Neue Chats und Antworten</span>
</div>
<a href="#">Alle</a>
</div>
<div class="dropdown-list">
<a class="dropdown-item unread" href="#">
<img alt="" src="{{ $defaultAvatar }}"/>
<span><strong>Tina Tzoo</strong><small>Bin gleich online, schick mir dein Loadout.</small></span>
<em>2m</em>
</a>
<a class="dropdown-item" href="#">
<img alt="" src="{{ $defaultAvatar }}"/>
<span><strong>MKBHD</strong><small>Sieht wild aus. Würde ich testen.</small></span>
<em>18m</em>
</a>
<a class="dropdown-item" href="#">
<img alt="" src="{{ $defaultAvatar }}"/>
<span><strong>Faraz Tariq</strong><small>Ready Lobby später?</small></span>
<em>1h</em>
</a>
</div>
<a class="dropdown-footer" href="#">Alle Messages öffnen</a>
</div>
</div>
<div class="action-menu user-menu">
<a aria-expanded="false" aria-label="User menu" class="avatar-wrap" data-dropdown-toggle="" href="#"><img alt="{{ $demoName }}" class="header-avatar" src="{{ $demoAvatar }}"/></a>
<div class="top-dropdown user-dropdown" data-dropdown-panel="">
<div class="user-dropdown-head">
<img alt="{{ $demoName }}" src="{{ $demoAvatar }}"/>
<div>
<strong>{{ $demoName }}</strong>
<span>Level 7 · 12,256 Marks</span>
</div>
</div>
<div class="user-menu-list">
<a href="{{ route('profile.show') }}"><i aria-hidden="true" class="ph ph-user ph-icon"></i><span>Mein Profil</span></a>
<a data-settings-modal-open="" href="#"><i aria-hidden="true" class="ph ph-gear-six ph-icon"></i><span>Einstellungen</span></a>
<a href="{{ \Illuminate\Support\Facades\Route::has('crowns.shop') ? route('crowns.shop') : '#' }}"><img alt="" src="{{ $demoMark }}"/><span>Bounty Marks</span></a>
<a href="{{ \Illuminate\Support\Facades\Route::has('crowns.shop') ? route('crowns.shop') : '#' }}"><i aria-hidden="true" class="ph ph-storefront ph-icon"></i><span>Shop</span></a>
</div>
<a class="user-logout" href="#"><i aria-hidden="true" class="ph ph-sign-out ph-icon"></i>Logout</a>
</div>
</div>
</div>
</header>
<section class="content-grid">
<div class="left-col">
<section class="card hnt-profile-hero">
<div class="hnt-profile-cover" style="background-image: linear-gradient(180deg, rgba(17,17,15,.08), rgba(17,17,15,.76)), url('{{ $profileCoverUrl }}');">
<div class="cover-glow"></div>
<a aria-label="Coverbild hochladen" class="profile-share-btn" href="#">↗</a>
</div>
<div class="hnt-profile-mainline profile-mainline-corrected">
<div class="hnt-profile-stats-left">
<div><strong>{{ $staticProfileFormatCount($profilePostsCount) }}</strong><span>Posts</span></div>
<div><strong>{{ $staticProfileFormatCount($profileFriendsCount) }}</strong><span>Freunde</span></div>
</div>
<div class="hnt-profile-identity">
<div class="profile-avatar-badge">
<img alt="{{ $profileDisplayName }}" data-rework-profile-avatar src="{{ $profileAvatarUrl }}"/>
<span><img alt="Bounty Marks" src="{{ $demoMark }}"/></span>
</div>
<h1>{{ $profileDisplayName }}</h1>
<p>{{ $profileHandle }}</p>
<div class="hnt-profile-actions">
<a class="btn" href="#">Freund hinzufügen</a>
<a class="btn ghost" data-profile-edit-open="" href="#">Profil bearbeiten</a>
@if($profileMessageEnabled)
<a class="btn light" href="{{ $profileMessageStartUrl }}" data-profile-message-open data-profile-message-start-url="{{ $profileMessageStartUrl }}" data-ready-label="Nachricht" data-loading-label="Öffnet...">Nachricht</a>
@else
<a class="btn light is-disabled profile-message-disabled" href="#" aria-disabled="true">Nachricht</a>
@endif
</div>
</div>
<div class="hnt-profile-stats-right">
<div><strong>{{ $staticProfileFormatCount($profileMarksBalance) }}</strong><span>Marks</span></div>
<div><strong>{{ $staticProfileFormatCount($profileLfgCount) }}</strong><span>LFG</span></div>
<div><strong>{{ $staticProfileFormatCount($profileMomentsCount) }}</strong><span>Moments</span></div>
</div>
</div>
<div class="hnt-profile-progress">
<span>Level {{ $profileLevel }}</span>
<div class="profile-progress-track"><i style="width: {{ $profileLevelProgress }}%"></i><strong>{{ $profileLevelProgress }}%</strong></div>
</div>
</section>
<section class="card hnt-profile-tabs-card">
<div aria-label="Profilbereiche" class="hnt-profile-tabs" role="tablist">
<a aria-selected="true" class="active" data-profile-tab="posts" href="#profile-posts" role="tab"><i aria-hidden="true" class="ph ph-image ph-icon"></i>Posts <span>{{ $staticProfileFormatCount($profilePostsCount) }}</span></a>
<a aria-selected="false" data-profile-tab="info" href="#profile-info" role="tab"><i aria-hidden="true" class="ph ph-user ph-icon"></i>Info</a>
<a aria-selected="false" data-profile-tab="friends" href="#profile-friends" role="tab"><i aria-hidden="true" class="ph ph-chat-circle ph-icon"></i>Freunde <span>{{ $staticProfileFormatCount($profileFriendsCount) }}</span></a>
<a aria-selected="false" data-profile-tab="moments" href="#profile-moments" role="tab"><i aria-hidden="true" class="ph ph-trophy ph-icon"></i>Moments <span>{{ $staticProfileFormatCount($profileMomentsCount) }}</span></a>
<a aria-selected="false" data-profile-tab="badges" href="#profile-badges" role="tab"><img alt="" src="{{ $demoMark }}"/>Badges <span>{{ $staticProfileFormatCount($profileBadgesCount) }}</span></a>
<a class="profile-create-post" data-post-composer-open="" href="#">Post erstellen</a>
</div>
</section>
<div class="profile-tab-panels">
<!-- HNT static profile real posts tab v1 -->
@php
    $postsProfileUser = $profileUser ?? auth()->user();
    $postsProfileName = trim((string) ($profileDisplayName ?? ($postsProfileUser?->name ?: $postsProfileUser?->username ?: 'HNT Hunter')));
    $profilePostsCollection = collect($profilePosts ?? []);
    $postsTotal = (int) ($profilePostsTotal ?? ($postsProfileUser?->visible_feed_posts_count ?? $profilePostsCollection->count()));
    $postsShown = (int) ($profilePostsShown ?? $profilePostsCollection->count());
    $postsFormatCount = fn ($count): string => number_format((int) $count, 0, ',', '.');
    $currentProfilePostsPage = max(1, (int) ($profilePostPage ?? request()->query('profile_posts_page', 1)));
    $nextProfilePostsPage = $currentProfilePostsPage + 1;
    $nextProfilePostsUrl = request()->fullUrlWithQuery(['profile_posts_page' => $nextProfilePostsPage]) . '#profile-posts';
@endphp
<div class="profile-tab-panel is-active" data-profile-tab-panel="posts">
<section class="profile-posts-panel-real">
@if($profilePostsCollection->isEmpty())
<div class="card profile-empty-state profile-posts-empty-real">
<i aria-hidden="true" class="ph ph-images ph-icon"></i>
<strong>Noch keine Posts sichtbar</strong>
<p>Wenn {{ $postsProfileName }} Posts veröffentlicht, erscheinen sie hier.</p>
</div>
@else
<div class="profile-posts-feed-real" data-profile-posts-stream>
@foreach($profilePostsCollection as $post)
@include('themes.rework.feed.partials.post-card', ['post' => $post])
@endforeach
</div>
@if($postsShown < $postsTotal)
<div class="rework-load-more-wrap profile-posts-more-real" data-profile-posts-load-more-wrap>
<button
    class="btn rework-load-more profile-posts-load-more-btn"
    type="button"
    data-profile-posts-load-more
    data-next-url="{{ $nextProfilePostsUrl }}"
    data-loading-label="Lädt..."
    data-ready-label="Weitere Posts laden"
    data-error-label="Erneut versuchen"
>
    <span data-profile-posts-load-more-label>Weitere Posts laden</span>
</button>
<small>{{ $postsFormatCount($postsShown) }} von {{ $postsFormatCount($postsTotal) }} Posts geladen</small>
</div>
@endif
@endif
</section>
</div>

<!-- HNT static profile real info tab v1 -->
@php
    $infoProfileUser = $profileUser ?? auth()->user();
    $infoProfile = $infoProfileUser?->profile;

    $infoName = trim((string) ($profileDisplayName ?? ($infoProfileUser?->name ?: $infoProfileUser?->username ?: 'HNT Hunter')));
    $infoBio = trim((string) ($infoProfile?->bio ?? ''));
    $infoHeadline = trim((string) ($infoProfile?->headline ?? ''));
    $infoAboutText = $infoBio ?: ($infoHeadline ?: 'Noch keine Profilinformationen hinterlegt.');

    $infoFormatCount = fn ($count): string => number_format((int) $count, 0, ',', '.');
    $infoDash = '—';

    $infoLevel = max(1, (int) ($profileLevel ?? ($infoProfileUser?->level ?? 1)));
    $infoXpTotal = max(0, (int) ($profileXpTotal ?? ($infoProfileUser?->xp_total ?? 0)));

    $infoPlatform = filled($infoProfile?->platform ?? null) ? $infoProfile->platform : $infoDash;
    $infoRegion = filled($infoProfile?->region ?? null) ? $infoProfile->region : $infoDash;
    $infoLanguage = filled($infoProfile?->language ?? null) ? $infoProfile->language : $infoDash;
    $infoPlaystyle = filled($infoProfile?->playstyle ?? null) ? $infoProfile->playstyle : $infoDash;
    $infoHuntRole = filled($infoProfile?->hunt_role ?? null) ? $infoProfile->hunt_role : $infoDash;
    $infoLfgLabel = (bool) ($infoProfile?->is_lfg_available ?? false) ? 'LFG offen' : 'Nicht offen';

    $infoDiscord = filled($infoProfile?->discord_name ?? null) ? $infoProfile->discord_name : null;
    $infoSteam = filled($infoProfile?->steam_url ?? null) ? $infoProfile->steam_url : null;
    $infoTwitch = filled($infoProfile?->twitch_url ?? null) ? $infoProfile->twitch_url : null;
    $infoYoutube = filled($infoProfile?->youtube_url ?? null) ? $infoProfile->youtube_url : null;
@endphp
<div class="profile-tab-panel" data-profile-tab-panel="info">
<section class="card profile-info-panel">
<h2>Über {{ $infoName }}</h2>
<p>{{ $infoAboutText }}</p>
<div class="profile-info-grid">
<div><strong>{{ $infoFormatCount($infoLevel) }}</strong><span>Level</span></div>
<div><strong>{{ $infoFormatCount($infoXpTotal) }}</strong><span>XP</span></div>
<div><strong>{{ $infoPlatform }}</strong><span>Plattform</span></div>
<div><strong>{{ $infoRegion }}</strong><span>Region</span></div>
<div><strong>{{ $infoLanguage }}</strong><span>Sprache</span></div>
<div><strong>{{ $infoPlaystyle }}</strong><span>Spielstil</span></div>
<div><strong>{{ $infoHuntRole }}</strong><span>Rolle</span></div>
<div><strong>{{ $infoLfgLabel }}</strong><span>Suche</span></div>
@if($infoDiscord)
<div><strong>{{ $infoDiscord }}</strong><span>Discord</span></div>
@endif
@if($infoSteam)
<div><strong>Steam</strong><span>{{ $infoSteam }}</span></div>
@endif
@if($infoTwitch)
<div><strong>Twitch</strong><span>{{ $infoTwitch }}</span></div>
@endif
@if($infoYoutube)
<div><strong>YouTube</strong><span>{{ $infoYoutube }}</span></div>
@endif
</div>
</section>
</div>

<!-- HNT static profile real friends tab v1 -->
@php
    $friendsProfileUser = $profileUser ?? auth()->user();
    $friendsProfileName = trim((string) ($profileDisplayName ?? ($friendsProfileUser?->name ?: $friendsProfileUser?->username ?: 'HNT Hunter')));
    $friendsPreview = collect($profileFriendsPreview ?? []);
    $friendsTotal = (int) ($profileFriendsCount ?? ($friendsProfileUser ? $friendsProfileUser->friendsCount() : $friendsPreview->count()));
    $friendsFormatCount = fn ($count): string => number_format((int) $count, 0, ',', '.');
    $friendsDefaultAvatar = asset('assets/vikinger/img/default-avatar.svg');

    $friendsProfileUrl = function ($friend): string {
        if (! $friend || ! $friend->username) {
            return '#';
        }

        if ((int) $friend->id === (int) auth()->id() && \Illuminate\Support\Facades\Route::has('profile.show')) {
            return route('profile.show');
        }

        return \Illuminate\Support\Facades\Route::has('profile.public')
            ? route('profile.public', $friend)
            : '#';
    };
@endphp
<div class="profile-tab-panel" data-profile-tab-panel="friends">
<section class="card profile-list-panel">
<header class="profile-list-head">
<div>
<span>Freunde</span>
<h2>Freundesliste von {{ $friendsProfileName }}</h2>
<p>{{ $friendsFormatCount($friendsTotal) }} bestätigte Freunde</p>
</div>
</header>

@if($friendsPreview->isEmpty())
<div class="profile-empty-state">
<i aria-hidden="true" class="ph ph-users-three ph-icon"></i>
<strong>Noch keine Freunde sichtbar</strong>
<p>Sobald Freundschaften bestätigt sind, erscheinen sie hier.</p>
</div>
@else
<div class="profile-friends-grid">
@foreach($friendsPreview as $friend)
@php
    $friendProfile = $friend?->profile;
    $friendName = trim((string) ($friend?->name ?: $friend?->username ?: 'HNT Hunter'));
    $friendHandle = $friend?->username ? '@'.$friend->username : '@hunter';
    $friendAvatar = $friend?->avatarUrl() ?: $friendsDefaultAvatar;
    $friendUrl = $friendsProfileUrl($friend);
    $commonCount = (int) ($friend?->getAttribute('common_friends_count') ?? 0);
    $metaParts = collect([
        $friendProfile?->platform,
        $friendProfile?->region,
        $friendProfile?->playstyle,
    ])->filter()->values();
    $friendMeta = $metaParts->isNotEmpty() ? $metaParts->take(3)->implode(' · ') : 'HNT Hunter';
    $commonLabel = $commonCount > 0
        ? $friendsFormatCount($commonCount).' gemeinsame Freunde'
        : 'Keine gemeinsamen Freunde';
@endphp
<article class="friend-card-v2">
<a class="friend-card-main" href="{{ $friendUrl }}">
<img alt="{{ $friendName }}" src="{{ $friendAvatar }}"/>
<div>
<strong>{{ $friendName }}</strong>
<span>{{ $friendHandle }}</span>
<small>{{ $friendMeta }}</small>
</div>
</a>
<div class="friend-card-meta">
<span><i aria-hidden="true" class="ph ph-handshake ph-icon"></i>{{ $commonLabel }}</span>
@if((bool) ($friendProfile?->is_lfg_available ?? false))
<span><i aria-hidden="true" class="ph ph-crosshair ph-icon"></i>LFG offen</span>
@endif
</div>
</article>
@endforeach
</div>
@endif
</section>
</div>

<!-- HNT static profile real moments tab v1 -->
@php
    $momentsProfileUser = $profileUser ?? auth()->user();
    $momentsProfileName = trim((string) ($profileDisplayName ?? ($momentsProfileUser?->name ?: $momentsProfileUser?->username ?: 'HNT Hunter')));
    $momentsPreview = collect($profileMomentsPreview ?? []);
    $momentsTotal = (int) ($profileMomentsCount ?? ($momentsProfileUser?->moments_count ?? $momentsPreview->count()));
    $momentsFormatCount = fn ($count): string => number_format((int) $count, 0, ',', '.');

    $momentUrl = function ($moment): string {
        return ($moment && \Illuminate\Support\Facades\Route::has('moments.show'))
            ? route('moments.show', $moment)
            : '#';
    };

    $momentDurationLabel = function ($seconds): string {
        $seconds = max(0, (int) $seconds);
        if ($seconds < 1) {
            return 'Moment';
        }

        $minutes = intdiv($seconds, 60);
        $rest = $seconds % 60;

        return $minutes > 0
            ? sprintf('%d:%02d', $minutes, $rest)
            : sprintf('0:%02d', $rest);
    };
@endphp
<div class="profile-tab-panel" data-profile-tab-panel="moments">
<section class="card profile-moments-panel-real">
<header class="profile-list-head">
<div>
<span>Moments</span>
<h2>Moments von {{ $momentsProfileName }}</h2>
<p>{{ $momentsFormatCount($momentsTotal) }} veröffentlichte Moments</p>
</div>
</header>

@if($momentsPreview->isEmpty())
<div class="profile-empty-state profile-moments-empty-real">
<i aria-hidden="true" class="ph ph-play-circle ph-icon"></i>
<strong>Noch keine Moments sichtbar</strong>
<p>Sobald veröffentlichte Moments vorhanden sind, erscheinen sie hier.</p>
</div>
@else
<div class="profile-moments-grid-real">
@foreach($momentsPreview as $moment)
@php
    $momentTitle = trim((string) ($moment->caption ?: $moment->description ?: 'HNT Moment'));
    $momentCover = $moment->coverUrl();
    $momentHref = $momentUrl($moment);
    $momentDuration = $momentDurationLabel($moment->duration_seconds ?? 0);
    $momentViews = (int) ($moment->views_count ?? 0);
    $momentLikes = (int) ($moment->likes_count ?? 0);
    $momentComments = (int) ($moment->comments_count ?? 0);
    $momentPublished = $moment->published_at?->diffForHumans() ?: 'Moment';
@endphp
<article class="profile-moment-card-real">
<a href="{{ $momentHref }}" class="profile-moment-thumb-real">
<img src="{{ $momentCover }}" alt="{{ $momentTitle }}"/>
<span class="profile-moment-duration-real">{{ $momentDuration }}</span>
<i aria-hidden="true" class="ph ph-play ph-icon"></i>
</a>
<div class="profile-moment-body-real">
<strong title="{{ $momentTitle }}">{{ $momentTitle }}</strong>
<span>{{ $momentPublished }}</span>
<div class="profile-moment-metrics-real">
<em><i aria-hidden="true" class="ph ph-eye ph-icon"></i>{{ $momentsFormatCount($momentViews) }}</em>
<em><i aria-hidden="true" class="ph ph-heart ph-icon"></i>{{ $momentsFormatCount($momentLikes) }}</em>
<em><i aria-hidden="true" class="ph ph-chat-circle ph-icon"></i>{{ $momentsFormatCount($momentComments) }}</em>
</div>
</div>
</article>
@endforeach
</div>
@endif
</section>
</div>

<!-- HNT static profile real badges tab v2 demo layout -->
@php
    $badgesProfileUser = $profileUser ?? auth()->user();
    $badgesPreview = collect($latestBadges ?? []);
    $badgesTotal = (int) ($profileBadgesCount ?? ($badgesProfileUser?->badges_count ?? $badgesPreview->count()));
    $badgesFormatCount = fn ($count): string => number_format((int) $count, 0, ',', '.');
    $badgesFallbackIcon = \App\Support\HntTheme::asset('images/bounty-mark.png', 'rework');
@endphp
<div class="profile-tab-panel" data-profile-tab-panel="badges">
<section class="card profile-badges-panel">
<div class="profile-section-head"><h2>Badges</h2><span>{{ $badgesFormatCount($badgesTotal) }} freigeschaltet</span></div>

@if($badgesPreview->isEmpty())
<div class="profile-empty-state profile-badges-empty-real">
<i aria-hidden="true" class="ph ph-seal-check ph-icon"></i>
<strong>Noch keine Badges sichtbar</strong>
<p>Sobald Badges freigeschaltet sind, erscheinen sie hier.</p>
</div>
@else
<div class="badges-grid badges-grid-v2">
@foreach($badgesPreview as $badge)
@php
    $badgeName = trim((string) ($badge->name ?: 'Badge'));
    $badgeDescription = trim((string) ($badge->description ?: 'Badge freigeschaltet.'));
    $badgeRarity = method_exists($badge, 'rarityLabel') ? $badge->rarityLabel() : ucfirst((string) ($badge->rarity ?: 'Normal'));
    $badgeStatus = (bool) ($badge->is_active ?? true) ? 'Aktiv' : 'Inaktiv';
    $badgeSource = trim((string) ($badge->category ?: $badge->slug ?: 'Badge'));

    $badgeIconUrl = method_exists($badge, 'iconUrl') ? $badge->iconUrl() : null;
    $badgeIconRaw = trim((string) ($badge->icon ?? ''));
    $badgeIconClasses = null;

    if (! $badgeIconUrl && $badgeIconRaw && str_contains($badgeIconRaw, 'ph-')) {
        $iconParts = collect(preg_split('/\s+/', $badgeIconRaw))->filter()->values();
        $badgeIconClasses = $iconParts
            ->merge(['ph', 'ph-icon', 'badge-card-avatar'])
            ->unique()
            ->implode(' ');
    }

    $badgeAwardedRaw = $badge->pivot?->awarded_at ?? $badge->created_at ?? null;
    try {
        $badgeAwardedAt = $badgeAwardedRaw ? \Illuminate\Support\Carbon::parse($badgeAwardedRaw)->format('d.m.Y') : 'Badge';
    } catch (\Throwable $exception) {
        $badgeAwardedAt = 'Badge';
    }
@endphp
<article class="profile-badge-card badge-card-v2">
@if($badgeIconUrl)
<img alt="{{ $badgeName }}" class="badge-card-avatar" src="{{ $badgeIconUrl }}">
@elseif($badgeIconClasses)
<i aria-hidden="true" class="{{ $badgeIconClasses }}"></i>
@else
<img alt="{{ $badgeName }}" class="badge-card-avatar" src="{{ $badgesFallbackIcon }}">
@endif
<div class="badge-card-body">
<div class="badge-card-title"><strong>{{ $badgeName }}</strong><span>{{ $badgeAwardedAt }}</span></div>
<div class="badge-card-meta">
<span><small>Seltenheit</small><b>{{ $badgeRarity }}</b></span>
<span><small>Status</small><b>{{ $badgeStatus }}</b></span>
<span><small>Quelle</small><b>{{ $badgeSource }}</b></span>
</div>
<p>{{ $badgeDescription }}</p>
<div class="badge-card-progress"><small>Badge</small><i><em style="width: 100%"></em><strong>100%</strong></i></div>
</div>
</article>
@endforeach
</div>
@endif
</section>
</div>


</div>
</div>
@include('themes.rework.profile.partials.right-widgets')
</section>
</main>
</div>
<div aria-hidden="true" class="modal-backdrop profile-edit-backdrop" data-profile-edit-modal="">
<section aria-labelledby="profile-edit-title" aria-modal="true" class="profile-edit-modal" role="dialog">
<header class="profile-edit-header">
<div>
<span class="profile-edit-eyebrow">Profil</span>
<h2 id="profile-edit-title">Profil bearbeiten</h2>
<p>Aktualisiere die wichtigsten Angaben für dein HNT.rocks-Profil.</p>
</div>
<button aria-label="Profil bearbeiten schließen" class="profile-edit-close" data-profile-edit-close="" type="button"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>
</header>
<div class="profile-edit-body">
<section class="profile-edit-preview">
<div class="profile-edit-cover-preview">
<span>Coverbild</span>
<a href="#">Ändern</a>
</div>
<div class="profile-edit-avatar-row">
<img alt="{{ $demoName }}" src="{{ $demoAvatar }}"/>
<div>
<strong>{{ $demoName }}</strong>
<small>{{ $demoHandle }}</small>
</div>
<a class="profile-edit-mini-btn" href="#">Avatar ändern</a>
</div>
</section>
<form action="#" class="profile-edit-form">
<label>
<span>Anzeigename</span>
<input type="text" value="{{ $demoName }}"/>
</label>
<label>
<span>Handle</span>
<input type="text" value="{{ $demoHandle }}"/>
</label>
<label>
<span>Plattform</span>
<select>
<option>Xbox</option>
<option>PC</option>
<option>PlayStation</option>
<option>Offen</option>
</select>
</label>
<label>
<span>Region</span>
<select>
<option>EU</option>
<option>US East</option>
<option>US West</option>
<option>Offen</option>
</select>
</label>
<label>
<span>Spielstil</span>
<select>
<option>Locker</option>
<option>Fokus</option>
<option>Kompetitiv</option>
<option>Offen</option>
</select>
</label>
<label>
<span>Sprache</span>
<select>
<option>Deutsch</option>
<option>Englisch</option>
<option>Deutsch / Englisch</option>
</select>
</label>
<label class="profile-edit-wide">
<span>Kurzbeschreibung</span>
<textarea rows="4">Hunt together, die alone...</textarea>
</label>
<label class="profile-edit-check">
<input checked="" type="checkbox"/>
<span>Als LFG offen anzeigen</span>
</label>
</form>
</div>
<footer class="profile-edit-footer">
<button class="profile-edit-cancel" data-profile-edit-close="" type="button">Abbrechen</button>
<button class="profile-edit-save" type="button">Speichern</button>
</footer>
</section>
</div>
<div aria-hidden="true" class="modal-backdrop settings-backdrop" data-settings-modal="">
<section aria-labelledby="settings-modal-title" aria-modal="true" class="settings-modal" role="dialog">
<header class="settings-modal-header">
<div>
<span class="settings-eyebrow">Account</span>
<h2 id="settings-modal-title">Einstellungen</h2>
<p>Benachrichtigungen, Datenschutz, blockierte Nutzer und Sicherheit an einem Ort verwalten.</p>
</div>
<button aria-label="Einstellungen schließen" class="settings-close" data-settings-modal-close="" type="button"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>
</header>
<nav aria-label="Einstellungen Tabs" class="settings-tabs">
<button class="is-active" data-settings-tab="notifications" type="button"><i aria-hidden="true" class="ph ph-bell ph-icon"></i><span>Benachrichtigungen</span></button>
<button data-settings-tab="privacy" type="button"><i aria-hidden="true" class="ph ph-shield-check ph-icon"></i><span>Datenschutz</span></button>
<button data-settings-tab="blocked" type="button"><i aria-hidden="true" class="ph ph-prohibit ph-icon"></i><span>Blockierte Nutzer</span></button>
<button data-settings-tab="security" type="button"><i aria-hidden="true" class="ph ph-lock-key ph-icon"></i><span>Sicherheit</span></button>
</nav>
<div class="settings-modal-body">
<section class="settings-panel is-active" data-settings-panel="notifications">
<div class="settings-section-head">
<span>Notification Center</span>
<h3>Benachrichtigungseinstellungen</h3>
<p>Lege fest, welche HNT.rocks-Meldungen im System erscheinen sollen.</p>
</div>
<div class="settings-toggle-list">
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>Feed-Kommentare</strong><p>Wenn jemand deine Feed-Beiträge kommentiert.</p></div></label>
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>Feed-Reaktionen</strong><p>Wenn jemand auf deine Feed-Beiträge reagiert.</p></div></label>
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>Freunde &amp; Netzwerk</strong><p>Anfragen, angenommene Freundschaften und Netzwerk-Aktivität.</p></div></label>
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>Teams</strong><p>Team-Anfragen, Team-Aktivität und Team-LFG.</p></div></label>
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>LFG</strong><p>Bewerbungen, Annahmen und Ablehnungen in der Mitspielersuche.</p></div></label>
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>Badges &amp; Quests</strong><p>Freigeschaltete Badges und abgeschlossene Quests.</p></div></label>
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>Moments</strong><p>Likes und Kommentare auf deinen Moments.</p></div></label>
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>Cups</strong><p>Cup-Teams, Einreichungen und Ergebnisse.</p></div></label>
</div>
</section>
<section class="settings-panel" data-settings-panel="privacy">
<div class="settings-section-head">
<span>Datenschutz</span>
<h3>Kontakt &amp; Profilsichtbarkeit</h3>
<p>Steuere, wer dein Profil sehen kann und wer dich direkt kontaktieren darf.</p>
</div>
<div class="settings-form-grid">
<label><span>Profil-Sichtbarkeit</span><select><option>Öffentlich</option><option>Nur angemeldete Nutzer</option><option>Privat</option></select></label>
<label><span>Nachrichten erlauben von</span><select><option>Allen</option><option>Angemeldeten Nutzern</option><option>Nur Kontakten</option><option>Niemandem</option></select></label>
</div>
<div class="settings-toggle-list compact">
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>Team-Einladungen erlauben</strong><p>Andere Spieler können dich zu Teams einladen.</p></div></label>
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>LFG-Einladungen erlauben</strong><p>Andere Spieler können dich für Mitspielersuche kontaktieren.</p></div></label>
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>Online-Status anzeigen</strong><p>Dein Status kann in Profil- und Community-Bereichen erscheinen.</p></div></label>
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>Level, Badges und Quests anzeigen</strong><p>Dein Fortschritt darf öffentlich im Profil sichtbar sein.</p></div></label>
</div>
</section>
<section class="settings-panel" data-settings-panel="blocked">
<div class="settings-section-head">
<span>Datenschutz</span>
<h3>Blockierte Nutzer</h3>
<p>Blockierte Spieler können später für Nachrichten, Einladungen und Interaktionen ausgeschlossen werden.</p>
</div>
<div class="settings-form-grid blocked-form">
<label><span>Nutzername</span><input placeholder="z. B. huntername" type="text"/></label>
<label><span>Notiz</span><input placeholder="Optionaler Grund für dich" type="text"/></label>
<a class="settings-inline-btn" href="#">Blockieren</a>
</div>
<div class="blocked-list">
<div class="blocked-item"><div><strong>ToxicHunter</strong><span>Spam im Chat</span></div><a href="#">Aufheben</a></div>
<div class="blocked-item"><div><strong>CampKing77</strong><span>Optionaler Grund für dich</span></div><a href="#">Aufheben</a></div>
</div>
</section>
<section class="settings-panel" data-settings-panel="security">
<div class="settings-section-head">
<span>Sicherheit</span>
<h3>Passwort &amp; Datenkontrolle</h3>
<p>Passwort, 2FA, Datenexport und Kontolöschung verwalten.</p>
</div>
<div class="settings-form-grid">
<label><span>Aktuelles Passwort bestätigen</span><input placeholder="••••••••" type="password"/></label>
<label><span>Neues Passwort</span><input placeholder="Neues Passwort" type="password"/></label>
<label><span>Neues Passwort bestätigen</span><input placeholder="Wiederholen" type="password"/></label>
</div>
<div class="settings-action-grid">
<a href="#"><i aria-hidden="true" class="ph ph-lock-key ph-icon"></i><strong>Passwort ändern</strong><span>Login-Daten aktualisieren</span></a>
<a href="#"><i aria-hidden="true" class="ph ph-device-mobile-camera ph-icon"></i><strong>2FA einrichten</strong><span>Authenticator-App verbinden</span></a>
<a href="#"><i aria-hidden="true" class="ph ph-download-simple ph-icon"></i><strong>Datenexport</strong><span>Accountdaten herunterladen</span></a>
<a class="danger" href="#"><i aria-hidden="true" class="ph ph-warning ph-icon"></i><strong>Kontolöschung</strong><span>Löschung vormerken</span></a>
</div>
</section>
</div>
<footer class="settings-modal-footer">
<button class="settings-cancel" data-settings-modal-close="" type="button">Abbrechen</button>
<button class="settings-save" type="button">Speichern</button>
</footer>
</section>
</div>


<!-- HNT static profile tabs no reload v1 -->
<style>
body.profile-page .profile-tab-panel {
  display: none;
}

body.profile-page .profile-tab-panel.is-active {
  display: block;
}

body.profile-page .hnt-profile-tabs [data-profile-tab] {
  cursor: pointer;
}
</style>
<script>
(() => {
  if (window.__hntStaticProfileTabsNoReloadReady) return;
  window.__hntStaticProfileTabsNoReloadReady = true;

  const tabs = Array.from(document.querySelectorAll('[data-profile-tab]'));
  const panels = Array.from(document.querySelectorAll('[data-profile-tab-panel]'));

  if (!tabs.length || !panels.length) return;

  const panelByName = (name) => panels.find((panel) => panel.getAttribute('data-profile-tab-panel') === name);

  const normalizeHash = () => {
    const raw = (window.location.hash || '').replace(/^#/, '');
    if (!raw) return '';
    return raw.replace(/^profile-/, '');
  };

  const activateProfileTab = (target, writeUrl = true) => {
    if (!target || !panelByName(target)) return false;

    tabs.forEach((tab) => {
      const isActive = tab.getAttribute('data-profile-tab') === target;
      tab.classList.toggle('active', isActive);
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
      tab.setAttribute('tabindex', isActive ? '0' : '-1');
    });

    panels.forEach((panel) => {
      const isActive = panel.getAttribute('data-profile-tab-panel') === target;
      panel.classList.toggle('is-active', isActive);
      panel.hidden = !isActive;
      panel.setAttribute('aria-hidden', isActive ? 'false' : 'true');
    });

    if (writeUrl && window.history?.replaceState) {
      const nextHash = `#profile-${target}`;
      if (window.location.hash !== nextHash) {
        window.history.replaceState(null, '', `${window.location.pathname}${window.location.search}${nextHash}`);
      }
    }

    return true;
  };

  tabs.forEach((tab) => {
    const target = tab.getAttribute('data-profile-tab');
    if (!target) return;

    tab.setAttribute('role', tab.getAttribute('role') || 'tab');
    tab.setAttribute('href', `#profile-${target}`);

    const panel = panelByName(target);
    if (panel) {
      const panelId = panel.id || `profile-${target}`;
      panel.id = panelId;
      panel.setAttribute('role', panel.getAttribute('role') || 'tabpanel');
      tab.setAttribute('aria-controls', panelId);
    }
  });

  document.addEventListener('click', (event) => {
    const tab = event.target.closest('[data-profile-tab]');
    if (!tab) return;

    const target = tab.getAttribute('data-profile-tab');
    if (!target || !panelByName(target)) return;

    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();

    activateProfileTab(target, true);
  }, true);

  document.addEventListener('keydown', (event) => {
    const tab = event.target.closest('[data-profile-tab]');
    if (!tab) return;

    const currentIndex = tabs.indexOf(tab);
    if (currentIndex < 0) return;

    let nextIndex = currentIndex;

    if (event.key === 'ArrowRight') nextIndex = (currentIndex + 1) % tabs.length;
    else if (event.key === 'ArrowLeft') nextIndex = (currentIndex - 1 + tabs.length) % tabs.length;
    else if (event.key === 'Home') nextIndex = 0;
    else if (event.key === 'End') nextIndex = tabs.length - 1;
    else return;

    event.preventDefault();

    const nextTab = tabs[nextIndex];
    const target = nextTab?.getAttribute('data-profile-tab');
    if (target && activateProfileTab(target, true)) {
      nextTab.focus();
    }
  });

  window.addEventListener('hashchange', () => {
    const target = normalizeHash();
    if (target) activateProfileTab(target, false);
  });

  const initialTarget = normalizeHash();
  const activeTarget = tabs.find((tab) => tab.classList.contains('active'))?.getAttribute('data-profile-tab') || 'posts';

  activateProfileTab(panelByName(initialTarget) ? initialTarget : activeTarget, false);
})();
</script>
<!-- /HNT static profile tabs no reload v1 -->

<!-- HNT static profile real feed post modals v2 safe -->
@include('themes.rework.feed.partials.post-modals')

<script src="{{ \App\Support\HntTheme::asset('script.js', 'rework') }}?v={{ $reworkScriptVersion ?? time() }}" defer></script>

<!-- HNT static profile posts ajax load more v1 -->
<style>
body.profile-page [data-profile-tab-panel="posts"] .profile-posts-more-real {
  display: grid;
  place-items: center;
  gap: 8px;
  margin-top: 22px;
  padding: 10px 0 2px;
}

body.profile-page [data-profile-tab-panel="posts"] .profile-posts-more-real small {
  color: var(--muted-warm);
  font-size: 12px;
  font-weight: 700;
}

body.profile-page [data-profile-tab-panel="posts"] .profile-posts-load-more-btn[disabled] {
  opacity: .72;
  cursor: wait;
}
</style>
<script>
(() => {
  if (window.__hntProfilePostsAjaxLoadMoreReady) return;
  window.__hntProfilePostsAjaxLoadMoreReady = true;

  const streamSelector = '[data-profile-posts-stream]';
  const wrapSelector = '[data-profile-posts-load-more-wrap]';
  const buttonSelector = '[data-profile-posts-load-more]';
  const parser = new DOMParser();

  const setButtonState = (button, state) => {
    const label = button.querySelector('[data-profile-posts-load-more-label]') || button;
    const ready = button.getAttribute('data-ready-label') || 'Weitere Posts laden';
    const loading = button.getAttribute('data-loading-label') || 'Lädt...';
    const error = button.getAttribute('data-error-label') || 'Erneut versuchen';

    if (state === 'loading') {
      button.disabled = true;
      button.setAttribute('aria-busy', 'true');
      label.textContent = loading;
      return;
    }

    button.disabled = false;
    button.setAttribute('aria-busy', 'false');
    label.textContent = state === 'error' ? error : ready;
  };

  const postIdFor = (node) => node?.getAttribute?.('id') || '';

  document.addEventListener('click', async (event) => {
    const button = event.target.closest(buttonSelector);
    if (!button) return;

    event.preventDefault();
    event.stopPropagation();

    const url = button.getAttribute('data-next-url') || button.getAttribute('href');
    const currentStream = document.querySelector(streamSelector);
    const currentWrap = button.closest(wrapSelector);

    if (!url || !currentStream || !currentWrap || button.disabled) return;

    setButtonState(button, 'loading');

    try {
      const response = await fetch(url, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          'Accept': 'text/html',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const html = await response.text();
      const doc = parser.parseFromString(html, 'text/html');
      const nextStream = doc.querySelector(streamSelector);

      if (!nextStream) {
        throw new Error('Next post stream not found.');
      }

      const knownIds = new Set(
        Array.from(currentStream.querySelectorAll('[data-rework-post-card][id]'))
          .map(postIdFor)
          .filter(Boolean)
      );

      const incomingPosts = Array.from(nextStream.querySelectorAll('[data-rework-post-card]'));
      const newPosts = incomingPosts.filter((post) => {
        const id = postIdFor(post);
        return !id || !knownIds.has(id);
      });

      if (newPosts.length < 1) {
        throw new Error('No new posts found.');
      }

      newPosts.forEach((post) => {
        currentStream.appendChild(document.importNode(post, true));
      });

      const nextWrap = doc.querySelector(wrapSelector);

      if (nextWrap) {
        currentWrap.replaceWith(document.importNode(nextWrap, true));
      } else {
        currentWrap.remove();
      }

      document.dispatchEvent(new CustomEvent('hnt:profile-posts-loaded', {
        detail: { count: newPosts.length },
      }));
    } catch (error) {
      console.error('[HNT] Profile posts load-more failed:', error);
      setButtonState(button, 'error');
    }
  }, true);
})();
</script>
<!-- /HNT static profile posts ajax load more v1 -->
@include('themes.socialite.partials.chat-tabs')
<script defer src="{{ asset('assets/socialite/js/hnt-socialite-chat-tabs.js') }}?v={{ $socialiteChatTabsVersion ?? time() }}"></script>

<!-- HNT static profile message chat-tab bridge v1 -->
<style>
body.profile-page .profile-message-disabled {
  opacity: .55;
  pointer-events: none;
}

body.profile-page [data-profile-message-open][aria-busy="true"] {
  opacity: .75;
  pointer-events: none;
}
</style>
<script>
(() => {
  if (window.__hntProfileMessageChatTabBridgeReady) return;
  window.__hntProfileMessageChatTabBridgeReady = true;

  const openViaChatTabs = (showUrl, conversationId) => {
    const cleanShowUrl = String(showUrl || '').replace(/\/$/, '');
    const chatTabUrl = `${cleanShowUrl}/chat-tab`;
    const opener = document.createElement('a');

    opener.href = cleanShowUrl || chatTabUrl;
    opener.setAttribute('data-hnt-chat-tab-open', '');
    opener.setAttribute('data-hnt-chat-tab-url', chatTabUrl);

    if (conversationId) {
      opener.setAttribute('data-hnt-chat-conversation-id', String(conversationId));
    }

    opener.style.display = 'none';
    document.body.appendChild(opener);

    opener.dispatchEvent(new MouseEvent('click', {
      bubbles: true,
      cancelable: true,
      view: window,
    }));

    window.setTimeout(() => opener.remove(), 150);
  };

  document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-profile-message-open]');
    if (!trigger) return;

    event.preventDefault();
    event.stopPropagation();

    const startUrl = trigger.getAttribute('data-profile-message-start-url') || trigger.href;
    const readyLabel = trigger.getAttribute('data-ready-label') || trigger.textContent || 'Nachricht';
    const loadingLabel = trigger.getAttribute('data-loading-label') || 'Öffnet...';

    if (!startUrl || startUrl === '#') return;
    if (trigger.getAttribute('aria-busy') === 'true') return;

    trigger.setAttribute('aria-busy', 'true');
    trigger.textContent = loadingLabel;

    try {
      const response = await fetch(startUrl, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      const payload = await response.json().catch(() => ({}));

      if (!response.ok) {
        throw new Error(payload?.message || 'Chat konnte nicht geöffnet werden.');
      }

      openViaChatTabs(payload.show_url || startUrl, payload.conversation_id || '');
    } catch (error) {
      console.error('[HNT] Profile message chat-tab failed:', error);
      window.location.href = startUrl;
    } finally {
      window.setTimeout(() => {
        trigger.removeAttribute('aria-busy');
        trigger.textContent = readyLabel;
      }, 250);
    }
  }, true);
})();
</script>
<!-- /HNT static profile message chat-tab bridge v1 -->

</body>
</html>
