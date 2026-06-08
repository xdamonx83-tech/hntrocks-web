@extends('layouts.app')

@section('title', $post->title.' · Team-LFG')

@section('content')
@php
    $canReportTeamLfg = ! $post->canManage(auth()->user());
@endphp
@if (session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="hh-alert hh-alert-danger">
        <strong>{{ __('ui.please_check') }}</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<section class="hh-lfg-detail-head hh-card">
    <div class="hh-lfg-detail-author">
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
            <p class="hh-kicker">{{ $post->typeLabel() }} · {{ $post->statusLabel() }} · {{ $post->visibilityLabel() }}</p>
            <h1>{{ $post->title }}</h1>
            @if ($post->isTeamSeekingPlayers() && $post->team)
                <p>{{ $post->team->name }} · {{ __('ui.team_lfg_created_by', ['name' => $post->user->name]) }} · {{ $post->created_at->diffForHumans() }}</p>
            @else
                <p>{{ $post->user->name }} · {{ '@'.$post->user->username }} · {{ $post->created_at->diffForHumans() }}</p>
            @endif
        </div>
    </div>

    <div class="hh-lfg-detail-actions">
        @if ($canManage)
            <a class="hh-secondary-button" href="{{ route('team-lfg.edit', $post) }}">{{ __('ui.team_lfg_edit_action') }}</a>
        @endif
        <a class="hh-secondary-button" href="{{ route('team-lfg.index') }}">{{ __('ui.team_lfg_back_overview') }}</a>
        @if ($canReportTeamLfg)
            <button class="hh-secondary-button hh-report-mini-button text-tooltip-tft" type="button" title="{{ __('ui.team_lfg_report') }}" data-title="{{ __('ui.team_lfg_report') }}" aria-label="{{ __('ui.team_lfg_report') }}" data-hh-report-open data-hh-report-type="team_lfg" data-hh-report-id="{{ $post->id }}" data-hh-report-label="{{ __('ui.team_lfg_report_label', ['title' => $post->title]) }}">
                <i class="hh-ph-action-icon ph ph-warning-octagon" aria-hidden="true"></i>
                <span class="hh-report-button-label">{{ __('ui.report_short') }}</span>
            </button>
        @endif
    </div>
</section>

<section class="hh-lfg-layout">
    <aside class="hh-lfg-side">
        <div class="hh-card hh-card-compact">
            <h2>{{ __('ui.team_lfg_details_title') }}</h2>
            <dl class="hh-mini-list">
                <div><dt>{{ __('ui.team_lfg_type_label') }}</dt><dd>{{ $post->typeLabel() }}</dd></div>
                <div><dt>{{ __('ui.status') }}</dt><dd>{{ $post->statusLabel() }}</dd></div>
                @if ($post->isTeamSeekingPlayers())
                    <div><dt>{{ __('ui.team_lfg_team_title') }}</dt><dd>{{ $post->team?->name ?: __('ui.team_lfg_team_missing') }}</dd></div>
                    <div><dt>{{ __('ui.team_lfg_wanted') }}</dt><dd>{{ __('ui.team_lfg_filled_count', ['filled' => $post->slots_filled, 'total' => $post->slots_total]) }}</dd></div>
                    <div><dt>{{ __('ui.team_lfg_free') }}</dt><dd>{{ $post->slotsOpen() }}</dd></div>
                @endif
                <div><dt>{{ __('ui.team_lfg_platform') }}</dt><dd>{{ $post->localizedOptionLabel('platform', $post->platform) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                <div><dt>{{ __('ui.team_lfg_playstyle') }}</dt><dd>{{ $post->localizedOptionLabel('playstyle', $post->playstyle) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                <div><dt>{{ __('ui.team_lfg_region') }}</dt><dd>{{ $post->localizedOptionLabel('region', $post->region) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                <div><dt>{{ __('ui.team_lfg_language') }}</dt><dd>{{ $post->localizedOptionLabel('language', $post->language) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                <div><dt>{{ __('ui.team_lfg_preferred_time') }}</dt><dd>{{ $post->localizedOptionLabel('preferred_time', $post->preferred_time) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                <div><dt>{{ __('ui.team_lfg_experience') }}</dt><dd>{{ $post->localizedOptionLabel('experience_level', $post->experience_level) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                <div><dt>Voice</dt><dd>{{ $post->voiceLabel() }}</dd></div>
            </dl>
        </div>

        @if ($post->team)
            <div class="hh-card hh-card-compact">
                <h2>{{ __('ui.team_lfg_team_title') }}</h2>
                <div class="hh-team-member-row">
                    <a href="{{ route('teams.show', $post->team) }}">
                        <img src="{{ $post->team->avatarUrl() }}" alt="{{ $post->team->name }}">
                    </a>
                    <div>
                        <strong><a href="{{ route('teams.show', $post->team) }}">{{ $post->team->name }}</a></strong>
                        <span>{{ __('ui.team_lfg_members_count', ['count' => $post->team->activeMembers->count()]) }} · {{ $post->team->recruitmentLabel() }}</span>
                    </div>
                </div>
            </div>
        @endif
    </aside>

    <main class="hh-lfg-main">
        <article class="hh-card hh-card-compact">
            <h2>{{ __('ui.team_lfg_description_title') }}</h2>
            @if ($post->body)
                <p>{!! \App\Support\MentionRenderer::render($post->body) !!}</p>
            @else
                <p>{{ __('ui.team_lfg_no_description') }}</p>
            @endif
        </article>

        @if ($post->canApplyAsUser(auth()->user()))
            <article class="hh-card hh-card-compact">
                <h2>{{ __('ui.team_lfg_apply_title') }}</h2>
                <form method="post" action="{{ route('team-lfg.applications.store', $post) }}" class="hh-form">
                    @csrf
                    <label for="message">{{ __('ui.team_lfg_message_optional') }}</label>
                    <textarea id="message" name="message" rows="4" maxlength="900" placeholder="{{ __('ui.team_lfg_apply_placeholder') }}"></textarea>
                    <button class="hh-primary-button" type="submit">{{ __('ui.team_lfg_apply_button') }}</button>
                </form>
            </article>
        @elseif ($post->isPlayerSeekingTeam() && ! $canManage && ! $viewerApplication && $manageableTeams->isNotEmpty())
            <article class="hh-card hh-card-compact">
                <h2>{{ __('ui.team_lfg_invite_title') }}</h2>
                <form method="post" action="{{ route('team-lfg.applications.store', $post) }}" class="hh-form">
                    @csrf
                    <div>
                        <label for="team_id">{{ __('ui.team_lfg_team_title') }}</label>
                        <select id="team_id" name="team_id" required>
                            @foreach ($manageableTeams as $team)
                                <option value="{{ $team->id }}" @disabled($post->hasApplicationFromTeam($team->id))>
                                    {{ $team->name }}{{ $post->hasApplicationFromTeam($team->id) ? ' · '.__('ui.team_lfg_team_already_invited') : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <label for="message">{{ __('ui.team_lfg_message_optional') }}</label>
                    <textarea id="message" name="message" rows="4" maxlength="900" placeholder="{{ __('ui.team_lfg_invite_placeholder') }}"></textarea>
                    <button class="hh-primary-button" type="submit">{{ __('ui.team_lfg_invite_button') }}</button>
                </form>
            </article>
        @elseif ($viewerApplication)
            <article class="hh-card hh-card-compact">
                <h2>{{ __('ui.team_lfg_your_request') }}</h2>
                <p>{{ __('ui.status') }}: <strong>{{ $viewerApplication->statusLabel() }}</strong></p>
                @if ($viewerApplication->team)
                    <p>Team: <strong>{{ $viewerApplication->team->name }}</strong></p>
                @endif
                @if ($viewerApplication->message)
                    <p>{!! nl2br(e($viewerApplication->message)) !!}</p>
                @endif
            </article>
        @endif

        @if ($canManage)
            <article class="hh-card hh-card-compact">
                <h2>{{ $post->isTeamSeekingPlayers() ? __('ui.team_lfg_applications_title') : __('ui.team_lfg_invitations_title') }}</h2>
                @forelse ($post->applications as $application)
                    <div class="hh-lfg-application-row">
                        <div class="hh-team-member-row">
                            @if ($application->team)
                                <a href="{{ route('teams.show', $application->team) }}">
                                    <img src="{{ $application->team->avatarUrl() }}" alt="{{ $application->team->name }}">
                                </a>
                                <div>
                                    <strong><a href="{{ route('teams.show', $application->team) }}">{{ $application->team->name }}</a></strong>
                                    <span>{{ $application->statusLabel() }} · {{ __('ui.team_lfg_invited_by', ['name' => $application->user->name]) }} · {{ $application->created_at->diffForHumans() }}</span>
                                    <p>{{ $application->message ?: __('ui.no_message_given') }}</p>
                                </div>
                            @else
                                <a href="{{ route('profile.public', $application->user) }}">
                                    <img src="{{ $application->user->avatarUrl() }}" alt="{{ $application->user->name }}">
                                </a>
                                <div>
                                    <strong><a href="{{ route('profile.public', $application->user) }}">{{ $application->user->name }}</a></strong>
                                    <span>{{ $application->statusLabel() }} · {{ $application->created_at->diffForHumans() }}</span>
                                    <p>{{ $application->message ?: __('ui.no_message_given') }}</p>
                                </div>
                            @endif
                        </div>
                        @if ($application->status === 'pending')
                            <div class="hh-team-request-actions">
                                <form method="post" action="{{ route('team-lfg.applications.accept', [$post, $application]) }}">
                                    @csrf
                                    <button class="hh-primary-button" type="submit">{{ __('ui.team_lfg_accept') }}</button>
                                </form>
                                <form method="post" action="{{ route('team-lfg.applications.reject', [$post, $application]) }}">
                                    @csrf
                                    <button class="hh-secondary-button" type="submit">{{ __('ui.team_lfg_reject') }}</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <p>{{ $post->isTeamSeekingPlayers() ? __('ui.team_lfg_no_applications') : __('ui.team_lfg_no_invitations') }}</p>
                @endforelse
            </article>
        @endif
    </main>
</section>
@endsection
