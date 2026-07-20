@php
    $isEnglish = app()->getLocale() === 'en';
    $copy = $isEnglish ? [
        'eyebrow' => 'HNT.ROCKS LEGENDS',
        'title' => 'Hall of Fame',
        'all_time' => 'All time',
        'community_cups' => 'Community cups',
        'verified' => 'Manually confirmed results',
        'intro' => 'The most successful teams and hunters from completed community cups – with wins, points, records and final appearances.',
        'champion_teams' => 'Champion teams',
        'cup_winners' => 'Cup winners',
        'finalists' => 'Finalists',
        'records' => 'Records',
        'cups' => 'Cups',
        'champions' => 'Champions',
        'scores' => 'Scores',
        'ranking' => 'Ranking',
        'legends' => 'Legends of the Bayou',
        'teams' => 'Teams',
        'hunters' => 'Hunters',
        'season_all' => 'All time',
        'season' => 'Season',
        'finalist' => 'Finalist',
        'champion' => 'Champion',
        'top_three' => 'Top 3',
        'points' => 'Points',
        'wins' => 'Cup wins',
        'podiums' => 'Podium finishes',
        'kills' => 'confirmed hunter kills',
        'extractions' => 'successful extractions',
        'appearances' => 'final appearances',
        'view_team' => 'View result',
        'past_cups' => 'Past cups',
        'records_title' => 'Unforgotten',
        'active_records' => 'active',
        'hunter_board' => 'Hunter ranking',
        'individual' => 'Individual results',
        'confirmed_only' => 'Only confirmed cup submissions',
        'bounties' => 'Bounties',
        'empty_title' => 'No finished cups yet',
        'empty_text' => 'The Hall of Fame is filled automatically as soon as a cup has ended and confirmed scores are available.',
        'open_cups' => 'Open cups',
        'no_team' => 'No team',
    ] : [
        'eyebrow' => 'HNT.ROCKS LEGENDEN',
        'title' => 'Hall of Fame',
        'all_time' => 'Alle Zeiten',
        'community_cups' => 'Community Cups',
        'verified' => 'Manuell bestätigte Ergebnisse',
        'intro' => 'Die erfolgreichsten Teams und Hunter aus vergangenen Community Cups – mit Siegen, Punkten, Rekorden und Finalteilnahmen.',
        'champion_teams' => 'Champion-Teams',
        'cup_winners' => 'Cup-Sieger',
        'finalists' => 'Finalisten',
        'records' => 'Rekorde',
        'cups' => 'Cups',
        'champions' => 'Champions',
        'scores' => 'Scores',
        'ranking' => 'Rangliste',
        'legends' => 'Legenden des Bayou',
        'teams' => 'Teams',
        'hunters' => 'Hunter',
        'season_all' => 'Alle Zeiten',
        'season' => 'Saison',
        'finalist' => 'Finalist',
        'champion' => 'Champion',
        'top_three' => 'Top 3',
        'points' => 'Punkte',
        'wins' => 'Cup-Siege',
        'podiums' => 'Podiumsplätze',
        'kills' => 'bestätigte Hunter-Kills',
        'extractions' => 'erfolgreiche Extraktionen',
        'appearances' => 'Finalteilnahmen',
        'view_team' => 'Ergebnis ansehen',
        'past_cups' => 'Vergangene Cups',
        'records_title' => 'Unvergessen',
        'active_records' => 'aktiv',
        'hunter_board' => 'Hunter-Rangliste',
        'individual' => 'Einzelleistungen',
        'confirmed_only' => 'Nur bestätigte Cup-Ergebnisse',
        'bounties' => 'Trophäen',
        'empty_title' => 'Noch keine beendeten Cups',
        'empty_text' => 'Die Hall of Fame füllt sich automatisch, sobald ein Cup beendet wurde und bestätigte Ergebnisse vorliegen.',
        'open_cups' => 'Cups öffnen',
        'no_team' => 'Kein Team',
    ];

    $scoredStatuses = \App\Models\CupSubmission::scoredStatuses();
    $placements = collect();
    $historyRows = collect();
    $submissionRows = collect();
    $seasons = collect();

    foreach ($hallCups as $hallEntry) {
        /** @var \App\Models\Cup $cup */
        $cup = $hallEntry['cup'];
        $cup->loadMissing([
            'teams.members.user:id,name,username,avatar_path,level',
            'submissions.submitter:id,name,username,avatar_path,level',
        ]);

        $seasonDate = $cup->ends_at ?: ($cup->starts_at ?: $cup->created_at);
        $season = (string) ($seasonDate?->format('Y') ?: now()->format('Y'));
        $seasons->push($season);
        $leaderboard = collect($hallEntry['topFive'])->values();

        foreach ($leaderboard as $index => $team) {
            $members = $team->members
                ->where('status', 'active')
                ->map(fn ($member) => $member->user)
                ->filter()
                ->values();

            if ($members->isEmpty() && $team->owner) {
                $members = collect([$team->owner]);
            }

            $teamSubmissions = $cup->submissions
                ->where('cup_team_id', $team->id)
                ->whereIn('status', $scoredStatuses)
                ->values();

            $placements->push([
                'cup' => $cup,
                'team' => $team,
                'name' => $team->displayName(),
                'rank' => $index + 1,
                'season' => $season,
                'members' => $members,
                'points' => (int) $team->points_total,
                'kills' => (int) $team->kills_total,
                'bounties' => (int) $team->bounty_tokens_total,
                'extractions' => $teamSubmissions->where('extracted', true)->count(),
            ]);
        }

        $winner = $leaderboard->first();
        if ($winner) {
            $historyRows->push([
                'cup' => $cup,
                'team' => $winner,
                'season' => $season,
                'members' => $winner->members->where('status', 'active')->map(fn ($member) => $member->user)->filter()->values(),
                'points' => (int) $winner->points_total,
                'date' => $seasonDate,
            ]);
        }

        foreach ($cup->submissions->whereIn('status', $scoredStatuses) as $submission) {
            if (! $submission->submitter) {
                continue;
            }

            $submissionTeam = $cup->teams->firstWhere('id', $submission->cup_team_id);
            $submissionRows->push([
                'user' => $submission->submitter,
                'team_name' => $submissionTeam?->displayName() ?: $copy['no_team'],
                'cup' => $cup,
                'season' => $season,
                'kills' => max(0, (int) $submission->kills),
                'bounties' => max(0, (int) $submission->bounty_tokens),
                'extracted' => (bool) $submission->extracted,
                'points' => max(0, (int) $submission->points),
            ]);
        }
    }

    $teamRows = $placements
        ->groupBy(fn (array $row): string => \Illuminate\Support\Str::lower(trim($row['name'])))
        ->map(function ($group): array {
            $representative = $group->sortBy([
                ['rank', 'asc'],
                ['points', 'desc'],
            ])->first();

            return [
                'name' => $representative['name'],
                'team' => $representative['team'],
                'cup' => $representative['cup'],
                'members' => $representative['members'],
                'wins' => $group->where('rank', 1)->count(),
                'podiums' => $group->where('rank', '<=', 3)->count(),
                'appearances' => $group->count(),
                'points' => $group->sum('points'),
                'kills' => $group->sum('kills'),
                'bounties' => $group->sum('bounties'),
                'extractions' => $group->sum('extractions'),
                'best_rank' => (int) $group->min('rank'),
                'seasons' => $group->pluck('season')->unique()->sortDesc()->implode(','),
            ];
        })
        ->sortBy([
            ['wins', 'desc'],
            ['points', 'desc'],
            ['kills', 'desc'],
            ['name', 'asc'],
        ])
        ->values()
        ->map(fn (array $row, int $index): array => array_merge($row, ['rank' => $index + 1]));

    $podiumBase = $teamRows->take(3)->values();
    $podiumRows = collect([1, 0, 2])->map(fn (int $index) => $podiumBase->get($index))->filter()->values();

    $hunterRows = $submissionRows
        ->groupBy(fn (array $row): int => (int) $row['user']->id)
        ->map(function ($group): array {
            $latest = $group->last();
            return [
                'user' => $latest['user'],
                'team_name' => $latest['team_name'],
                'kills' => $group->sum('kills'),
                'bounties' => $group->sum('bounties'),
                'extractions' => $group->where('extracted', true)->count(),
                'points' => $group->sum('points'),
                'cups' => $group->pluck('cup.id')->unique()->count(),
                'seasons' => $group->pluck('season')->unique()->sortDesc()->implode(','),
            ];
        })
        ->sortBy([
            ['points', 'desc'],
            ['kills', 'desc'],
            ['extractions', 'desc'],
        ])
        ->values()
        ->map(fn (array $row, int $index): array => array_merge($row, ['rank' => $index + 1]));

    $historyRows = $historyRows->sortByDesc(fn (array $row) => $row['date']?->timestamp ?? 0)->values();
    $seasons = $seasons->unique()->sortDesc()->values();
    $championCount = $teamRows->where('wins', '>', 0)->count();
    $scoreCount = $submissionRows->count();

    $recordPoints = $teamRows->sortByDesc('points')->first();
    $recordKills = $teamRows->sortByDesc('kills')->first();
    $recordExtractions = $teamRows->sortByDesc('extractions')->first();
    $records = collect([$recordPoints, $recordKills, $recordExtractions])->filter();

    $formatNumber = static fn (int $value): string => number_format($value, 0, ',', '.');
    $teamInitials = static function (string $name): string {
        return collect(preg_split('/\s+/u', trim($name)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('') ?: 'HNT';
    };
    $platformLabel = static function (\App\Models\Cup $cup) use ($isEnglish): string {
        $platforms = $cup->allowedPlatforms();
        return $platforms !== [] ? implode(' / ', $platforms) : ($isEnglish ? 'All platforms' : 'Alle Plattformen');
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="index,follow">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $copy['title'] }} · HNT.ROCKS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/common.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/feed.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/hall-of-fame-live.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-cups/hall-of-fame-live.css')) ?: time() }}" rel="stylesheet">
</head>
<body data-page="hall-of-fame">
@include('themes.hnt_preview.partials.icons')
<svg aria-hidden="true" class="svg-defs">
<symbol id="i-crown" viewBox="0 0 24 24"><path d="m3 7 4.5 4L12 4l4.5 7L21 7l-2 11H5Z"></path><path d="M5 18h14M7 21h10"></path></symbol>
<symbol id="i-medal" viewBox="0 0 24 24"><circle cx="12" cy="14" r="6"></circle><path d="m8 3 4 5 4-5M7 3h10"></path></symbol>
<symbol id="i-star" viewBox="0 0 24 24"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9Z"></path></symbol>
<symbol id="i-flame" viewBox="0 0 24 24"><path d="M12 22c4 0 7-2.8 7-7 0-3-1.7-5.5-4.8-8.8.1 3-1.5 4.2-2.8 5.1.2-3.7-1.7-6.3-4.1-8.3.2 4-2.3 6.3-2.3 10.5C5 18.6 8 22 12 22Z"></path></symbol>
</svg>
<main class="app-shell hall-page-shell" data-hall-root>
@include('themes.hnt_preview.partials.header')
<section class="hall-stage">
<div class="hall-scroll" id="hallScroll">
<section class="hall-overview">
<div class="hall-heading">
<span>{{ $copy['eyebrow'] }}</span>
<h1>{{ $copy['title'] }}</h1>
<div class="hall-meta">
<span class="live"><i></i>{{ $copy['all_time'] }}</span>
<span>{{ $copy['community_cups'] }}</span>
<span>{{ $copy['verified'] }}</span>
</div>
<p>{{ $copy['intro'] }}</p>
<div class="hall-bars">
<article class="hall-summary-bar"><span>{{ $copy['champion_teams'] }}</span><div class="dark"><b>{{ $formatNumber($championCount) }}</b></div></article>
<article class="hall-summary-bar"><span>{{ $copy['cup_winners'] }}</span><div class="red"><b>{{ $formatNumber($hallCups->count()) }}</b></div></article>
<article class="hall-summary-bar"><span>{{ $copy['finalists'] }}</span><div class="striped"><b>{{ $formatNumber((int) $finalistCount) }}</b></div></article>
<article class="hall-summary-bar"><span>{{ $copy['records'] }}</span><div class="outline"><b>{{ $formatNumber($records->count()) }}</b></div></article>
</div>
</div>
<div class="hall-overview-stats">
<article><strong>{{ $formatNumber($hallCups->count()) }}</strong><span>{{ $copy['cups'] }}</span></article>
<article><strong>{{ $formatNumber($championCount) }}</strong><span>{{ $copy['champions'] }}</span></article>
<article><strong>{{ $formatNumber($scoreCount) }}</strong><span>{{ $copy['scores'] }}</span></article>
</div>
</section>

<section class="hall-content" data-hall-teams-section>
<header class="hall-toolbar">
<div><span>{{ $copy['ranking'] }}</span><h2>{{ $copy['legends'] }}</h2></div>
<div class="hall-toolbar-actions">
<div class="hall-segmented" role="tablist" aria-label="{{ $copy['ranking'] }}">
<button class="active" data-hall-mode="teams" type="button" role="tab" aria-selected="true">{{ $copy['teams'] }}</button>
<button data-hall-mode="hunters" type="button" role="tab" aria-selected="false">{{ $copy['hunters'] }}</button>
</div>
<label class="hall-season-select">
<select data-hall-season aria-label="{{ $copy['season'] }}">
<option value="all">{{ $copy['season_all'] }}</option>
@foreach($seasons as $season)<option value="{{ $season }}">{{ $copy['season'] }} {{ $season }}</option>@endforeach
</select>
<svg><use href="#i-chevron"></use></svg>
</label>
</div>
</header>

@if($teamRows->isEmpty())
<div class="hall-empty-state">
<span>{{ $copy['eyebrow'] }}</span>
<h2>{{ $copy['empty_title'] }}</h2>
<p>{{ $copy['empty_text'] }}</p>
<a href="{{ route('cups.index') }}">{{ $copy['open_cups'] }}</a>
</div>
@else
<section class="hall-podium" id="hallPodium">
@foreach($podiumRows as $row)
@php
    $rank = (int) $row['rank'];
    $rankClass = match($rank) { 1 => 'rank-one', 2 => 'rank-two', default => 'rank-three' };
    $stateLabel = match($rank) { 1 => $copy['champion'], 2 => $copy['finalist'], default => $copy['top_three'] };
@endphp
<article class="hall-rank-card {{ $rankClass }}" data-seasons="{{ $row['seasons'] }}">
<span class="hall-card-state {{ $rank === 1 ? 'champion' : '' }}">@if($rank === 1)<svg><use href="#i-crown"></use></svg>@else<i></i>@endif{{ $stateLabel }}</span>
<div class="hall-rank-number">{{ str_pad((string)$rank, 2, '0', STR_PAD_LEFT) }}</div>
<div class="hall-team-head"><div class="hall-team-mark">{{ $teamInitials($row['name']) }}</div><div><strong>{{ $row['name'] }}</strong><span>{{ $platformLabel($row['cup']) }} · {{ $row['appearances'] }} {{ $copy['appearances'] }}</span></div></div>
<div class="hall-team-avatars">
@foreach($row['members']->take(3) as $member)<img alt="{{ $member->name ?: $member->username }}" src="{{ $member->avatarUrl() }}">@endforeach
<span>{{ $row['members']->count() }}</span>
</div>
<div class="hall-score"><strong>{{ $formatNumber((int)$row['points']) }}</strong><span>{{ $copy['points'] }}</span></div>
<div class="hall-card-line"></div>
<div class="hall-achievement-list">
<article><i><svg><use href="#i-check"></use></svg></i><span>{{ $formatNumber((int)$row['kills']) }} {{ $copy['kills'] }}</span></article>
<article><i><svg><use href="#i-check"></use></svg></i><span>{{ $formatNumber((int)$row['extractions']) }} {{ $copy['extractions'] }}</span></article>
<article><i><svg><use href="#i-check"></use></svg></i><span>{{ $formatNumber((int)$row['wins']) }} {{ $copy['wins'] }}</span></article>
<article><i><svg><use href="#i-check"></use></svg></i><span>{{ $formatNumber((int)$row['podiums']) }} {{ $copy['podiums'] }}</span></article>
</div>
<a href="{{ route('cups.show', $row['cup']) }}">{{ $copy['view_team'] }}</a>
</article>
@endforeach
</section>

<section class="hall-detail-grid">
<article class="hall-history-card">
<header><div><span>{{ $copy['past_cups'] }}</span><h2>{{ $copy['champions'] }}</h2></div><a href="{{ route('cups.index') }}">{{ $copy['cups'] }}</a></header>
<div class="hall-history-list">
@foreach($historyRows as $index => $row)
<article data-season="{{ $row['season'] }}">
<span class="history-rank champion">{{ str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
<div class="history-cup"><strong>{{ $row['cup']->title }}</strong><small>{{ $row['cup']->modeLabel() }} · {{ $platformLabel($row['cup']) }} · {{ $row['date']?->translatedFormat('M Y') }}</small></div>
<div class="history-team"><div>@foreach($row['members']->take(3) as $member)<img alt="{{ $member->name ?: $member->username }}" src="{{ $member->avatarUrl() }}">@endforeach</div><span>{{ $row['team']->displayName() }}</span></div>
<strong class="history-score">{{ $formatNumber((int)$row['points']) }}</strong>
<a href="{{ route('cups.show', $row['cup']) }}" aria-label="{{ $row['cup']->title }}"><svg><use href="#i-arrow"></use></svg></a>
</article>
@endforeach
</div>
</article>

<aside class="hall-records-card">
<header><div><span>{{ $copy['records'] }}</span><h2>{{ $copy['records_title'] }}</h2></div><span class="records-live"><i></i>{{ $records->count() }} {{ $copy['active_records'] }}</span></header>
<div class="hall-record-ring"><div><svg><use href="#i-crown"></use></svg><strong>{{ $formatNumber((int)($recordPoints['wins'] ?? 0)) }}</strong><span>{{ $copy['wins'] }}</span></div></div>
<div class="hall-record-list">
@if($recordKills)<article><span><svg><use href="#i-flame"></use></svg></span><div><strong>{{ $formatNumber((int)$recordKills['kills']) }} {{ $copy['kills'] }}</strong><small>{{ $recordKills['name'] }}</small></div></article>@endif
@if($recordExtractions)<article><span><svg><use href="#i-medal"></use></svg></span><div><strong>{{ $formatNumber((int)$recordExtractions['extractions']) }} {{ $copy['extractions'] }}</strong><small>{{ $recordExtractions['name'] }}</small></div></article>@endif
@if($recordPoints)<article><span><svg><use href="#i-star"></use></svg></span><div><strong>{{ $formatNumber((int)$recordPoints['points']) }} {{ $copy['points'] }}</strong><small>{{ $recordPoints['name'] }}</small></div></article>@endif
</div>
</aside>
</section>
@endif
</section>

<section class="hall-hunter-board" data-hall-hunters-section hidden>
<header><div><span>{{ $copy['hunter_board'] }}</span><h2>{{ $copy['individual'] }}</h2></div><small>{{ $copy['confirmed_only'] }}</small></header>
@if($hunterRows->isEmpty())
<div class="hall-empty-state"><span>{{ $copy['hunter_board'] }}</span><h2>{{ $copy['empty_title'] }}</h2><p>{{ $copy['empty_text'] }}</p></div>
@else
<div class="hunter-board-list">
@foreach($hunterRows as $row)
<article data-seasons="{{ $row['seasons'] }}">
<span class="hunter-position {{ $row['rank'] === 1 ? 'champion' : '' }}">{{ $row['rank'] }}</span>
<img alt="{{ $row['user']->name ?: $row['user']->username }}" src="{{ $row['user']->avatarUrl() }}">
<div><strong>{{ $row['user']->name ?: $row['user']->username }}</strong><small>{{ $row['team_name'] }} · {{ $row['cups'] }} {{ $copy['cups'] }}</small></div>
<div><strong>{{ $formatNumber((int)$row['kills']) }}</strong><small>{{ $copy['kills'] }}</small></div>
<div><strong>{{ $formatNumber((int)$row['extractions']) }}</strong><small>{{ $copy['extractions'] }}</small></div>
<div><strong>{{ $formatNumber((int)$row['points']) }}</strong><small>{{ $copy['points'] }}</small></div>
<a href="{{ route('profile.public', $row['user']) }}" aria-label="{{ $row['user']->name ?: $row['user']->username }}"><svg><use href="#i-arrow"></use></svg></a>
</article>
@endforeach
</div>
@endif
</section>
</div>
</section>
<div class="toast" id="toast"></div>
</main>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-cups/hall-of-fame-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-cups/hall-of-fame-live.js')) ?: time() }}"></script>
</body>
</html>
