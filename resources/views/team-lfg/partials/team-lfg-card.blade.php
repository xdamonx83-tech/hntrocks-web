@php
    $canReportTeamLfg = ! $post->canManage(auth()->user());
@endphp

<article class="hh-lfg-card hh-team-lfg-card">
    <div class="hh-lfg-card-head">
        @if ($post->isTeamSeekingPlayers() && $post->team)
            <a href="{{ route('teams.show', $post->team) }}">
                <img src="{{ $post->team->avatarUrl() }}" alt="{{ $post->team->name }}">
            </a>
        @else
            <a href="{{ route('profile.public', $post->user) }}">
                <img src="{{ $post->user->avatarUrl() }}" alt="{{ $post->user->name }}">
            </a>
        @endif
        <div>
            <p class="hh-kicker">{{ $post->typeLabel() }} · {{ $post->statusLabel() }}</p>
            <h2><a href="{{ route('team-lfg.show', $post) }}">{{ $post->title }}</a></h2>
            @if ($post->isTeamSeekingPlayers() && $post->team)
                <p>{{ $post->team->name }} · {{ $post->created_at->diffForHumans() }}</p>
            @else
                <p>{{ $post->user->name }} · {{ $post->created_at->diffForHumans() }}</p>
            @endif
        </div>
    </div>

    <p class="hh-lfg-card-body">{!! $post->body ? \App\Support\MentionRenderer::render($post->body) : e(__('ui.team_lfg_no_description')) !!}</p>

    <div class="hh-lfg-tags">
        @foreach ($post->displayTags() as $tag)
            <span>{{ $tag }}</span>
        @endforeach
        <span>{{ $post->voiceLabel() }}</span>
    </div>

    <div class="hh-lfg-bottom">
        @if ($post->isTeamSeekingPlayers())
            <div>
                <strong>{{ $post->slotsOpen() ?? 0 }}</strong>
                <span>{{ __('ui.team_lfg_card_free_slots') }}</span>
            </div>
        @else
            <div>
                <strong>{{ $post->pending_count ?? 0 }}</strong>
                <span>{{ __('ui.team_lfg_card_invitations') }}</span>
            </div>
        @endif
        <div>
            <strong>{{ $post->pending_count ?? 0 }}</strong>
            <span>{{ __('ui.team_lfg_card_requests') }}</span>
        </div>
        <div class="hh-report-inline-actions">
            <a class="hh-secondary-button" href="{{ route('team-lfg.show', $post) }}">{{ __('ui.team_lfg_view') }}</a>
            @if ($canReportTeamLfg)
                <button class="hh-secondary-button hh-report-mini-button text-tooltip-tft" type="button" title="{{ __('ui.team_lfg_report') }}" data-title="{{ __('ui.team_lfg_report') }}" aria-label="{{ __('ui.team_lfg_report') }}" data-hh-report-open data-hh-report-type="team_lfg" data-hh-report-id="{{ $post->id }}" data-hh-report-label="{{ __('ui.team_lfg_report_label', ['title' => $post->title]) }}">
                    <i class="hh-ph-action-icon ph ph-warning-octagon" aria-hidden="true"></i>
                    <span class="hh-report-button-label">{{ __('ui.report_short') }}</span>
                </button>
            @endif
        </div>
    </div>
</article>
