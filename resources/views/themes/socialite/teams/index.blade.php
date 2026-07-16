@php
    $viewer = auth()->user();
    $stats = $overviewStats ?? ['all' => 0, 'mine' => 0, 'recruiting' => 0, 'new_this_week' => 0, 'members' => 0];
    $filters = $filters ?? [];
    $activeSort = (string) ($filters['sort'] ?? request('sort', 'newest'));
    $activeView = $viewMode ?? 'discover';
    $isRecruiting = $activeView === 'recruiting' || ! empty($filters['recruiting']) || request()->boolean('recruiting');
    $searchValue = (string) ($filters['q'] ?? request('q', ''));
    $platformValue = (string) ($filters['platform'] ?? request('platform', ''));
    $playstyleValue = (string) ($filters['playstyle'] ?? request('playstyle', ''));
    $regionValue = (string) ($filters['region'] ?? request('region', ''));
    $languageValue = (string) ($filters['language'] ?? request('language', ''));
    $formatCount = static fn (int $value): string => number_format(
        $value,
        0,
        app()->getLocale() === 'de' ? ',' : '.',
        app()->getLocale() === 'de' ? '.' : ','
    );
    $filterUrl = static function (array $changes = []): string {
        $query = array_merge(request()->except('page'), $changes);
        $query = array_filter($query, static fn ($value) => $value !== null && $value !== '' && $value !== false);
        return route('teams.index', $query);
    };
    $viewerMemberships = $viewerMemberships ?? collect();
    $filterOptions = $filterOptions ?? collect();
    $optionLabel = static fn (string $field, string $value): string =>
        \App\Models\LfgPost::localizedOptionLabelFor($field, $value) ?? $value;
    $featuredCup = ($featuredCups ?? collect())->first();
    $teamsCssVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-teams/teams-overview.css')) ?: time();
    $teamsJsVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-teams/teams-overview.js')) ?: time();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1" name="viewport"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<meta content="noindex,nofollow" name="robots"/>
<title>{{ __('hnt_teams.title') }} · HNT.ROCKS</title>
<meta name="description" content="{{ __('hnt_teams.meta_description') }}"/>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&amp;display=swap" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v=20260710-1" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v=20260710-1" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/theme-colors.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/theme-colors.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-teams/teams-overview.css') }}?v={{ $teamsCssVersion }}" rel="stylesheet"/>
</head>
<body data-page="teams">
<svg aria-hidden="true" class="svg-defs">
<symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.8"></circle><path d="m16.2 16.2 4 4"></path></symbol>
<symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"></path></symbol>
<symbol id="i-sliders" viewBox="0 0 24 24"><path d="M4 7h9M17 7h3M4 17h3M11 17h9M13 4v6M8 14v6"></path></symbol>
<symbol id="i-settings" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3.1"></circle><path d="M19 13.6v-3.2l-2-.7-.7-1.7.9-1.9-2.3-2.3-1.9.9-1.7-.7-.7-2H8.4l-.7 2-1.7.7-1.9-.9-2.3 2.3.9 1.9-.7 1.7-2 .7v3.2l2 .7.7 1.7-.9 1.9 2.3 2.3 1.9-.9 1.7.7.7 2h3.2l.7-2 1.7-.7 1.9.9 2.3-2.3-.9-1.9.7-1.7z"></path></symbol>
<symbol id="i-bell" viewBox="0 0 24 24"><path d="M6 9a6 6 0 0 1 12 0c0 7 3 6 3 8H3c0-2 3-1 3-8"></path><path d="M9.5 20h5"></path></symbol>
<symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.4"></circle><path d="M5.5 20a6.5 6.5 0 0 1 13 0"></path></symbol>
<symbol id="i-arrow" viewBox="0 0 24 24"><path d="M7 17 17 7M9 7h8v8"></path></symbol>
<symbol id="i-arrow-left" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"></path></symbol>
<symbol id="i-arrow-right" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"></path></symbol>
<symbol id="i-briefcase" viewBox="0 0 24 24"><rect height="12" rx="3" width="18" x="3" y="7"></rect><path d="M9 7V5h6v2M3 12h18M10 12v2h4v-2"></path></symbol>
<symbol id="i-chevron" viewBox="0 0 24 24"><path d="m8 10 4 4 4-4"></path></symbol>
<symbol id="i-users" viewBox="0 0 24 24"><circle cx="9" cy="8.5" r="3"></circle><circle cx="17" cy="9.5" r="2.3"></circle><path d="M3 19a6 6 0 0 1 12 0M14 18a4.5 4.5 0 0 1 7 0"></path></symbol>
<symbol id="i-folder" viewBox="0 0 24 24"><path d="M3 7h7l2 2h9v10H3z"></path><path d="M3 7V5h7l2 2"></path></symbol>
<symbol id="i-check" viewBox="0 0 24 24"><path d="m6 12 4 4 8-8"></path></symbol>
<symbol id="i-comment" viewBox="0 0 24 24"><path d="M21 12a8 8 0 0 1-8 8H5l-3 2 1-5a8 8 0 1 1 18-5Z"></path></symbol>
<symbol id="i-bookmark" viewBox="0 0 24 24"><path d="M6 3h12v18l-6-4-6 4z"></path></symbol>
<symbol id="i-image" viewBox="0 0 24 24"><rect height="16" rx="3" width="18" x="3" y="4"></rect><circle cx="9" cy="10" r="2"></circle><path d="m5 18 5-5 3 3 2-2 4 4"></path></symbol>
<symbol id="i-eye" viewBox="0 0 24 24"><path d="M2.8 12s3.3-6 9.2-6 9.2 6 9.2 6-3.3 6-9.2 6-9.2-6-9.2-6"></path><circle cx="12" cy="12" r="2.6"></circle></symbol>
</svg>
<main class="app-shell feed-shell teams-page-shell">
@include('themes.hnt_preview.partials.header')
<section class="feed-stage teams-stage">
<div class="feed-scroll">
<section class="teams-overview">
<div class="teams-overview-copy">
<span>{{ __('hnt_teams.eyebrow') }}</span>
<h1>{{ __('hnt_teams.title') }}</h1>
<p>{{ __('hnt_teams.intro') }}</p>
<div class="teams-progress-row">
<div class="teams-progress-item wide"><span>{{ __('hnt_teams.all_teams') }}</span><div class="teams-progress dark"><b>{{ $formatCount((int) $stats['all']) }}</b><i style="width:100%"></i></div></div>
<div class="teams-progress-item"><span>{{ __('hnt_teams.my_teams') }}</span><div class="teams-progress red"><b>{{ $formatCount((int) $stats['mine']) }}</b><i style="width:{{ min(100, max(8, (int) round(((int) $stats['mine'] / max(1, (int) $stats['all'])) * 100))) }}%"></i></div></div>
<div class="teams-progress-item"><span>{{ __('hnt_teams.recruiting') }}</span><div class="teams-progress striped"><b>{{ $formatCount((int) $stats['recruiting']) }}</b><i style="width:{{ min(100, (int) round(((int) $stats['recruiting'] / max(1, (int) $stats['all'])) * 100)) }}%"></i></div></div>
<div class="teams-progress-item compact"><span>{{ __('hnt_teams.new_this_week') }}</span><div class="teams-progress outline"><b>{{ $formatCount((int) $stats['new_this_week']) }}</b></div></div>
</div>
</div>
<div class="teams-overview-counts">
<article><strong>{{ $formatCount((int) $stats['all']) }}</strong><span>{{ trans_choice('hnt_teams.team_count_label', (int) $stats['all']) }}</span></article>
<article><strong>{{ $formatCount((int) $stats['members']) }}</strong><span>{{ trans_choice('hnt_teams.member_count_label', (int) $stats['members']) }}</span></article>
<article><strong>{{ $formatCount((int) $stats['recruiting']) }}</strong><span>{{ __('hnt_teams.recruiting') }}</span></article>
</div>
</section>

@if(session('status'))<div class="teams-flash success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="teams-flash error">{{ $errors->first() }}</div>@endif

<section class="teams-directory-card is-filter-open" data-teams-directory>
<header class="teams-directory-head">
<nav aria-label="{{ __('hnt_teams.title') }}" class="teams-view-tabs">
<a class="{{ $activeView === 'discover' ? 'active' : '' }}" href="{{ route('teams.index') }}">{{ __('hnt_teams.discover') }}</a>
<a class="{{ $activeView === 'popular' ? 'active' : '' }}" href="{{ $filterUrl(['view' => 'popular', 'sort' => 'members', 'recruiting' => null]) }}">{{ __('hnt_teams.popular') }}</a>
<a class="{{ $activeView === 'recruiting' ? 'active' : '' }}" href="{{ $filterUrl(['view' => 'recruiting', 'recruiting' => null, 'sort' => 'newest']) }}">{{ __('hnt_teams.recruiting') }} <span>{{ $formatCount((int) $stats['recruiting']) }}</span></a>
<a class="{{ $activeView === 'mine' ? 'active' : '' }}" href="{{ $filterUrl(['view' => 'mine', 'recruiting' => null, 'sort' => 'newest']) }}">{{ __('hnt_teams.my_teams') }} <span>{{ $formatCount((int) $stats['mine']) }}</span></a>
</nav>
<form class="teams-search" method="get" action="{{ route('teams.index') }}">
<button aria-label="{{ __('hnt_teams.search_placeholder') }}" type="submit"><svg><use href="#i-search"></use></svg></button>
<input autocomplete="off" name="q" value="{{ $searchValue }}" placeholder="{{ __('hnt_teams.search_placeholder') }}" type="search"/>
@foreach(request()->except(['q', 'page']) as $key => $value)@if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}"/>@endif @endforeach
</form>
</header>
<form class="teams-filter-strip" method="get" action="{{ route('teams.index') }}" data-teams-filter-form>
@if($activeView !== 'discover')<input type="hidden" name="view" value="{{ $activeView }}"/>@endif
@if($searchValue !== '')<input type="hidden" name="q" value="{{ $searchValue }}"/>@endif
<label><span>{{ __('hnt_teams.platform') }}</span><select name="platform"><option value="">{{ __('hnt_teams.all') }}</option>@foreach($filterOptions->get('platform', collect()) as $option)<option value="{{ $option }}" @selected($platformValue === $option)>{{ $optionLabel('platform', $option) }}</option>@endforeach</select></label>
<label><span>{{ __('hnt_teams.playstyle') }}</span><select name="playstyle"><option value="">{{ __('hnt_teams.all') }}</option>@foreach($filterOptions->get('playstyle', collect()) as $option)<option value="{{ $option }}" @selected($playstyleValue === $option)>{{ $optionLabel('playstyle', $option) }}</option>@endforeach</select></label>
<label><span>{{ __('hnt_teams.region') }}</span><select name="region"><option value="">{{ __('hnt_teams.all') }}</option>@foreach($filterOptions->get('region', collect()) as $option)<option value="{{ $option }}" @selected($regionValue === $option)>{{ $optionLabel('region', $option) }}</option>@endforeach</select></label>
<label><span>{{ __('hnt_teams.language') }}</span><select name="language"><option value="">{{ __('hnt_teams.all') }}</option>@foreach($filterOptions->get('language', collect()) as $option)<option value="{{ $option }}" @selected($languageValue === $option)>{{ $optionLabel('language', $option) }}</option>@endforeach</select></label>
<label><span>{{ __('hnt_teams.sorting') }}</span><select name="sort"><option value="newest" @selected($activeSort === 'newest')>{{ __('hnt_teams.newest') }}</option><option value="members" @selected($activeSort === 'members')>{{ __('hnt_teams.most_members') }}</option><option value="name" @selected($activeSort === 'name')>{{ __('hnt_teams.name_az') }}</option></select></label>
<label class="teams-recruiting-filter {{ $isRecruiting ? 'active' : '' }}"><input type="checkbox" name="recruiting" value="1" @checked($isRecruiting)/><i></i><span>{{ __('hnt_teams.only_recruiting') }}</span></label>
<div class="teams-filter-actions"><button type="submit"><svg><use href="#i-search"></use></svg>{{ __('hnt_teams.filter_results') }}</button><a href="{{ route('teams.index') }}"><svg><use href="#i-sliders"></use></svg>{{ __('hnt_teams.reset_filters') }}</a></div>
</form>

<div class="teams-directory-layout">
<section aria-label="{{ __('hnt_teams.title') }}" class="teams-table" role="table">
<div class="teams-table-head" role="row"><span>{{ __('hnt_teams.team') }}</span><span>{{ __('hnt_teams.profile') }}</span><span>{{ __('hnt_teams.platform') }}</span><span>{{ __('hnt_teams.region') }}</span><span>{{ __('hnt_teams.playstyle') }}</span><span>{{ __('hnt_teams.members') }}</span><span>{{ __('hnt_teams.posts') }}</span><span>{{ __('hnt_teams.status') }}</span><span>{{ __('hnt_teams.actions') }}</span></div>
<div class="teams-table-body">
@forelse($teams as $team)
@php
    $membership = $viewerMemberships->get($team->id);
    $canManage = $membership && $membership->status === 'active' && in_array($membership->role, ['owner', 'officer'], true);
    $isPending = $membership && $membership->status === 'pending';
    $isActiveMember = $membership && $membership->status === 'active';
    $memberCount = (int) ($team->members_count ?? $team->activeMembers->count());
    $postsCount = (int) ($team->posts_count ?? 0);
    $ownerLabel = $team->owner?->name ?: $team->owner?->username ?: __('hnt_teams.hunter');
    $teamSummary = $team->tagline ?: \Illuminate\Support\Str::limit((string) $team->description, 72) ?: __('hnt_teams.unknown');
    $roleLabel = $membership?->role ? __('hnt_teams.' . $membership->role) : __('hnt_teams.owner');
    $languageLabel = $team->language ? $optionLabel('language', $team->language) : __('hnt_teams.unknown');
    $playstyleLabel = $team->playstyle ? $optionLabel('playstyle', $team->playstyle) : __('hnt_teams.unknown');
@endphp
<article class="teams-table-row {{ $membership?->status === 'active' ? 'is-member' : '' }}" role="row">
<div class="team-identity"><a class="team-mark" href="{{ route('teams.show', $team) }}"><img src="{{ $team->avatarUrl() }}" alt="{{ $team->name }}"/></a><div><a href="{{ route('teams.show', $team) }}"><strong>{{ $team->name }}</strong></a><small>{{ '@' . $team->slug }}</small></div></div>
<div class="team-profile"><strong>{{ $teamSummary }}</strong><small>{{ $ownerLabel }} · {{ $membership?->status === 'active' ? $roleLabel : __('hnt_teams.owner') }} · {{ $languageLabel }}</small></div>
<span class="team-chip">{{ $team->platform ?: __('hnt_teams.unknown') }}</span>
<span class="team-cell">{{ $team->region ?: __('hnt_teams.unknown') }}</span>
<span class="team-cell">{{ $playstyleLabel }}</span>
<div class="team-members-cell"><div class="team-avatar-stack">@foreach($team->activeMembers->take(3) as $member)<img alt="{{ $member->user?->name ?: $member->user?->username ?: __('hnt_teams.hunter') }}" src="{{ $member->user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"/>@endforeach</div><strong>{{ $formatCount($memberCount) }}</strong></div>
<span class="team-number"><strong>{{ $formatCount($postsCount) }}</strong><small>{{ __('hnt_teams.posts') }}</small></span>
<span class="team-status {{ $team->recruitment_status === 'open' ? 'recruiting' : 'closed' }}"><i></i>{{ $team->recruitment_status === 'open' ? __('hnt_teams.recruiting_open') : __('hnt_teams.closed') }}</span>
<div class="team-row-actions">
@if($canManage)<a aria-label="{{ __('hnt_teams.manage_team') }}" href="{{ route('teams.edit', $team) }}"><svg><use href="#i-sliders"></use></svg></a>
@elseif($isPending)<span class="pending" title="{{ __('hnt_teams.request_pending') }}"><svg><use href="#i-check"></use></svg></span>
@elseif(! $isActiveMember && $team->recruitment_status === 'open')<form method="post" action="{{ route('teams.join', $team) }}" data-teams-join-form>@csrf<button aria-label="{{ __('hnt_teams.request_join') }}" type="submit"><svg><use href="#i-plus"></use></svg></button></form>@endif
<a aria-label="{{ __('hnt_teams.open_team') }}" href="{{ route('teams.show', $team) }}"><svg><use href="#i-arrow"></use></svg></a>
</div>
</article>
@empty
<div class="teams-empty-state"><svg><use href="#i-users"></use></svg><strong>{{ __('hnt_teams.no_results_title') }}</strong><span>{{ __('hnt_teams.no_results_text') }}</span><a href="{{ route('teams.index') }}">{{ __('hnt_teams.reset_filters') }}</a></div>
@endforelse
</div>
<footer class="teams-table-footer"><span>{{ trans_choice('hnt_teams.shown', $teams->count(), ['count' => $teams->count()]) }}</span>@if($teams->hasPages())<nav aria-label="{{ __('hnt_teams.pagination') }}">@if($teams->previousPageUrl())<a aria-label="{{ __('hnt_teams.previous') }}" href="{{ $teams->previousPageUrl() }}"><svg><use href="#i-arrow-left"></use></svg></a>@endif<span>{{ $teams->currentPage() }} / {{ $teams->lastPage() }}</span>@if($teams->nextPageUrl())<a aria-label="{{ __('hnt_teams.next') }}" href="{{ $teams->nextPageUrl() }}"><svg><use href="#i-arrow-right"></use></svg></a>@endif</nav>@endif</footer>
</section>

<aside class="teams-side-column">
<article class="teams-side-card teams-managed-card"><header><div><span>{{ __('hnt_teams.your_area') }}</span><h2>{{ __('hnt_teams.my_teams') }}</h2></div><a href="{{ route('teams.manage') }}">{{ __('hnt_teams.manage') }}</a></header><div class="managed-team-list">@forelse(($managedTeams ?? collect())->take(4) as $team)<a href="{{ route('teams.show', $team) }}"><img src="{{ $team->avatarUrl() }}" alt="{{ $team->name }}"/><div><strong>{{ $team->name }}</strong><small>{{ (int) $team->owner_id === (int) $viewer->id ? __('hnt_teams.owner') : __('hnt_teams.officer') }} · {{ trans_choice('hnt_teams.member_count', (int) ($team->members_count ?? 0), ['count' => (int) ($team->members_count ?? 0)]) }}</small></div><i title="{{ trans_choice('hnt_teams.pending_requests', (int) ($team->pending_count ?? 0), ['count' => (int) ($team->pending_count ?? 0)]) }}">{{ $formatCount((int) ($team->pending_count ?? 0)) }}</i></a>@empty<p>{{ __('hnt_teams.no_teams_yet') }}</p>@endforelse</div><a class="teams-create-button" href="{{ route('teams.create') }}"><svg><use href="#i-plus"></use></svg>{{ __('hnt_teams.create_team') }}</a></article>

<article class="teams-side-card teams-lfg-card"><header><div><span>{{ __('hnt_teams.open_lfgs') }}</span><h2>{{ __('hnt_teams.hunters_wanted') }}</h2></div><a href="{{ route('lfg.index') }}">{{ __('hnt_teams.see_all') }}</a></header><div class="side-lfg-list">@forelse(($openLfgPosts ?? collect())->take(3) as $post)<a aria-label="{{ __('hnt_teams.open_lfg', ['title' => $post->title]) }}" href="{{ route('lfg.show', $post) }}"><img alt="{{ $post->user?->name ?: $post->user?->username ?: __('hnt_teams.hunter') }}" src="{{ $post->user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"/><div><strong>{{ $post->title }}</strong><small>{{ $post->region ?: __('hnt_teams.unknown') }} · {{ $post->platform ?: __('hnt_teams.unknown') }} · {{ trans_choice('hnt_teams.free_slots', $post->slotsOpen(), ['count' => $post->slotsOpen()]) }}</small></div><span aria-hidden="true">→</span></a>@empty<p>{{ __('hnt_teams.no_open_lfgs') }}</p>@endforelse</div></article>

@if($featuredCup)
@php
    $cupLimit = $featuredCup->participantLimit();
    $cupParticipants = (int) ($featuredCup->participants_count ?? 0);
    $cupRemaining = $cupLimit ? max(0, $cupLimit - $cupParticipants) : null;
    $cupProgress = $cupLimit ? min(100, (int) round(($cupParticipants / max(1, $cupLimit)) * 100)) : 20;
@endphp
<article class="teams-side-card teams-cup-card"><header><div><span>{{ __('hnt_teams.community_cup') }}</span><h2>{{ $featuredCup->title }}</h2></div><a aria-label="{{ __('hnt_teams.open_cup', ['title' => $featuredCup->title]) }}" href="{{ route('cups.show', $featuredCup) }}"><svg><use href="#i-arrow"></use></svg></a></header><a class="side-cup-preview" href="{{ route('cups.show', $featuredCup) }}" style="--team-cup-cover:url('{{ $featuredCup->coverUrl() }}')"><div><span>{{ $featuredCup->isRegistrationOpen() ? __('hnt_teams.registration_open') : $featuredCup->statusLabel() }}</span><strong>{{ trans_choice('hnt_teams.team_count', $cupParticipants, ['count' => $formatCount($cupParticipants)]) }}</strong><small>{{ $featuredCup->team_size ? __('hnt_teams.team_size', ['count' => $featuredCup->team_size]) : __('hnt_teams.solo') }} · {{ implode(' / ', $featuredCup->allowedPlatforms()) ?: ($featuredCup->platform ?: __('hnt_teams.unknown')) }}</small></div><i><b style="width:{{ $cupProgress }}%"></b></i><p>{{ $cupRemaining === null ? __('hnt_teams.unlimited_slots') : trans_choice('hnt_teams.team_slots_left', $cupRemaining, ['count' => $cupRemaining]) }}</p></a></article>
@else
<article class="teams-side-card teams-cup-card teams-cup-empty"><header><div><span>{{ __('hnt_teams.community_cup') }}</span><h2>{{ __('hnt_teams.no_cup') }}</h2></div></header><p>{{ __('hnt_teams.no_cup_text') }}</p></article>
@endif
</aside>
</div>
</section>
</div>
</section>
<div class="toast" id="toast"></div>
</main>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v=20260710-1"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-teams/teams-overview.js') }}?v={{ $teamsJsVersion }}"></script>
</body>
</html>
