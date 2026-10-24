@php
 $nextActivePage='hall-of-fame'; $nextPageTitle='Hall of Fame'; $viewer=auth()->user();
 $viewerCosmetics=\App\Support\CrownCosmetics::forUser($viewer);
 $prestige=app(\App\Services\HunterPrestigeService::class)->status($viewer);
 $prestigeProgress=app(\App\Services\GamificationService::class)->progressPercent($viewer);
@endphp
<!doctype html><html lang="{{ app()->getLocale() }}"><head>
@include('next.partials.shell.head')
<link rel="stylesheet" href="/build/next/hntv3-js/next-hall-of-fame.css?v=20261021-demo-font-match2">
<script defer src="/build/next/hntv3-js/next-hall-of-fame.js?v=20261021-demo-1to1"></script>
</head>
<body class="hnt-cups-shell" data-hnt-next-live-posts="true" data-next-viewer-name="{{ $viewer->name }}"
data-next-viewer-avatar="{{ $viewer->avatarUrl() }}" data-next-viewer-frame="{{ $viewerCosmetics['avatar_frame_class'] }}"
data-next-viewer-name-effect="{{ $viewerCosmetics['username_effect_class'] }}" data-next-like-effect="{{ $viewerCosmetics['like_effect_key'] }}">
@include('next.partials.shell.icons')
<script>window.HNTPrestigeData=@json($prestige);window.HNTPrestigeEnabled=@json(config('hnt_prestige.enabled'));window.HNTPrestigeWords=@json(trans('prestige'));window.HNTPrestigeProgress=@json($prestigeProgress);</script>
@include('next.partials.shell.sidebar')
@include('next.partials.shell.topbar')
<main class="main-content feed-main hof-page" id="hofMain" data-hof-total="{{ $topFive->count() }}" data-hof-points="{{ __('next_hof.points') }}" data-hof-tokens="{{ __('next_hof.tokens') }}" data-hof-kills="{{ __('next_hof.kills') }}" data-hof-count-copy="{{ __('next_hof.count_copy') }}"><div class="hof-wrap"><header class="feed-page-title hof-heading"><div class="hof-heading-copy"><div class="feed-kicker hof-kicker"><span class="feed-live-signal" aria-hidden="true"></span> HNT.ROCKS / CUPS / HALL OF FAME</div><h1>Hall of Fame<span class="title-ember">.</span></h1><p>{{ __('next_hof.subtitle') }}</p></div><a class="hof-back-button" href="{{ \App\Support\Hntv3Navigation::url('next.cups') }}"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-left"></use></svg> {{ __('next_hof.back_to_cups') }}</a></header><div class="hof-overview-strip"><div><span><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-trophy"></use></svg> {{ __('next_hof.completed_upper') }}</span><strong>{{ str_pad($cups->count(),2,"0",STR_PAD_LEFT) }}</strong></div><div><span><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-seal-check"></use></svg> {{ __('next_hof.podiums_upper') }}</span><strong>{{ str_pad($winnerCount,2,"0",STR_PAD_LEFT) }}</strong></div><div><span><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-users-three"></use></svg> {{ __('next_hof.finalists_upper') }}</span><strong>{{ str_pad($finalistCount,2,"0",STR_PAD_LEFT) }}</strong></div><div class="hof-current-cup"><span><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-clock"></use></svg> {{ __('next_hof.archive_upper') }} / {{ $cup?->ends_at?->translatedFormat('d.m.Y') ?? '—' }}</span><strong>{{ $cup?->title ?? __('next_hof.none') }}</strong></div></div>@if($cups->count()>1)
<nav class="hof-archive-switch" aria-label="{{ __('next_hof.select_cup') }}">
<span>{{ __('next_hof.select_cup') }}</span>
@foreach($cups as $item)
<a href="{{ route('next.hall-of-fame',['cup'=>$item->slug]) }}"
class="{{ $cup?->id===$item->id ? 'is-active' : '' }}">{{ $item->title }}</a>
@endforeach
</nav>
@endif
@if($cup)
<section class="hof-leaderboard" aria-label="Hall-of-Fame-{{ __('next_hof.leaderboard') }}"><div class="hof-section-head"><div><span class="hof-section-eyebrow">{{ __('next_hof.legends_upper') }}</span><h2>{{ __('next_hof.top_three') }}<span class="title-ember">.</span></h2><p>{{ $cup?->title ?? __('next_hof.none') }} <span aria-hidden="true">·</span> {{ __('next_hof.solo_leaderboard') }} <span aria-hidden="true">·</span> {{ $cup?->ends_at?->translatedFormat('d.m.Y') ?? '—' }}</p></div><div class="hof-cup-chip"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-shield-check"></use></svg> {{ __('next_hof.archive_chip') }}</div></div><div class="hof-podium-grid">@foreach($topThree as $index => $team)
@php($rank=$index+1)
<article class="hof-podium-card hof-place-{{ $rank }}" aria-label="{{ __('next_hof.rank') }} {{ $rank }}: {{ $team->owner?->name ?: $team->name }}">
<div class="hof-podium-top">
@if($team->owner?->username)
<a class="hof-portrait hof-portrait-{{ $rank }}" href="{{ route('profile.public.hntv3',$team->owner) }}" aria-label="{{ $team->owner->name }} – {{ __('next_hof.profile') }}"><img src="{{ $team->owner->avatarUrl() }}" alt="" loading="lazy"></a>
@else
<span class="hof-portrait hof-portrait-{{ $rank }}" aria-hidden="true">{{ mb_substr($team->name,0,1) }}</span>
@endif
<div class="hof-podium-person"><div class="hof-podium-name">{{ $team->owner?->name ?: $team->name }}</div>
<div class="hof-podium-rank">#{{ $rank }} {{ $rank===1 ? __('next_hof.champion') : ($rank===2 ? __('next_hof.second') : __('next_hof.third')) }}</div></div>
<div class="hof-podium-medal"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="{{ $rank===3 ? '#ph-seal-check' : '#ph-trophy' }}"></use></svg></div></div>
<div class="hof-podium-score">
<div data-metric="points"><span>{{ __('next_hof.points_upper') }}</span><strong>{{ $team->points_total }}</strong></div>
<div data-metric="tokens"><span>{{ __('next_hof.tokens_upper') }}</span><strong>{{ $team->bounty_tokens_total }}</strong></div>
<div data-metric="kills"><span>{{ __('next_hof.kills_upper') }}</span><strong>{{ $team->kills_total }}</strong></div></div>
<div class="hof-rank-track"><span></span></div>
<div class="hof-podium-under"><span>{{ $cup->title }}</span><span>{{ $team->submissions_approved_count }} {{ __('next_hof.matches') }}</span></div>
</article>
@endforeach
@if($topThree->isEmpty())<p class="hof-empty">{{ __('next_hof.no_results') }}</p>@endif
</div><div class="hof-table-heading"><div><span class="hof-section-eyebrow">{{ __('next_hof.final_board_upper') }}</span><h2>{{ __('next_hof.leaderboard') }}<span class="title-ember">.</span></h2><p>{{ __('next_hof.winner_explain') }}</p></div><div class="hof-view-hint">{{ __('next_hof.sort_hint') }}</div></div><div class="hof-toolbar"><div class="hof-segments" role="group" aria-label="Sortieren nach"><button type="button" data-hof-metric="points" class="is-active" aria-pressed="true">{{ __('next_hof.points') }}</button><button type="button" data-hof-metric="tokens" aria-pressed="false">{{ __('next_hof.tokens') }}</button><button type="button" data-hof-metric="kills" aria-pressed="false">{{ __('next_hof.kills') }}</button></div><div class="hof-tools-right"><div class="hof-segments hof-scope" role="group" aria-label="Angezeigte Platzierungen"><button type="button" data-hof-scope="3" aria-pressed="false">{{ __('next_hof.top_3') }}</button><button type="button" data-hof-scope="5" class="is-active" aria-pressed="true">{{ __('next_hof.top_5') }}</button></div><a class="hof-view-cup" href="{{ route('cups.show',$cup) }}"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg> {{ __('next_hof.view_cup') }}</a></div></div><div class="hof-table-scroll"><div class="hof-rank-list" id="hofRankList"><div class="hof-rank-columns"><span>{{ __('next_hof.rank_upper') }}</span><span>{{ __('next_hof.hunter_upper') }}</span><span data-hof-col="points">{{ __('next_hof.points_upper') }}</span><span data-hof-col="tokens">{{ __('next_hof.tokens_upper') }}</span><span data-hof-col="kills">{{ __('next_hof.kills_upper') }}</span><span>{{ __('next_hof.matches_upper') }}</span></div><div id="hofRows">@foreach($topFive as $index => $team)
@php($rank=$index+1)
<div class="hof-rank-row {{ $rank<=3 ? 'hof-rank-row-winner' : '' }}" data-hof-row
data-rank="{{ $rank }}" data-points="{{ $team->points_total }}"
data-tokens="{{ $team->bounty_tokens_total }}" data-kills="{{ $team->kills_total }}"
data-matches="{{ $team->submissions_approved_count }}">
<span class="hof-row-rank">#{{ $rank }}</span>
<div class="hof-row-player">
@if($team->owner?->username)
<a class="hof-row-avatar" href="{{ route('profile.public.hntv3',$team->owner) }}" aria-label="{{ $team->owner->name }} – {{ __('next_hof.profile') }}"><img src="{{ $team->owner->avatarUrl() }}" alt="" loading="lazy"></a>
@else
<span class="hof-row-avatar" aria-hidden="true">{{ mb_substr($team->name,0,1) }}</span>
@endif
<div>
@if($team->owner?->username)<a class="hof-row-player-name" href="{{ route('profile.public.hntv3',$team->owner) }}"><strong>{{ $team->owner->name }}</strong></a>
@else<strong>{{ $team->name }}</strong>@endif
<small>{{ $rank===1 ? __('next_hof.champion') : ($rank===2 ? __('next_hof.second') : ($rank===3 ? __('next_hof.third') : __('next_hof.finalist'))) }}</small>
</div></div>
<strong class="hof-row-num" data-stat="points">{{ $team->points_total }}</strong>
<strong class="hof-row-num" data-stat="tokens">{{ $team->bounty_tokens_total }}</strong>
<strong class="hof-row-num" data-stat="kills">{{ $team->kills_total }}</strong>
<span class="hof-row-num">{{ $team->submissions_approved_count }}</span>
</div>
@endforeach
@if($topFive->isEmpty())<p class="hof-empty">{{ __('next_hof.no_results') }}</p>@endif
</div></div></div><div class="hof-list-foot"><span id="hofShownCount">{{ $topFive->count() }} {{ __('next_hof.of') }} {{ $topFive->count() }} {{ __('next_hof.finalists') }}</span><span><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-shield-check"></use></svg> {{ __('next_hof.official_order') }}</span></div></section>
@else
<section class="hof-empty-section"><svg class="ph" viewBox="0 0 256 256"><use href="#ph-trophy"/></svg><h2>{{ __('next_hof.none') }}</h2><p>{{ __('next_hof.no_cups_yet') }}</p></section>
@endif
<div class="hof-archive-note"><span class="hof-note-icon"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-shield-check"></use></svg></span><div><strong>{{ __('next_hof.archive_note_title') }}</strong><p>{{ __('next_hof.archive_note_real') }}</p></div><a href="{{ \App\Support\Hntv3Navigation::url('next.cups') }}">{{ __('next_hof.all_cups') }} <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></a></div></div></main>
@include('next.partials.shell.utilities')
</body></html>
