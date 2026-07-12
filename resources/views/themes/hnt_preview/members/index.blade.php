@php
    $relationship = $filters['relationship'] ?? 'all';
    $makeFilterUrl = static function (array $overrides = []) {
        return route('members.index', array_merge(request()->except(['page']), $overrides));
    };
    $activeFilters = collect([
        'platform' => $filters['platform'] ?? null,
        'playstyle' => $filters['playstyle'] ?? null,
        'region' => $filters['region'] ?? null,
        'language' => $filters['language'] ?? null,
        'lfg' => ($filters['lfg'] ?? null) === '1' ? 'LFG' : null,
    ])->filter();
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() === 'en' ? 'en' : 'de' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>{{ __('ui.members_title') }} · HNT.ROCKS</title>
<link href="https://fonts.googleapis.com" rel="preconnect">
<link crossorigin href="https://fonts.gstatic.com" rel="preconnect">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/common.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/feed.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-members/members-live.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-members/members-live.css')) ?: time() }}" rel="stylesheet">
</head>
<body data-page="members">
@include('themes.hnt_preview.partials.icons')
<main class="app-shell members-page-shell">
@include('themes.hnt_preview.partials.header')

<div class="members-page-content">
    <section class="members-live-hero">
        <div>
            <span>HNT.ROCKS COMMUNITY</span>
            <h1>{{ __('ui.preview_members_title') }}</h1>
            <p>{{ __('ui.preview_members_text') }}</p>
        </div>
        <div class="members-live-hero-stats" aria-label="{{ __('ui.preview_members_stats_aria') }}">
            <div><strong>{{ number_format((int) ($membersStats['total'] ?? 0), 0, ',', '.') }}</strong><span>{{ __('ui.members') }}</span></div>
            <div><strong>{{ number_format((int) ($membersStats['lfg'] ?? 0), 0, ',', '.') }}</strong><span>LFG</span></div>
            <div><strong>{{ number_format((int) ($membersStats['friends'] ?? 0), 0, ',', '.') }}</strong><span>{{ __('ui.friends') }}</span></div>
        </div>
    </section>

    @if(session('status'))
        <div class="members-live-alert success">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="members-live-alert danger">
            <strong>{{ __('ui.please_check') }}</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="members-live-toolbar">
        <nav class="members-live-tabs" aria-label="{{ __('ui.members') }}">
            <a class="{{ $relationship === 'all' ? 'active' : '' }}" href="{{ $makeFilterUrl(['relationship' => 'all']) }}">{{ __('ui.members_all_players') }} <span>{{ (int) ($membersStats['filtered'] ?? $members->total()) }}</span></a>
            <a class="{{ $relationship === 'friends' ? 'active' : '' }}" href="{{ $makeFilterUrl(['relationship' => 'friends']) }}">{{ __('ui.friends') }} <span>{{ (int) ($relationshipCounts['friends'] ?? 0) }}</span></a>
            <a class="{{ $relationship === 'pending' ? 'active' : '' }}" href="{{ $makeFilterUrl(['relationship' => 'pending']) }}">{{ __('ui.members_requests') }} <span>{{ (int) ($relationshipCounts['pending'] ?? 0) }}</span></a>
        </nav>

        <form class="members-live-search" method="get" action="{{ route('members.index') }}">
            <input type="hidden" name="relationship" value="{{ $relationship }}">
            @foreach(['platform','playstyle','region','language','lfg'] as $key)
                @if(filled($filters[$key] ?? null))<input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">@endif
            @endforeach
            <svg aria-hidden="true"><use href="#i-search"></use></svg>
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('ui.members_search_label') }}">
            <button type="submit">{{ __('ui.search') }}</button>
        </form>

        <button class="members-live-filter-button" data-members-filter-open type="button">
            <svg><use href="#i-sliders"></use></svg>
            <span>{{ __('ui.rework_members_filters') }}</span>
            @if($activeFilters->isNotEmpty())<em>{{ $activeFilters->count() }}</em>@endif
        </button>
    </section>

    @if($activeFilters->isNotEmpty())
        <div class="members-live-active-filters">
            @foreach($activeFilters as $key => $value)
                <span>{{ $value }}</span>
            @endforeach
            <a href="{{ route('members.index', ['relationship' => $relationship]) }}">{{ __('ui.preview_members_reset') }}</a>
        </div>
    @endif

    <section class="members-live-results-head">
        <div>
            <span>COMMUNITY</span>
            <h2>{{ __('ui.preview_members_title') }}</h2>
        </div>
        <p>{{ number_format($members->total(), 0, ',', '.') }} {{ __('ui.members') }}</p>
    </section>

    <section class="members-live-grid" data-members-stream aria-label="{{ __('ui.members') }}">
        @include('themes.hnt_preview.members.partials.member-items')
    </section>

    @if($hasMoreMembers && $nextMembersPageUrl)
        <div class="members-live-load-more-wrap" data-members-load-more-wrap>
            <button type="button" data-members-load-more data-next-url="{{ $nextMembersPageUrl }}">
                <span data-members-load-more-label>{{ __('ui.notifications_load_more') }}</span>
            </button>
        </div>
    @endif
</div>

<div class="members-live-modal-backdrop" data-members-filter-modal aria-hidden="true">
    <section class="members-live-filter-modal" role="dialog" aria-modal="true" aria-labelledby="membersFilterTitle">
        <header>
            <div><span>HNT.ROCKS</span><h2 id="membersFilterTitle">{{ __('ui.rework_members_filters') }}</h2></div>
            <button type="button" data-members-filter-close aria-label="{{ __('ui.close') }}"><svg><use href="#i-x"></use></svg></button>
        </header>
        <form method="get" action="{{ route('members.index') }}">
            @if(filled($filters['q'] ?? null))<input type="hidden" name="q" value="{{ $filters['q'] }}">@endif
            <input type="hidden" name="relationship" value="{{ $relationship }}">
            <div class="members-live-filter-grid">
                <label><span>{{ __('ui.platform') }}</span><select name="platform"><option value="">{{ __('ui.members_platform_all') }}</option>@foreach($filterOptions['platforms'] as $option)<option value="{{ $option }}" @selected(($filters['platform'] ?? '') === $option)>{{ $option }}</option>@endforeach</select></label>
                <label><span>{{ __('ui.playstyle') }}</span><select name="playstyle"><option value="">{{ __('ui.members_all_playstyles') }}</option>@foreach($filterOptions['playstyles'] as $option)<option value="{{ $option }}" @selected(($filters['playstyle'] ?? '') === $option)>{{ $option }}</option>@endforeach</select></label>
                <label><span>{{ __('ui.region') }}</span><select name="region"><option value="">{{ __('ui.preview_members_all_regions') }}</option>@foreach($filterOptions['regions'] as $option)<option value="{{ $option }}" @selected(($filters['region'] ?? '') === $option)>{{ $option }}</option>@endforeach</select></label>
                <label><span>{{ __('ui.language') }}</span><select name="language"><option value="">{{ __('ui.preview_members_all_languages') }}</option>@foreach($filterOptions['languages'] as $option)<option value="{{ $option }}" @selected(($filters['language'] ?? '') === $option)>{{ $option }}</option>@endforeach</select></label>
                <label class="members-live-check"><input type="checkbox" name="lfg" value="1" @checked(($filters['lfg'] ?? null) === '1')><i></i><strong>{{ __('ui.members_lfg_only') }}</strong></label>
            </div>
            <footer>
                <a href="{{ route('members.index', ['relationship' => $relationship]) }}">{{ __('ui.preview_members_reset') }}</a>
                <button type="submit">{{ __('ui.search') }}</button>
            </footer>
        </form>
    </section>
</div>
</main>
<script>
window.HNT_DASHBOARD_HEADER_ENDPOINT = window.location.href;
window.HNT_MEMBERS_COPY = {
    loading: @json(__('ui.rework_loading')),
    ready: @json(__('ui.notifications_load_more')),
    error: @json(__('ui.rework_try_again'))
};
</script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-members/members-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-members/members-live.js')) ?: time() }}"></script>
</body>
</html>
