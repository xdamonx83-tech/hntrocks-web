<aside aria-label="{{ __('hnt_cups_overview.your_cup') }}" class="cups-fixed-column cups-fixed-left" id="cupsFixedLeft">
@if($viewerTeam && $viewerCup)
<article class="cups-my-card">
<div class="cups-my-cover">
<img alt="{{ $viewerCup->title }}" src="{{ $viewerCup->coverUrl() }}"/>
<span><i></i>{{ $viewerCup->statusLabel() }}</span>
</div>
<div class="cups-my-content">
<span>{{ __('hnt_cups_overview.your_cup') }}</span>
<h2>{{ $viewerCup->title }}</h2>
<p>
{{ $viewerTeam->displayName() }} ·
{{ $viewerCup->isSoloLeaderboard() ? __('hnt_cups_overview.solo') : __('hnt_cups_overview.team_size', ['counter' => $viewerCup->team_size]) }} ·
{{ $viewerPlatforms }}
</p>
<div class="cups-team-stack">
@foreach($viewerMembers->take(4) as $member)
<img alt="{{ $member->user?->name ?: $member->user?->username ?: 'Hunter' }}" src="{{ $member->user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"/>
@endforeach
<strong>{{ $viewerMembers->count() }}/{{ max(1, $viewerRequiredMembers) }}</strong>
</div>
<div class="cups-my-progress">
<div><span>{{ __('hnt_cups_overview.participation') }}</span><strong>{{ $viewerSubmissionProgress }}%</strong></div>
<i><b style="width:{{ $viewerSubmissionProgress }}%"></b></i>
<small>{{ __('hnt_cups_overview.submission_progress', ['used' => $viewerSubmissionUsed, 'max' => $viewerSubmissionMax]) }}</small>
</div>
<div class="cups-my-actions">
<a href="{{ route('cups.show', $viewerCup) }}">{{ __('hnt_cups_overview.open_cup') }}</a>
<a href="{{ route('cups.teams.index', $viewerCup) }}">{{ __('hnt_cups_overview.manage_team') }}</a>
</div>
</div>
</article>
@else
<article class="cups-my-card">
<div class="cups-my-content">
<span>{{ __('hnt_cups_overview.your_cup') }}</span>
<h2>{{ __('hnt_cups_overview.no_current_cup') }}</h2>
<p>{{ __('hnt_cups_overview.no_current_cup_text') }}</p>
<div class="cups-my-actions">
<a href="{{ route('cups.index', ['status' => 'active']) }}">{{ __('hnt_cups_overview.browse_cups') }}</a>
<a href="{{ route('cups.index', ['mine' => 1]) }}">{{ __('hnt_cups_overview.my_cups') }}</a>
</div>
</div>
</article>
@endif
<article class="cups-timeline-card">
<header><span>{{ __('hnt_cups_overview.today_next') }}</span><h3>{{ __('hnt_cups_overview.schedule') }}</h3></header>
<div class="cups-mini-timeline">
@foreach($timelineEvents as $event)
<article class="{{ $loop->first ? 'current' : '' }}">
<time>{{ $event['date_label'] }}</time>
<div><strong>{{ $event['title'] }}</strong><small>{{ $event['detail'] }}</small></div>
</article>
@endforeach
</div>
</article>
</aside>
