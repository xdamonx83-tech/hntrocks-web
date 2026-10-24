<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
@include('next.partials.shell.head')
<meta name="robots" content="noindex,nofollow,noarchive">
<link rel="stylesheet" href="/build/next/hntv3-js/news-editor-hntv3.css?v=20261024-editor1">
<script defer src="/build/next/hntv3-js/news-editor-hntv3.js?v=20261024-editor1"></script>
</head>
@php
    $viewerCosmetics = \App\Support\CrownCosmetics::forUser($viewer);
    $prestige = app(\App\Services\HunterPrestigeService::class)->status($viewer);
    $prestigeProgress = app(\App\Services\GamificationService::class)->progressPercent($viewer);
@endphp
<body class="hnt-news-editor-page" data-hnt-next-live-posts="true"
    data-next-viewer-name="{{ $viewer->name }}"
    data-next-viewer-avatar="{{ $viewer->avatarUrl() }}"
    data-next-viewer-profile="{{ $viewer->username ? url('/u/'.$viewer->username) : '' }}"
    data-next-viewer-frame="{{ $viewerCosmetics['avatar_frame_class'] }}"
    data-next-viewer-name-effect="{{ $viewerCosmetics['username_effect_class'] }}">
@include('next.partials.shell.icons')
<script>
window.HNTPrestigeData = @json($prestige);
window.HNTPrestigeEnabled = @json(config('hnt_prestige.enabled'));
window.HNTPrestigeWords = @json(trans('prestige'));
window.HNTPrestigeProgress = @json($prestigeProgress);
</script>
@include('next.partials.shell.sidebar')
@include('next.partials.shell.topbar')
<main class="main-content feed-main hnt-editor-workspace" id="hntNewsEditor">
 <div class="hnt-editor-shell">
  <div class="feed-page-title hnt-editor-heading">
    <div>
      <div class="feed-kicker"><span class="feed-live-signal" aria-hidden="true"></span> HNT.ROCKS / NEWS / {{ $words['editor'] }}</div>
      <h1>{{ $articleId ? $words['edit'] : $words['heading'] }}<span class="title-ember">.</span></h1>
      <p>{{ $words['intro'] }}</p>
    </div>
    <div class="hnt-editor-header-actions">
      <a class="hnt-editor-button" href="{{ route('news.overview.locale', ['locale'=>$locale]) }}">
        <svg class="ph" aria-hidden="true" viewBox="0 0 256 256"><use href="#ph-arrow-left"></use></svg> {{ $words['back'] }}
      </a>
      <a class="hnt-editor-button" href="{{ url('/news/manage') }}">
        <svg class="ph" aria-hidden="true" viewBox="0 0 256 256"><use href="#ph-files"></use></svg> {{ $words['manage'] }}
      </a>
    </div>
  </div>
  <div class="hnt-editor-notice">
    <svg class="ph" aria-hidden="true" viewBox="0 0 256 256"><use href="#ph-info"></use></svg>
    <span>{{ $words['tip'] }}</span>
  </div>
  <section class="hnt-editor-frame-shell" aria-label="{{ $words['workspace'] }}">
    <div class="hnt-editor-frame-meta"><span class="hnt-editor-live-dot"></span> {{ $words['workspace'] }} <span class="hnt-editor-architecture">TIPTAP · MEDIA / V2 · HNT.ROCKS</span></div>
    <iframe
      title="{{ $words['workspace'] }}"
      id="hntNewsEditorFrame"
      src="{{ $frameUrl }}"
      loading="eager"
      referrerpolicy="same-origin"
      allow="clipboard-write; fullscreen"
      data-base-url="{{ url('/next/news/editor') }}"
      data-editor-style="/build/next/hntv3-js/news-editor-frame.css?v=20261024-editor1">
    </iframe>
  </section>
 </div>
</main>
@include('next.partials.shell.utilities')
</html>
