@php
    $teamManageJsVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-cups/team-manage.js')) ?: time();
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1" name="viewport"/>
<meta name="robots" content="noindex,nofollow"/>
<title>HNT.ROCKS — Night Ravens verwalten</title>
<base href="{{ asset('assets/themes/hnt_preview/dashboard-feed/') }}/"/>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&amp;display=swap" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/team-manage-original/common.css') }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v=20260710-1" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/cup-detail.css') }}?v=20260717-1" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/team-manage-original/part1.css') }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/team-manage-original/part2.css') }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/team-manage-original/part3.css') }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/team-manage-original/part4.css') }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/team-manage-original/part5.css') }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/team-manage-original/part6.css') }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/cup-detail-theme-live-red.css') }}" rel="stylesheet"/>
</head>
<body data-page="team-manage">
@include('themes.hnt_preview.cups.demo.demo-svg')
<svg aria-hidden="true" class="svg-defs">
<symbol id="i-edit" viewBox="0 0 24 24"><path d="m4 20 4.2-1 10.4-10.4a2 2 0 0 0 0-2.8l-.4-.4a2 2 0 0 0-2.8 0L5 15.8 4 20Z"></path><path d="m13.8 7 3.2 3.2"></path></symbol>
</svg>
<main class="app-shell team-manage-page-shell">
@include('themes.hnt_preview.cups.demo.team-manage.demo-team-manage-header')
@include('themes.hnt_preview.cups.demo.team-manage.demo-team-manage-stage')
@include('themes.hnt_preview.cups.demo.team-manage.demo-team-manage-confirm')
<div class="toast" id="toast"></div>
</main>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v=20260710-1"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-cups/team-manage.js') }}?v={{ $teamManageJsVersion }}"></script>
</body>
</html>
