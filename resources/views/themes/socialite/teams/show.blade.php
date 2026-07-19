@php
    $demoCssVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-teams/team-detail-demo.css')) ?: time();
    $demoThemeVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-teams/team-detail-theme.css')) ?: time();
    $liveCssVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-teams/team-detail-live.css')) ?: time();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1" name="viewport"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<meta content="noindex,nofollow" name="robots"/>
<title>{{ $team->name }} · HNT.ROCKS</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&amp;display=swap" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/common.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/feed.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-teams/team-detail-demo.css') }}?v={{ $demoCssVersion }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-teams/team-detail-theme.css') }}?v={{ $demoThemeVersion }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-teams/team-detail-live.css') }}?v={{ $liveCssVersion }}" rel="stylesheet"/>
</head>
<body data-page="team-detail">
@include('themes.hnt_preview.partials.icons')
<main class="app-shell team-detail-page-shell">
@include('themes.hnt_preview.partials.header')
@if(session('status'))<div class="team-detail-flash" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="team-detail-flash is-error" role="alert">{{ $errors->first() }}</div>@endif
@include('themes.socialite.teams.demo-overview')
<div class="toast" id="toast"></div>
</main>
<script>
window.HNT_DASHBOARD_HEADER_ENDPOINT = @json(route('feed.index'));
window.HNT_TEAM_DETAIL_SECTION = @json($activeTeamSection ?? 'overview');
window.HNT_PREVIEW_LOCALE = @json(str_replace('_', '-', app()->getLocale()));
window.HNT_PREVIEW_USER_ID = @json(auth()->id());
window.HNT_PREVIEW_LIVE_BADGES = {
 endpoint: @json(route('socialite.header.live-badges')),
 notificationsEndpoint: @json(route('socialite.header.notifications')),
 messagesEndpoint: @json(route('socialite.header.messages')),
 friendRequestsEndpoint: @json(route('socialite.header.friend-requests')),
 interval:8000,shellInterval:5000,chatTabInterval:4500
};
</script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-teams/team-detail.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-teams/team-detail.js')) ?: time() }}" defer></script>
</body>
</html>
