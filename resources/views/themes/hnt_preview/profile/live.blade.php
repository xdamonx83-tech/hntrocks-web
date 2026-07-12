@php
    $viewer = auth()->user();
    $profile = $profileUser->profile;
    $profileDisplayName = trim((string) ($profileUser->name ?: $profileUser->username ?: 'HNT Hunter'));
    $profileHandle = $profileUser->username ? '@'.$profileUser->username : '@hunter';
    $profileAvatarUrl = $profileUser->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $profileCoverUrl = $profileUser->coverUrl() ?: asset('assets/vikinger/img/default-cover.svg');
    $profileCoverDisplayMode = $profile?->coverDisplayMode() ?? \App\Models\UserProfile::COVER_DISPLAY_AUTO;
    $profileBio = trim((string) ($profile?->bio ?: $profile?->headline ?: 'Noch keine Profilbeschreibung vorhanden.'));
    $profilePostsCount = (int) ($profileUser->visible_feed_posts_count ?? ($profilePostsTotal ?? 0));
    $profileFriendsTotal = (int) ($profileFriendsCount ?? 0);
    $profileMomentsCount = (int) ($profileUser->moments_count ?? 0);
    $profileBadgesCount = (int) ($profileUser->badges_count ?? 0);
    $profileRocks = (int) ($profileUser->crownWallet?->balance ?? 0);
    $profileXpTotal = max(0, (int) ($profileUser->xp_total ?? 0));
    $profileGamification = app(\App\Services\GamificationService::class);
    $profileLevel = $profileGamification->levelForXp($profileXpTotal);
    $profileCurrentLevelXp = $profileGamification->xpForCurrentLevel($profileLevel);
    $profileNextLevelXp = $profileGamification->xpForNextLevel($profileLevel);
    $profileLevelProgress = (int) min(100, max(0, round((($profileXpTotal - $profileCurrentLevelXp) / max(1, $profileNextLevelXp - $profileCurrentLevelXp)) * 100)));
    $profileXpRemaining = max(0, $profileNextLevelXp - $profileXpTotal);
    $profileJoinedLabel = $profileUser->created_at?->translatedFormat('M Y') ?: '—';
    $profileIsOnline = $profileUser->allowsOnlineStatusVisibility($viewer) && $profileUser->isOnline();
    $profileMessageUrl = (! $isOwnProfile && $profileCanMessage && \Illuminate\Support\Facades\Route::has('messages.with-user'))
        ? route('messages.with-user', $profileUser)
        : null;
    $profileFormatCount = static fn ($value): string => number_format((int) $value, 0, ',', '.');
    $profileExternalUrl = static function (?string $value, array $allowedHosts): ?string {
        $url = trim((string) $value);

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return null;
        }

        foreach ($allowedHosts as $allowedHost) {
            if ($host === $allowedHost || str_ends_with($host, '.'.$allowedHost)) {
                return $url;
            }
        }

        return null;
    };
    $profileSteamUrl = $profileExternalUrl($profile?->steam_url, ['steamcommunity.com', 'steampowered.com']);
    $profileTwitchUrl = $profileExternalUrl($profile?->twitch_url, ['twitch.tv']);
    $profileYoutubeUrl = $profileExternalUrl($profile?->youtube_url, ['youtube.com', 'youtu.be']);
    $profileTwitchChannel = null;

    if ($profileTwitchUrl) {
        $profileTwitchPath = trim((string) parse_url($profileTwitchUrl, PHP_URL_PATH), '/');
        $profileTwitchCandidate = explode('/', $profileTwitchPath)[0] ?? '';

        if (preg_match('/^[A-Za-z0-9_]{4,25}$/', $profileTwitchCandidate)) {
            $profileTwitchChannel = strtolower($profileTwitchCandidate);
        }
    }

    $profileTwitchParent = request()->getHost();
    $profileHasSocialLinks = (bool) ($profileSteamUrl || $profileTwitchUrl || $profileYoutubeUrl);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() === 'en' ? 'en' : 'de' }}">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1" name="viewport"/>
<meta content="noindex,nofollow,noarchive" name="robots"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>{{ $profileDisplayName }} · HNT.ROCKS</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&amp;display=swap" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/common.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/feed.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-profile/profile-live.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-profile/profile-live.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-profile/profile-cover-peek.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-profile/profile-cover-peek.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-profile/profile-social-twitch.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-profile/profile-social-twitch.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-feed.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-feed.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-feed-polish.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-feed-polish.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-feed-comments.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-feed-comments.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-feed-media-viewer.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-feed-media-viewer.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-feed-compose.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-feed-compose.css')) ?: time() }}" rel="stylesheet"/>
</head>
<body data-page="profile">
@include('themes.hnt_preview.partials.icons')
<div aria-hidden="true" class="feed-shell" hidden style="display:none!important"></div>
<main class="app-shell profile-page-shell">
@include('themes.hnt_preview.partials.header')
@include('themes.hnt_preview.profile.exact.heading')
<section class="profile-page-layout">
@include('themes.hnt_preview.profile.exact.quests')
<article class="profile-page-card">
@include('themes.hnt_preview.profile.exact.card-left')
<div class="profile-page-main">
@include('themes.hnt_preview.profile.exact.summary')
<section class="social-feed-card profile-post-feed">
@include('themes.hnt_preview.profile.exact.feed-head')
@include('themes.hnt_preview.profile.exact.feed-posts')
@include('themes.hnt_preview.profile.exact.feed-info')
@include('themes.hnt_preview.profile.exact.feed-friends')
@include('themes.hnt_preview.profile.exact.feed-moments')
@include('themes.hnt_preview.profile.exact.feed-badges')
@include('themes.hnt_preview.profile.exact.feed-twitch')
</section>
</div>
</article>
</section>
@include('themes.hnt_preview.profile.exact.composer')
@include('themes.hnt_preview.profile.exact.comments')
<div class="toast" id="toast"></div>
</main>
<script>window.HNT_DASHBOARD_HEADER_ENDPOINT = window.location.href; window.HNT_PROFILE_LIVE = true;</script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
@if(auth()->check())
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js')) ?: time() }}"></script>
@endif
<script src="{{ asset('assets/themes/hnt_preview/dashboard-profile/profile-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-profile/profile-live.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-profile/profile-cover-peek.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-profile/profile-cover-peek.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-profile/profile-social-twitch.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-profile/profile-social-twitch.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-profile/profile-live-compose.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-profile/profile-live-compose.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-profile/profile-live-actions.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-profile/profile-live-actions.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-feed-comments.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-feed-comments.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-feed-media-viewer.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-feed-media-viewer.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-feed-comment-upload-progress.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-feed-comment-upload-progress.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-feed-comment-media-v3.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-feed-comment-media-v3.js')) ?: time() }}"></script>
</body>
</html>
