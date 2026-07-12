@php
    $relationship = $filters['relationship'] ?? 'all';
    $makeFilterUrl = static function (array $overrides = []) {
        return route('members.index', array_merge(request()->except(['page', 'fragment', 'export']), $overrides));
    };
    $activeFilters = collect([
        'platform' => $filters['platform'] ?? null,
        'playstyle' => $filters['playstyle'] ?? null,
        'region' => $filters['region'] ?? null,
        'language' => $filters['language'] ?? null,
        'lfg' => ($filters['lfg'] ?? null) === '1' ? 'Ready / LFG' : null,
    ])->filter();
    $totalMembers = max(0, (int) ($membersStats['total'] ?? 0));
    $progressBase = max(1, $totalMembers);
    $friendProgress = min(100, (int) round(((int) ($membersStats['friends'] ?? 0) / $progressBase) * 100));
    $lfgProgress = min(100, (int) round(((int) ($membersStats['lfg'] ?? 0) / $progressBase) * 100));
    $exportUrl = route('members.index', array_merge(request()->except(['page', 'fragment', 'export']), ['export' => 'csv']));
    $inviteUrl = \Illuminate\Support\Facades\Route::has('referrals.index') ? route('referrals.index') : null;
    $localeIsEnglish = app()->getLocale() === 'en';
    $currentPage = $members->currentPage();
    $lastPage = $members->lastPage();
    $pageStart = max(1, min($currentPage - 2, max(1, $lastPage - 4)));
    $pageEnd = min($lastPage, $pageStart + 4);
@endphp
<!DOCTYPE html>
<html lang="{{ $localeIsEnglish ? 'en' : 'de' }}">
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
<div aria-hidden="true" class="feed-shell" hidden style="display:none!important"></div>
<main class="app-shell members-page-shell">
@include('themes.hnt_preview.partials.header')

<section class="members-overview">
    <div class="members-overview-copy">
        <span>HNT.ROCKS COMMUNITY</span>
        <h1>{{ $localeIsEnglish ? 'Members' : 'Mitglieder' }}</h1>
        <div class="members-progress-row">
            <div class="members-progress-item wide">
                <span>{{ $localeIsEnglish ? 'All hunters' : 'Alle Hunter' }}</span>
                <div class="members-progress dark"><b>{{ number_format($totalMembers, 0, ',', '.') }}</b><i style="width:100%"></i></div>
            </div>
            <div class="members-progress-item">
                <span>{{ __('ui.friends') }}</span>
                <div class="members-progress yellow"><b>{{ number_format((int) ($membersStats['friends'] ?? 0), 0, ',', '.') }}</b><i style="width:{{ $friendProgress }}%"></i></div>
            </div>
            <div class="members-progress-item">
                <span>Ready / LFG</span>
                <div class="members-progress striped"><b>{{ number_format((int) ($membersStats['lfg'] ?? 0), 0, ',', '.') }}</b><i style="width:{{ $lfgProgress }}%"></i></div>
            </div>
            <div class="members-progress-item compact">
                <span>{{ $localeIsEnglish ? 'New this week' : 'Neu diese Woche' }}</span>
                <div class="members-progress outline"><b>{{ number_format((int) ($membersStats['new_this_week'] ?? 0), 0, ',', '.') }}</b></div>
            </div>
        </div>
    </div>
    <div class="members-overview-counts">
        <article><strong>{{ number_format((int) ($membersStats['online'] ?? 0), 0, ',', '.') }}</strong><span>Online</span></article>
        <article><strong>{{ number_format((int) ($membersStats['lfg'] ?? 0), 0, ',', '.') }}</strong><span>Ready</span></article>
        <article><strong>{{ number_format((int) ($membersStats['pending'] ?? 0), 0, ',', '.') }}</strong><span>{{ $localeIsEnglish ? 'Requests' : 'Anfragen' }}</span></article>
    </div>
</section>

@if(session('status'))
<div class="members-alert success">{{ session('status') }}</div>
@endif
@if($errors->any())
<div class="members-alert danger"><strong>{{ __('ui.please_check') }}</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<section class="members-directory-card">
    <div aria-label="{{ $localeIsEnglish ? 'Member actions' : 'Mitgliederaktionen' }}" class="members-action-shelf">
        @if($inviteUrl)
        <a aria-label="{{ $localeIsEnglish ? 'Invite member' : 'Mitglied einladen' }}" class="members-shelf-icon" href="{{ $inviteUrl }}"><svg><use href="#i-plus"></use></svg></a>
        @endif
        <button aria-label="{{ $localeIsEnglish ? 'Toggle filters' : 'Filter ein- oder ausblenden' }}" class="members-shelf-icon members-shelf-filter" data-members-filter-toggle type="button"><svg><use href="#i-sliders"></use></svg></button>
        <a class="members-shelf-export" href="{{ $exportUrl }}"><svg><use href="#i-export"></use></svg><span>Export</span></a>
    </div>

    <header class="members-directory-head">
        <nav aria-label="{{ __('ui.members') }}" class="members-relationship-tabs">
            <a class="{{ $relationship === 'all' ? 'active' : '' }}" href="{{ $makeFilterUrl(['relationship' => 'all']) }}">{{ $localeIsEnglish ? 'All members' : 'Alle Mitglieder' }} <span>{{ $totalMembers }}</span></a>
            <a class="{{ $relationship === 'friends' ? 'active' : '' }}" href="{{ $makeFilterUrl(['relationship' => 'friends']) }}">{{ __('ui.friends') }} <span>{{ (int) ($relationshipCounts['friends'] ?? 0) }}</span></a>
            <a class="{{ $relationship === 'pending' ? 'active' : '' }}" href="{{ $makeFilterUrl(['relationship' => 'pending']) }}">{{ $localeIsEnglish ? 'Requests' : 'Anfragen' }} <span>{{ (int) ($relationshipCounts['pending'] ?? 0) }}</span></a>
        </nav>
        <div class="members-directory-actions">
            <form class="members-search" method="get" action="{{ route('members.index') }}">
                <input type="hidden" name="relationship" value="{{ $relationship }}">
                @foreach(['platform','playstyle','region','language','lfg'] as $key)
                    @if(filled($filters[$key] ?? null))<input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">@endif
                @endforeach
                <svg><use href="#i-search"></use></svg>
                <input autocomplete="off" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ $localeIsEnglish ? 'Search members' : 'Mitglieder suchen' }}" type="search">
            </form>
            <button class="members-mobile-filter-trigger" data-members-filter-open type="button"><svg><use href="#i-sliders"></use></svg><span>{{ $localeIsEnglish ? 'Filters' : 'Filter' }}</span></button>
        </div>
    </header>

    <form class="members-filter-strip {{ $activeFilters->isEmpty() ? '' : 'has-active-filters' }}" data-members-filter-strip method="get" action="{{ route('members.index') }}">
        @if(filled($filters['q'] ?? null))<input type="hidden" name="q" value="{{ $filters['q'] }}">@endif
        <input type="hidden" name="relationship" value="{{ $relationship }}">
        <label><span>{{ __('ui.platform') }}</span><select name="platform" onchange="this.form.submit()"><option value="">{{ $localeIsEnglish ? 'All' : 'Alle' }}</option>@foreach($filterOptions['platforms'] as $option)<option value="{{ $option }}" @selected(($filters['platform'] ?? '') === $option)>{{ $option }}</option>@endforeach</select></label>
        <label><span>{{ __('ui.playstyle') }}</span><select name="playstyle" onchange="this.form.submit()"><option value="">{{ $localeIsEnglish ? 'All' : 'Alle' }}</option>@foreach($filterOptions['playstyles'] as $option)<option value="{{ $option }}" @selected(($filters['playstyle'] ?? '') === $option)>{{ $option }}</option>@endforeach</select></label>
        <label><span>{{ __('ui.region') }}</span><select name="region" onchange="this.form.submit()"><option value="">{{ $localeIsEnglish ? 'All' : 'Alle' }}</option>@foreach($filterOptions['regions'] as $option)<option value="{{ $option }}" @selected(($filters['region'] ?? '') === $option)>{{ $option }}</option>@endforeach</select></label>
        <label><span>{{ __('ui.language') }}</span><select name="language" onchange="this.form.submit()"><option value="">{{ $localeIsEnglish ? 'All' : 'Alle' }}</option>@foreach($filterOptions['languages'] as $option)<option value="{{ $option }}" @selected(($filters['language'] ?? '') === $option)>{{ $option }}</option>@endforeach</select></label>
        <label class="members-ready-filter {{ ($filters['lfg'] ?? null) === '1' ? 'active' : '' }}"><input type="checkbox" name="lfg" value="1" @checked(($filters['lfg'] ?? null) === '1') onchange="this.form.submit()"><i></i><span>{{ $localeIsEnglish ? 'Ready / LFG only' : 'Nur Ready / LFG' }}</span></label>
        <a class="members-reset-filter" href="{{ route('members.index', ['relationship' => $relationship]) }}">{{ $localeIsEnglish ? 'Reset filters' : 'Filter zurücksetzen' }}</a>
    </form>

    <div aria-label="{{ __('ui.members') }}" class="members-table" role="table">
        <div class="members-table-head" role="row">
            <span>{{ $localeIsEnglish ? 'Member' : 'Mitglied' }}</span>
            <span>{{ $localeIsEnglish ? 'Profile' : 'Profil' }}</span>
            <span>{{ __('ui.platform') }}</span>
            <span>{{ __('ui.region') }}</span>
            <span>{{ __('ui.playstyle') }}</span>
            <span>Posts</span>
            <span>{{ __('ui.friends') }}</span>
            <span>Moments</span>
            <span>Status</span>
            <span>{{ $localeIsEnglish ? 'Actions' : 'Aktionen' }}</span>
        </div>
        <div class="members-table-body" data-members-stream>
            @include('themes.hnt_preview.members.partials.member-items')
        </div>
    </div>

    <footer class="members-directory-footer">
        <span>{{ number_format($members->count(), 0, ',', '.') }} {{ $localeIsEnglish ? 'members shown' : 'Mitglieder angezeigt' }}</span>
        @if($lastPage > 1)
        <nav aria-label="{{ $localeIsEnglish ? 'Member pages' : 'Mitgliederseiten' }}" class="members-pagination">
            @if($members->onFirstPage())<span class="disabled">←</span>@else<a href="{{ $members->previousPageUrl() }}">←</a>@endif
            @for($page = $pageStart; $page <= $pageEnd; $page++)
                <a class="{{ $page === $currentPage ? 'active' : '' }}" href="{{ $members->url($page) }}">{{ $page }}</a>
            @endfor
            @if($members->hasMorePages())<a href="{{ $members->nextPageUrl() }}">→</a>@else<span class="disabled">→</span>@endif
        </nav>
        @endif
    </footer>
</section>

<div aria-hidden="true" class="members-mobile-filter-backdrop" data-members-filter-modal>
    <section aria-labelledby="membersMobileFilterTitle" aria-modal="true" class="members-mobile-filter-modal" role="dialog">
        <header><div><span>HNT.ROCKS</span><h2 id="membersMobileFilterTitle">{{ $localeIsEnglish ? 'Filter members' : 'Mitglieder filtern' }}</h2></div><button aria-label="{{ __('ui.close') }}" data-members-filter-close type="button"><svg><use href="#i-x"></use></svg></button></header>
        <form method="get" action="{{ route('members.index') }}">
            @if(filled($filters['q'] ?? null))<input type="hidden" name="q" value="{{ $filters['q'] }}">@endif
            <input type="hidden" name="relationship" value="{{ $relationship }}">
            <div class="members-mobile-filter-grid">
                <label><span>{{ __('ui.platform') }}</span><select name="platform"><option value="">{{ $localeIsEnglish ? 'All' : 'Alle' }}</option>@foreach($filterOptions['platforms'] as $option)<option value="{{ $option }}" @selected(($filters['platform'] ?? '') === $option)>{{ $option }}</option>@endforeach</select></label>
                <label><span>{{ __('ui.playstyle') }}</span><select name="playstyle"><option value="">{{ $localeIsEnglish ? 'All' : 'Alle' }}</option>@foreach($filterOptions['playstyles'] as $option)<option value="{{ $option }}" @selected(($filters['playstyle'] ?? '') === $option)>{{ $option }}</option>@endforeach</select></label>
                <label><span>{{ __('ui.region') }}</span><select name="region"><option value="">{{ $localeIsEnglish ? 'All' : 'Alle' }}</option>@foreach($filterOptions['regions'] as $option)<option value="{{ $option }}" @selected(($filters['region'] ?? '') === $option)>{{ $option }}</option>@endforeach</select></label>
                <label><span>{{ __('ui.language') }}</span><select name="language"><option value="">{{ $localeIsEnglish ? 'All' : 'Alle' }}</option>@foreach($filterOptions['languages'] as $option)<option value="{{ $option }}" @selected(($filters['language'] ?? '') === $option)>{{ $option }}</option>@endforeach</select></label>
                <label class="members-mobile-ready"><input type="checkbox" name="lfg" value="1" @checked(($filters['lfg'] ?? null) === '1')><i></i><strong>{{ $localeIsEnglish ? 'Ready / LFG only' : 'Nur Ready / LFG' }}</strong></label>
            </div>
            <footer><a href="{{ route('members.index', ['relationship' => $relationship]) }}">{{ $localeIsEnglish ? 'Reset' : 'Zurücksetzen' }}</a><button type="submit">{{ $localeIsEnglish ? 'Apply filters' : 'Filter anwenden' }}</button></footer>
        </form>
    </section>
</div>
</main>
<script>
window.HNT_DASHBOARD_HEADER_ENDPOINT = window.location.href;
</script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-members/members-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-members/members-live.js')) ?: time() }}"></script>
</body>
</html>
