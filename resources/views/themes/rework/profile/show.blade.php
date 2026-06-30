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
        && (bool) ($profileCanMessage ?? false)
        && \Illuminate\Support\Facades\Route::has('messages.with-user');
    $profileMessageStartUrl = $profileMessageEnabled ? route('messages.with-user', $profileUser) : '#';
    $currentLocale = app()->getLocale() === 'en' ? 'en' : 'de';
@endphp

<!DOCTYPE html>

<html lang="{{ $currentLocale }}">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1" name="viewport"/>
<title>{{ __('ui.rework_profile_page_title') }}</title>
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

<!-- HNT profile moments exact friends values v1 -->
<style>
body.profile-page [data-profile-tab-panel="moments"] .profile-moments-panel-real {
  padding: 26px !important;
  border-radius: var(--radius-md) !important;
  background: #1E1E1D;
  overflow: hidden;
}

body.profile-page [data-profile-tab-panel="moments"] .profile-list-head {
  margin: 0 0 18px !important;
  padding: 0 !important;
}

body.profile-page [data-profile-tab-panel="moments"] .profile-list-head span {
  display: block;
  color: var(--accent);
  font-size: 12px !important;
  line-height: 16px !important;
  font-weight: 800 !important;
  letter-spacing: .08em !important;
  text-transform: uppercase;
}

body.profile-page [data-profile-tab-panel="moments"] .profile-list-head h2 {
  margin: 6px 0 6px !important;
  color: #fff;
  font-size: 26px !important;
  line-height: 32px !important;
  font-weight: 800 !important;
  letter-spacing: 0 !important;
}

body.profile-page [data-profile-tab-panel="moments"] .profile-list-head p {
  margin: 0 !important;
  color: var(--muted-warm);
  font-size: 15px !important;
  line-height: 22px !important;
}

body.profile-page [data-profile-tab-panel="moments"] .profile-moments-grid-real {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 18px !important;
  margin: 0 !important;
  padding: 0 !important;
  align-items: stretch;
}

body.profile-page [data-profile-tab-panel="moments"] .profile-moment-card-real {
  min-width: 0;
  margin: 0 !important;
}

@media (max-width: 1100px) {
  body.profile-page [data-profile-tab-panel="moments"] .profile-moments-panel-real {
    padding: 26px !important;
  }

  body.profile-page [data-profile-tab-panel="moments"] .profile-moments-grid-real {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px !important;
  }
}

@media (max-width: 760px) {
  body.profile-page [data-profile-tab-panel="moments"] .profile-moments-panel-real {
    padding: 18px !important;
  }

  body.profile-page [data-profile-tab-panel="moments"] .profile-list-head {
    margin-bottom: 18px !important;
  }

  body.profile-page [data-profile-tab-panel="moments"] .profile-list-head h2 {
    font-size: 24px !important;
    line-height: 30px !important;
  }

  body.profile-page [data-profile-tab-panel="moments"] .profile-list-head p {
    font-size: 13px !important;
    line-height: 19px !important;
  }

  body.profile-page [data-profile-tab-panel="moments"] .profile-moments-grid-real {
    grid-template-columns: 1fr;
    gap: 14px !important;
  }
}
</style>
<!-- /HNT profile moments exact friends values v1 -->

</head>
<body class="profile-page">
<div class="app">
@include('themes.rework.partials.sidebar')
<main class="main">
@include('themes.rework.partials.topbar')
<section class="content-grid">
<div class="left-col">
<section class="card hnt-profile-hero">
<div class="hnt-profile-cover" data-rework-profile-cover style="background-image: linear-gradient(180deg, rgba(17,17,15,.08), rgba(17,17,15,.76)), url('{{ $profileCoverUrl }}');">
<div class="cover-glow"></div>
<!-- HNT profile cover upload v1 -->
@if($isOwnProfile)
<form action="{{ route('profile.media.update') }}" class="profile-cover-upload-form" data-profile-cover-upload-form enctype="multipart/form-data" method="post">
@csrf
<input name="type" type="hidden" value="cover"/>
<label aria-label="{{ __('ui.rework_profile_cover_change') }}" class="profile-share-btn profile-cover-upload-trigger" title="{{ __('ui.rework_profile_cover_change') }}">
<i aria-hidden="true" class="ph ph-image-square"></i>
<input accept="image/jpeg,image/png,image/webp" data-profile-cover-upload-input name="image" type="file"/>
</label>
</form>
@endif
<!-- /HNT profile cover upload v1 -->
</div>
<div class="hnt-profile-mainline profile-mainline-corrected">
<div class="hnt-profile-stats-left">
<div><strong>{{ $staticProfileFormatCount($profilePostsCount) }}</strong><span>Posts</span></div>
<div><strong>{{ $staticProfileFormatCount($profileFriendsCount) }}</strong><span>{{ __('ui.rework_profile_friends') }}</span></div>
</div>
<div class="hnt-profile-identity">
<div class="profile-avatar-badge" data-profile-avatar-upload-wrap>
<img alt="{{ $profileDisplayName }}" data-rework-profile-avatar src="{{ $profileAvatarUrl }}"/>
<!-- HNT profile avatar upload v1 -->
@if($isOwnProfile)
<form action="{{ route('profile.media.update') }}" class="profile-avatar-upload-form" data-profile-avatar-upload-form enctype="multipart/form-data" method="post">
@csrf
<input name="type" type="hidden" value="avatar"/>
<label aria-label="{{ __('ui.rework_profile_avatar_change') }}" class="profile-avatar-upload-trigger" title="{{ __('ui.rework_profile_avatar_change') }}">
<i aria-hidden="true" class="ph ph-image-square"></i>
<input accept="image/jpeg,image/png,image/webp" data-profile-avatar-upload-input name="image" type="file"/>
</label>
</form>
@endif
<!-- /HNT profile avatar upload v1 -->
</div>
<h1>{{ $profileDisplayName }}</h1>
<p>{{ $profileHandle }}</p>
<div class="hnt-profile-actions">
<!-- HNT profile friend action v1 -->
@if(! $isOwnProfile)
@php
    $profileFriendshipStatus = $friendship?->status;
    $profileFriendshipId = $friendship?->id;
    $profileFriendActionUrl = $profileFriendshipStatus === \App\Models\Friendship::STATUS_ACCEPTED && $profileFriendshipId
        ? route('friends.destroy', $friendship)
        : route('friends.store', $profileUser);
    $profileFriendActionMethod = $profileFriendshipStatus === \App\Models\Friendship::STATUS_ACCEPTED ? 'DELETE' : 'POST';
    $profileFriendActionLabel = match ($profileFriendshipStatus) {
        \App\Models\Friendship::STATUS_ACCEPTED => __('ui.profile_friend_remove'),
        \App\Models\Friendship::STATUS_PENDING => __('ui.profile_friend_request_sent'),
        default => __('ui.profile_add_friend'),
    };
    $profileFriendActionDisabled = $profileFriendshipStatus === \App\Models\Friendship::STATUS_PENDING || (! ($profileCanRequestFriend ?? false) && $profileFriendshipStatus !== \App\Models\Friendship::STATUS_ACCEPTED);
@endphp
<form
    action="{{ $profileFriendActionUrl }}"
    class="profile-friend-action-form"
    data-profile-friend-action
    data-add-url="{{ route('friends.store', $profileUser) }}"
    data-remove-url="{{ $profileFriendshipId ? route('friends.destroy', $friendship) : '' }}"
    data-add-label="{{ __('ui.profile_add_friend') }}"
    data-pending-label="{{ __('ui.profile_friend_request_sent') }}"
    data-remove-label="{{ __('ui.profile_friend_remove') }}"
    method="post"
>
    @csrf
    @if($profileFriendActionMethod === 'DELETE')
        @method('DELETE')
    @endif
    <button class="btn profile-friend-action-btn" type="submit" @if($profileFriendActionDisabled) disabled aria-disabled="true" @endif>{{ $profileFriendActionLabel }}</button>
</form>
@endif
<!-- /HNT profile friend action v1 -->
<!-- HNT profile edit button owner only v1 -->
@if($isOwnProfile)
<a class="btn ghost" data-profile-edit-modal-open="" href="#">{{ __('ui.profile_edit') }}</a>
@endif
<!-- /HNT profile edit button owner only v1 -->
@if($profileMessageEnabled)
<a class="btn light" href="{{ $profileMessageStartUrl }}" data-profile-message-open data-profile-message-start-url="{{ $profileMessageStartUrl }}" data-ready-label="{{ __('ui.rework_profile_message') }}" data-loading-label="{{ __('ui.rework_opening') }}">{{ __('ui.rework_profile_message') }}</a>
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
<div aria-label="{{ __('ui.profile_sections_aria') }}" class="hnt-profile-tabs" role="tablist">
<a aria-selected="true" class="active" data-profile-tab="posts" href="#profile-posts" role="tab"><i aria-hidden="true" class="ph ph-image ph-icon"></i>Posts <span>{{ $staticProfileFormatCount($profilePostsCount) }}</span></a>
<a aria-selected="false" data-profile-tab="info" href="#profile-info" role="tab"><i aria-hidden="true" class="ph ph-user ph-icon"></i>Info</a>
<a aria-selected="false" data-profile-tab="friends" href="#profile-friends" role="tab"><i aria-hidden="true" class="ph ph-chat-circle ph-icon"></i>{{ __('ui.rework_profile_friends') }} <span>{{ $staticProfileFormatCount($profileFriendsCount) }}</span></a>
<a aria-selected="false" data-profile-tab="moments" href="#profile-moments" role="tab"><i aria-hidden="true" class="ph ph-trophy ph-icon"></i>Moments <span>{{ $staticProfileFormatCount($profileMomentsCount) }}</span></a>
<a aria-selected="false" data-profile-tab="badges" href="#profile-badges" role="tab"><i aria-hidden="true" class="ph ph-medal ph-icon"></i>Badges <span>{{ $staticProfileFormatCount($profileBadgesCount) }}</span></a>
<a class="profile-create-post" data-post-composer-open="" href="#">{{ __('ui.rework_post_create_title') }}</a>
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
<strong>{{ __('ui.rework_profile_no_posts_visible') }}</strong>
<p>{{ __('ui.rework_profile_posts_empty_text', ['name' => $postsProfileName]) }}</p>
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
    data-loading-label="{{ __('ui.rework_loading') }}"
    data-ready-label="{{ __('ui.feed_load_more_posts') }}"
    data-error-label="{{ __('ui.rework_try_again') }}"
>
    <span data-profile-posts-load-more-label>{{ __('ui.feed_load_more_posts') }}</span>
</button>
<small>{{ __('ui.profile_posts_shown', ['shown' => $postsFormatCount($postsShown), 'total' => $postsFormatCount($postsTotal)]) }}</small>
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
    $infoAboutText = $infoBio ?: ($infoHeadline ?: __('ui.rework_profile_no_info'));

    $infoFormatCount = fn ($count): string => number_format((int) $count, 0, ',', '.');
    $infoDash = '—';

    $infoLevel = max(1, (int) ($profileLevel ?? ($infoProfileUser?->level ?? 1)));
    $infoXpTotal = max(0, (int) ($profileXpTotal ?? ($infoProfileUser?->xp_total ?? 0)));

    $infoPlatform = filled($infoProfile?->platform ?? null) ? $infoProfile->platform : $infoDash;
    $infoRegion = filled($infoProfile?->region ?? null) ? $infoProfile->region : $infoDash;
    $infoLanguage = filled($infoProfile?->language ?? null) ? $infoProfile->language : $infoDash;
    $infoPlaystyle = filled($infoProfile?->playstyle ?? null) ? $infoProfile->playstyle : $infoDash;
    $infoHuntRole = filled($infoProfile?->hunt_role ?? null) ? $infoProfile->hunt_role : $infoDash;
    $infoLfgLabel = (bool) ($infoProfile?->is_lfg_available ?? false) ? __('ui.rework_profile_lfg_open') : __('ui.rework_profile_lfg_not_open');

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
<div><strong>{{ $infoLanguage }}</strong><span>{{ __('ui.language') }}</span></div>
<div><strong>{{ $infoPlaystyle }}</strong><span>Spielstil</span></div>
<div><strong>{{ $infoHuntRole }}</strong><span>Rolle</span></div>
<div><strong>{{ $infoLfgLabel }}</strong><span>{{ __('ui.search') }}</span></div>
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
<span>{{ __('ui.rework_profile_friends') }}</span>
<h2>{{ __('ui.rework_profile_friends_of', ['name' => $friendsProfileName]) }}</h2>
<p>{{ __('ui.rework_profile_confirmed_friends', ['count' => $friendsFormatCount($friendsTotal)]) }}</p>
</div>
</header>

@if($friendsPreview->isEmpty())
<div class="profile-empty-state">
<i aria-hidden="true" class="ph ph-users-three ph-icon"></i>
<strong>{{ __('ui.profile_no_friends_visible') }}</strong>
<p>{{ __('ui.rework_profile_friends_empty_text') }}</p>
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
        ? __('ui.profile_mutual_friends', ['count' => $friendsFormatCount($commonCount)])
        : __('ui.rework_profile_no_mutual_friends');
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
<span><i aria-hidden="true" class="ph ph-crosshair ph-icon"></i>{{ __('ui.rework_profile_lfg_open') }}</span>
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
<section class="card profile-list-panel profile-moments-panel-real">
<header class="profile-list-head">
<div>
<span>Moments</span>
<h2>{{ __('ui.rework_profile_moments_of', ['name' => $momentsProfileName]) }}</h2>
<p>{{ __('ui.rework_profile_moments_published', ['count' => $momentsFormatCount($momentsTotal)]) }}</p>
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
@include('themes.rework.partials.right-widgets')
</section>
</main>
</div>
@include('themes.rework.feed.partials.post-modals')
@include('themes.rework.profile.partials.profile-edit-modal')
@include('themes.rework.feed.partials.settings-modal')

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
    const readyLabel = trigger.getAttribute('data-ready-label') || trigger.textContent || @json(__('ui.rework_profile_message'));
    const loadingLabel = trigger.getAttribute('data-loading-label') || @json(__('ui.rework_opening'));

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


<!-- HNT profile friend action ajax v1 -->
<style>
body.profile-page .profile-friend-action-form {
  margin: 0;
  display: inline-flex;
}

body.profile-page .profile-friend-action-form .btn[disabled] {
  opacity: .68;
  cursor: default;
}
</style>
<script>
(() => {
  if (window.__hntProfileFriendActionReady) return;
  window.__hntProfileFriendActionReady = true;

  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-profile-friend-action]');
    if (!form) return;

    event.preventDefault();

    const button = form.querySelector('button[type="submit"]');
    if (!button || button.disabled) return;

    const addUrl = form.getAttribute('data-add-url') || '';
    const addLabel = form.getAttribute('data-add-label') || 'Freund hinzufügen';
    const pendingLabel = form.getAttribute('data-pending-label') || 'Angefragt';
    const removeLabel = form.getAttribute('data-remove-label') || 'Freund entfernen';
    const methodInput = form.querySelector('input[name="_method"]');
    const currentMethod = (methodInput?.value || form.method || 'POST').toUpperCase();
    const originalLabel = button.textContent;

    button.disabled = true;
    button.setAttribute('aria-busy', 'true');

    try {
      const formData = new FormData(form);
      const response = await fetch(form.action, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf,
        },
        body: formData,
      });

      const payload = await response.json().catch(() => ({}));

      if (!response.ok) {
        throw new Error(payload?.message || 'Friend action failed.');
      }

      const status = payload.friendship_status || null;

      if (currentMethod === 'DELETE') {
        form.action = addUrl;
        form.setAttribute('data-remove-url', '');
        methodInput?.remove();
        button.textContent = addLabel;
        button.disabled = false;
        button.removeAttribute('aria-disabled');
        return;
      }

      if (status === 'accepted') {
        button.textContent = removeLabel;
        button.disabled = false;
        button.removeAttribute('aria-disabled');
        return;
      }

      button.textContent = pendingLabel;
      button.disabled = true;
      button.setAttribute('aria-disabled', 'true');
    } catch (error) {
      console.error('[HNT] Profile friend action failed:', error);
      button.textContent = originalLabel;
      button.disabled = false;
      button.removeAttribute('aria-busy');
    } finally {
      button.removeAttribute('aria-busy');
    }
  }, true);
})();
</script>
<!-- /HNT profile friend action ajax v1 -->
<!-- HNT profile cover crop upload v1 -->
<script>
(() => {
  if (window.__hntProfileCoverCropUploadReady) return;
  window.__hntProfileCoverCropUploadReady = true;

  const coverGradient = "linear-gradient(180deg, rgba(17,17,15,.08), rgba(17,17,15,.76))";
  let activeObjectUrl = null;

  const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

  const ensureModal = () => {
    let modal = document.querySelector('[data-profile-cover-crop-modal]');
    if (modal) return modal;

    modal = document.createElement('div');
    modal.className = 'profile-cover-crop-backdrop';
    modal.setAttribute('data-profile-cover-crop-modal', '');
    modal.innerHTML = `
      <section class="profile-cover-crop-dialog post-composer-modal profile-cover-crop-composer" role="dialog" aria-modal="true" aria-labelledby="profile-cover-crop-title">
        <div aria-hidden="true" class="post-composer-grip"></div>
        <header class="profile-cover-crop-head post-composer-header">
          <div class="post-composer-titleblock">
            <span class="composer-eyebrow"><span aria-hidden="true" class="composer-dot"></span>{{ __('ui.rework_profile_crop_kicker') }}</span>
            <h2 id="profile-cover-crop-title">{{ __('ui.rework_profile_cover_crop_title') }}</h2>
            <p>{{ __('ui.rework_profile_cover_crop_text') }}</p>
          </div>
          <button type="button" class="profile-cover-crop-close post-composer-close" data-profile-cover-crop-cancel aria-label="{{ __('ui.rework_profile_cover_crop_close') }}"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>
        </header>
        <div class="profile-cover-crop-stage post-composer-body">
          <div class="profile-cover-crop-frame" data-profile-cover-crop-frame>
            <img alt="" data-profile-cover-crop-image draggable="false">
          </div>
        </div>
        <footer class="profile-cover-crop-footer post-composer-footer">
          <label class="profile-cover-crop-zoom">
            <span>Zoom</span>
            <input type="range" min="1" max="3" step="0.01" value="1" data-profile-cover-crop-zoom>
          </label>
          <div class="profile-cover-crop-actions">
            <button type="button" class="composer-cancel" data-profile-cover-crop-cancel>{{ __('ui.preview_action_cancel') }}</button>
            <button type="button" class="composer-submit" data-profile-cover-crop-save>{{ __('ui.rework_apply') }}</button>
          </div>
        </footer>
      </section>
    `;
    document.body.appendChild(modal);
    return modal;
  };

  const frameSizeForCover = () => {
    const cover = document.querySelector('[data-rework-profile-cover]');
    const coverWidth = cover?.getBoundingClientRect?.().width || 1000;
    const coverHeight = cover?.getBoundingClientRect?.().height || 200;
    const aspect = clamp(coverWidth / Math.max(coverHeight, 1), 2.4, 7);

    let frameWidth = Math.min(720, Math.max(420, window.innerWidth - 96));
    let frameHeight = Math.round(frameWidth / aspect);

    const maxHeight = Math.min(260, Math.max(160, window.innerHeight - 360));
    if (frameHeight > maxHeight) {
      frameHeight = maxHeight;
      frameWidth = Math.round(frameHeight * aspect);
    }

    return { aspect, frameWidth, frameHeight };
  };

  const openCropper = (form, input, file) => new Promise((resolve) => {
    const modal = ensureModal();
    const frame = modal.querySelector('[data-profile-cover-crop-frame]');
    const img = modal.querySelector('[data-profile-cover-crop-image]');
    const zoomInput = modal.querySelector('[data-profile-cover-crop-zoom]');
    const save = modal.querySelector('[data-profile-cover-crop-save]');
    const cancels = modal.querySelectorAll('[data-profile-cover-crop-cancel]');
    const { aspect, frameWidth, frameHeight } = frameSizeForCover();

    let state = {
      naturalWidth: 0,
      naturalHeight: 0,
      frameWidth,
      frameHeight,
      baseScale: 1,
      zoom: 1,
      x: 0,
      y: 0,
      dragging: false,
      startX: 0,
      startY: 0,
      startLeft: 0,
      startTop: 0,
    };

    const cleanup = (result) => {
      modal.classList.remove('is-open');
      document.body.classList.remove('is-cover-crop-open');
      input.value = '';
      save.onclick = null;
      cancels.forEach((cancel) => {
        cancel.onclick = null;
      });
      frame.onpointerdown = null;
      frame.onpointermove = null;
      frame.onpointerup = null;
      frame.onpointercancel = null;
      zoomInput.oninput = null;

      window.setTimeout(() => {
        if (activeObjectUrl) {
          URL.revokeObjectURL(activeObjectUrl);
          activeObjectUrl = null;
        }
        resolve(result);
      }, 140);
    };

    const rendered = () => {
      const scale = state.baseScale * state.zoom;
      return {
        scale,
        width: state.naturalWidth * scale,
        height: state.naturalHeight * scale,
      };
    };

    const clampPosition = () => {
      const size = rendered();
      const minX = Math.min(0, state.frameWidth - size.width);
      const minY = Math.min(0, state.frameHeight - size.height);
      state.x = clamp(state.x, minX, 0);
      state.y = clamp(state.y, minY, 0);
    };

    const render = () => {
      clampPosition();
      const size = rendered();

      img.style.width = `${size.width}px`;
      img.style.height = `${size.height}px`;
      img.style.transform = `translate(${state.x}px, ${state.y}px)`;
    };

    const resetImage = () => {
      state.naturalWidth = img.naturalWidth;
      state.naturalHeight = img.naturalHeight;
      state.baseScale = Math.max(state.frameWidth / state.naturalWidth, state.frameHeight / state.naturalHeight);
      state.zoom = 1;
      state.x = (state.frameWidth - state.naturalWidth * state.baseScale) / 2;
      state.y = (state.frameHeight - state.naturalHeight * state.baseScale) / 2;
      zoomInput.value = '1';
      render();
    };

    frame.style.width = `${frameWidth}px`;
    frame.style.height = `${frameHeight}px`;
    frame.style.aspectRatio = `${aspect}`;
    modal.classList.add('is-open');
    document.body.classList.add('is-cover-crop-open');

    if (activeObjectUrl) {
      URL.revokeObjectURL(activeObjectUrl);
    }

    activeObjectUrl = URL.createObjectURL(file);
    img.onload = resetImage;
    img.src = activeObjectUrl;

    zoomInput.oninput = () => {
      const old = rendered();
      const centerX = state.frameWidth / 2;
      const centerY = state.frameHeight / 2;
      const relX = (centerX - state.x) / old.scale;
      const relY = (centerY - state.y) / old.scale;

      state.zoom = Number(zoomInput.value || 1);
      const next = rendered();
      state.x = centerX - relX * next.scale;
      state.y = centerY - relY * next.scale;
      render();
    };

    frame.onpointerdown = (event) => {
      event.preventDefault();
      frame.setPointerCapture(event.pointerId);
      state.dragging = true;
      state.startX = event.clientX;
      state.startY = event.clientY;
      state.startLeft = state.x;
      state.startTop = state.y;
      frame.classList.add('is-dragging');
    };

    frame.onpointermove = (event) => {
      if (!state.dragging) return;
      state.x = state.startLeft + event.clientX - state.startX;
      state.y = state.startTop + event.clientY - state.startY;
      render();
    };

    const stopDrag = () => {
      state.dragging = false;
      frame.classList.remove('is-dragging');
    };

    frame.onpointerup = stopDrag;
    frame.onpointercancel = stopDrag;

    cancels.forEach((cancel) => {
      cancel.onclick = () => cleanup(null);
    });

    save.onclick = async () => {
      save.disabled = true;
      save.textContent = @json(__('ui.rework_saving'));

      try {
        const size = rendered();
        const sourceX = clamp(-state.x / size.scale, 0, state.naturalWidth);
        const sourceY = clamp(-state.y / size.scale, 0, state.naturalHeight);
        const sourceWidth = clamp(state.frameWidth / size.scale, 1, state.naturalWidth - sourceX);
        const sourceHeight = clamp(state.frameHeight / size.scale, 1, state.naturalHeight - sourceY);

        const outputWidth = 1800;
        const outputHeight = Math.round(outputWidth / aspect);
        const canvas = document.createElement('canvas');
        canvas.width = outputWidth;
        canvas.height = outputHeight;

        const context = canvas.getContext('2d');
        context.drawImage(
          img,
          sourceX,
          sourceY,
          sourceWidth,
          sourceHeight,
          0,
          0,
          outputWidth,
          outputHeight
        );

        canvas.toBlob((blob) => {
          save.disabled = false;
          save.textContent = @json(__('ui.rework_apply'));

          if (!blob) {
            cleanup(null);
            return;
          }

          cleanup(new File([blob], 'cover-crop.jpg', { type: 'image/jpeg' }));
        }, 'image/jpeg', 0.92);
      } catch (error) {
        console.error('[HNT] Cover crop failed:', error);
        save.disabled = false;
        save.textContent = @json(__('ui.rework_apply'));
        cleanup(null);
      }
    };
  });

  document.addEventListener('change', async (event) => {
    const input = event.target.closest('[data-profile-cover-upload-input]');
    if (!input) return;

    const form = input.closest('[data-profile-cover-upload-form]');
    const trigger = form?.querySelector('.profile-cover-upload-trigger');
    const selectedFile = input.files && input.files[0];

    if (!form || !selectedFile) return;

    const croppedFile = await openCropper(form, input, selectedFile);
    if (!croppedFile) return;

    const originalTitle = trigger?.getAttribute('title') || @json(__('ui.rework_profile_cover_change'));

    if (trigger) {
      trigger.setAttribute('aria-busy', 'true');
      trigger.setAttribute('title', @json(__('ui.rework_loading')));
    }

    try {
      const formData = new FormData(form);
      formData.delete('image');
      formData.set('type', 'cover');
      formData.append('image', croppedFile);

      const response = await fetch(form.action, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData,
      });

      const payload = await response.json().catch(() => ({}));

      if (!response.ok) {
        throw new Error(payload?.message || 'Cover upload failed.');
      }

      if (payload.cover_url) {
        const cacheBust = `${payload.cover_url}${payload.cover_url.includes('?') ? '&' : '?'}v=${Date.now()}`;

        document.querySelectorAll('[data-rework-profile-cover]').forEach((cover) => {
          cover.style.backgroundImage = `${coverGradient}, url('${cacheBust}')`;
          cover.style.backgroundSize = '100% 100%';
          cover.style.backgroundPosition = 'center center';
        });
      }
    } catch (error) {
      console.error('[HNT] Profile cover crop upload failed:', error);
      window.alert(@json(__('ui.rework_profile_cover_upload_failed')));
    } finally {
      if (trigger) {
        trigger.removeAttribute('aria-busy');
        trigger.setAttribute('title', originalTitle);
      }
    }
  }, true);
})();
</script>
<!-- /HNT profile cover crop upload v1 -->


<!-- HNT profile avatar crop upload v1 -->
<script>
(() => {
  if (window.__hntProfileAvatarCropUploadReady) return;
  window.__hntProfileAvatarCropUploadReady = true;

  let activeAvatarObjectUrl = null;

  const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

  const ensureModal = () => {
    let modal = document.querySelector('[data-profile-avatar-crop-modal]');
    if (modal) return modal;

    modal = document.createElement('div');
    modal.className = 'profile-avatar-crop-backdrop';
    modal.setAttribute('data-profile-avatar-crop-modal', '');
    modal.innerHTML = `
      <section class="profile-avatar-crop-dialog post-composer-modal profile-avatar-crop-composer" role="dialog" aria-modal="true" aria-labelledby="profile-avatar-crop-title">
        <div aria-hidden="true" class="post-composer-grip"></div>
        <header class="profile-avatar-crop-head post-composer-header">
          <div class="post-composer-titleblock">
            <span class="composer-eyebrow"><span aria-hidden="true" class="composer-dot"></span>{{ __('ui.rework_profile_crop_kicker') }}</span>
            <h2 id="profile-avatar-crop-title">{{ __('ui.rework_profile_avatar_crop_title') }}</h2>
            <p>{{ __('ui.rework_profile_avatar_crop_text') }}</p>
          </div>
          <button type="button" class="profile-avatar-crop-close post-composer-close" data-profile-avatar-crop-cancel aria-label="{{ __('ui.rework_profile_avatar_crop_close') }}">
            <i aria-hidden="true" class="ph ph-x ph-icon"></i>
          </button>
        </header>
        <div class="profile-avatar-crop-stage post-composer-body">
          <div class="profile-avatar-crop-frame" data-profile-avatar-crop-frame>
            <img alt="" data-profile-avatar-crop-image draggable="false">
          </div>
        </div>
        <footer class="profile-avatar-crop-footer post-composer-footer">
          <label class="profile-avatar-crop-zoom">
            <span>Zoom</span>
            <input type="range" min="1" max="3" step="0.01" value="1" data-profile-avatar-crop-zoom>
          </label>
          <div class="profile-avatar-crop-actions">
            <button type="button" class="composer-cancel" data-profile-avatar-crop-cancel>{{ __('ui.preview_action_cancel') }}</button>
            <button type="button" class="composer-submit" data-profile-avatar-crop-save>{{ __('ui.rework_apply') }}</button>
          </div>
        </footer>
      </section>
    `;
    document.body.appendChild(modal);
    return modal;
  };

  const frameSizeForAvatar = () => {
    const maxAvailable = Math.min(window.innerWidth - 96, window.innerHeight - 330);
    const size = Math.round(clamp(maxAvailable, 230, 320));
    return { frameWidth: size, frameHeight: size };
  };

  const openCropper = (input, file) => new Promise((resolve) => {
    const modal = ensureModal();
    const frame = modal.querySelector('[data-profile-avatar-crop-frame]');
    const img = modal.querySelector('[data-profile-avatar-crop-image]');
    const zoomInput = modal.querySelector('[data-profile-avatar-crop-zoom]');
    const save = modal.querySelector('[data-profile-avatar-crop-save]');
    const cancels = modal.querySelectorAll('[data-profile-avatar-crop-cancel]');
    const { frameWidth, frameHeight } = frameSizeForAvatar();

    let state = {
      naturalWidth: 0,
      naturalHeight: 0,
      frameWidth,
      frameHeight,
      baseScale: 1,
      zoom: 1,
      x: 0,
      y: 0,
      dragging: false,
      startX: 0,
      startY: 0,
      startLeft: 0,
      startTop: 0,
    };

    const cleanup = (result) => {
      modal.classList.remove('is-open');
      document.body.classList.remove('is-avatar-crop-open');
      input.value = '';
      save.onclick = null;
      cancels.forEach((cancel) => {
        cancel.onclick = null;
      });
      frame.onpointerdown = null;
      frame.onpointermove = null;
      frame.onpointerup = null;
      frame.onpointercancel = null;
      zoomInput.oninput = null;

      window.setTimeout(() => {
        if (activeAvatarObjectUrl) {
          URL.revokeObjectURL(activeAvatarObjectUrl);
          activeAvatarObjectUrl = null;
        }
        resolve(result);
      }, 120);
    };

    const rendered = () => {
      const scale = state.baseScale * state.zoom;
      return {
        scale,
        width: state.naturalWidth * scale,
        height: state.naturalHeight * scale,
      };
    };

    const clampPosition = () => {
      const size = rendered();
      const minX = Math.min(0, state.frameWidth - size.width);
      const minY = Math.min(0, state.frameHeight - size.height);
      state.x = clamp(state.x, minX, 0);
      state.y = clamp(state.y, minY, 0);
    };

    const render = () => {
      clampPosition();
      const size = rendered();

      img.style.width = `${size.width}px`;
      img.style.height = `${size.height}px`;
      img.style.transform = `translate(${state.x}px, ${state.y}px)`;
    };

    const resetImage = () => {
      state.naturalWidth = img.naturalWidth;
      state.naturalHeight = img.naturalHeight;
      state.baseScale = Math.max(state.frameWidth / state.naturalWidth, state.frameHeight / state.naturalHeight);
      state.zoom = 1;
      state.x = (state.frameWidth - state.naturalWidth * state.baseScale) / 2;
      state.y = (state.frameHeight - state.naturalHeight * state.baseScale) / 2;
      zoomInput.value = '1';
      render();
    };

    frame.style.width = `${frameWidth}px`;
    frame.style.height = `${frameHeight}px`;

    modal.classList.add('is-open');
    document.body.classList.add('is-avatar-crop-open');

    if (activeAvatarObjectUrl) {
      URL.revokeObjectURL(activeAvatarObjectUrl);
    }

    activeAvatarObjectUrl = URL.createObjectURL(file);
    img.onload = resetImage;
    img.src = activeAvatarObjectUrl;

    zoomInput.oninput = () => {
      const old = rendered();
      const centerX = state.frameWidth / 2;
      const centerY = state.frameHeight / 2;
      const relX = (centerX - state.x) / old.scale;
      const relY = (centerY - state.y) / old.scale;

      state.zoom = Number(zoomInput.value || 1);
      const next = rendered();
      state.x = centerX - relX * next.scale;
      state.y = centerY - relY * next.scale;
      render();
    };

    frame.onpointerdown = (event) => {
      event.preventDefault();
      frame.setPointerCapture(event.pointerId);
      state.dragging = true;
      state.startX = event.clientX;
      state.startY = event.clientY;
      state.startLeft = state.x;
      state.startTop = state.y;
      frame.classList.add('is-dragging');
    };

    frame.onpointermove = (event) => {
      if (!state.dragging) return;
      state.x = state.startLeft + event.clientX - state.startX;
      state.y = state.startTop + event.clientY - state.startY;
      render();
    };

    const stopDrag = () => {
      state.dragging = false;
      frame.classList.remove('is-dragging');
    };

    frame.onpointerup = stopDrag;
    frame.onpointercancel = stopDrag;

    cancels.forEach((cancel) => {
      cancel.onclick = () => cleanup(null);
    });

    save.onclick = async () => {
      save.disabled = true;
      save.textContent = @json(__('ui.rework_saving'));

      try {
        const size = rendered();
        const sourceX = clamp(-state.x / size.scale, 0, state.naturalWidth);
        const sourceY = clamp(-state.y / size.scale, 0, state.naturalHeight);
        const sourceWidth = clamp(state.frameWidth / size.scale, 1, state.naturalWidth - sourceX);
        const sourceHeight = clamp(state.frameHeight / size.scale, 1, state.naturalHeight - sourceY);

        const outputSize = 800;
        const canvas = document.createElement('canvas');
        canvas.width = outputSize;
        canvas.height = outputSize;

        const context = canvas.getContext('2d');
        context.drawImage(
          img,
          sourceX,
          sourceY,
          sourceWidth,
          sourceHeight,
          0,
          0,
          outputSize,
          outputSize
        );

        canvas.toBlob((blob) => {
          save.disabled = false;
          save.textContent = @json(__('ui.rework_apply'));

          if (!blob) {
            cleanup(null);
            return;
          }

          cleanup(new File([blob], 'avatar-crop.jpg', { type: 'image/jpeg' }));
        }, 'image/jpeg', 0.92);
      } catch (error) {
        console.error('[HNT] Avatar crop failed:', error);
        save.disabled = false;
        save.textContent = @json(__('ui.rework_apply'));
        cleanup(null);
      }
    };
  });

  document.addEventListener('change', async (event) => {
    const input = event.target.closest('[data-profile-avatar-upload-input]');
    if (!input) return;

    const form = input.closest('[data-profile-avatar-upload-form]');
    const trigger = form?.querySelector('.profile-avatar-upload-trigger');
    const selectedFile = input.files && input.files[0];

    if (!form || !selectedFile) return;

    const croppedFile = await openCropper(input, selectedFile);
    if (!croppedFile) return;

    const originalTitle = trigger?.getAttribute('title') || @json(__('ui.rework_profile_avatar_change'));

    if (trigger) {
      trigger.setAttribute('aria-busy', 'true');
      trigger.setAttribute('title', @json(__('ui.rework_loading')));
    }

    try {
      const formData = new FormData(form);
      formData.delete('image');
      formData.set('type', 'avatar');
      formData.append('image', croppedFile);

      const response = await fetch(form.action, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData,
      });

      const payload = await response.json().catch(() => ({}));

      if (!response.ok) {
        throw new Error(payload?.message || 'Avatar upload failed.');
      }

      if (payload.avatar_url) {
        const cacheBust = `${payload.avatar_url}${payload.avatar_url.includes('?') ? '&' : '?'}v=${Date.now()}`;

        document.querySelectorAll('[data-rework-profile-avatar]').forEach((avatar) => {
          avatar.src = cacheBust;
        });
      }
    } catch (error) {
      console.error('[HNT] Profile avatar crop upload failed:', error);
      window.alert(@json(__('ui.rework_profile_avatar_upload_failed')));
    } finally {
      if (trigger) {
        trigger.removeAttribute('aria-busy');
        trigger.setAttribute('title', originalTitle);
      }
    }
  }, true);
})();
</script>
<!-- /HNT profile avatar crop upload v1 -->

</body>
</html>
