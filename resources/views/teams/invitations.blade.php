@extends('layouts.app')

@section('title', __('ui.invitations') . ' · hnt.rocks')

@section('content')
<div class="section-banner hh-account-hub-banner">
    <img class="section-banner-icon" src="{{ asset('assets/vikinger/img/banner/accounthub-icon.png') }}" alt="{{ __('ui.account_hub') }}">
    <p class="section-banner-title">{{ __('ui.account_hub') }}</p>
    <p class="section-banner-text">{{ __('ui.account_hub_groups_text') }}</p>
</div>

<div class="grid grid-3-9 medium-space hh-account-hub-grid hh-teams-hub-grid">
    @include('teams.partials.account-sidebar', ['activeSection' => 'teams', 'activeLink' => 'invitations'])

    <div class="account-hub-content">
        <div class="section-header">
            <div class="section-header-info">
                <p class="section-pretitle">{{ __('ui.teams') }}</p>
                <h2 class="section-title">{{ __('ui.invitations') }} <span class="highlighted">{{ $requests->count() }}</span></h2>
            </div>
        </div>

        @if (session('status'))
            <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="hh-alert hh-alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="grid grid-3-3-3 centered-on-mobile hh-account-team-grid">
            @forelse ($requests as $requestItem)
                @php
                    $team = $requestItem->team;
                    $requestUser = $requestItem->user;
                    $membersCount = $team?->activeMembers?->count() ?? 0;
                @endphp

                <div class="user-preview small hh-team-invitation-preview">
                    <figure class="user-preview-cover liquid">
                        <img src="{{ $team->coverUrl() }}" alt="{{ $team->name }}">
                    </figure>

                    <div class="user-preview-info">
                        <div class="user-short-description small">
                            <a class="user-short-description-avatar user-avatar no-stats" href="{{ route('teams.show', $team) }}">
                                <div class="user-avatar-border"><div class="hexagon-100-108"></div></div>
                                <div class="user-avatar-content">
                                    <div class="hexagon-image-84-92" data-src="{{ $team->avatarUrl() }}" style="background-image:url('{{ $team->avatarUrl() }}');"></div>
                                </div>
                            </a>

                            <p class="user-short-description-title small"><a href="{{ route('teams.show', $team) }}">{{ $team->name }}</a></p>
                            <p class="user-short-description-text regular">{{ $team->tagline ?: __('ui.team_default_tagline') }}</p>
                        </div>

                        <div class="user-stats">
                            <div class="user-stat"><p class="user-stat-title">{{ $membersCount }}</p><p class="user-stat-text">{{ __('ui.team_members') }}</p></div>
                            <div class="user-stat"><p class="user-stat-title">{{ strtoupper((string) ($team->platform ?: '—')) }}</p><p class="user-stat-text">{{ __('ui.platform') }}</p></div>
                            <div class="user-stat"><p class="user-stat-title">{{ $requestItem->created_at?->diffForHumans() }}</p><p class="user-stat-text">{{ __('ui.requested') }}</p></div>
                        </div>

                        <div class="hh-team-request-actions">
                            <form method="POST" action="{{ route('teams.requests.reject', [$team, $requestItem]) }}">
                                @csrf
                                <button class="button white full" type="submit" title="{{ __('ui.reject') }}">
                                    <i class="button-icon hh-ph-action-icon ph ph-user-minus" aria-hidden="true"></i>
                                    <span>{{ __('ui.reject') }}</span>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('teams.requests.accept', [$team, $requestItem]) }}">
                                @csrf
                                <button class="button secondary full" type="submit" title="{{ __('ui.accept') }}">
                                    <i class="button-icon hh-ph-action-icon ph ph-user-plus" aria-hidden="true"></i>
                                    <span>{{ __('ui.accept') }}</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="user-preview-footer">
                        <div class="user-avatar tiny no-border">
                            <div class="user-avatar-content">
                                <div class="hexagon-image-24-26" data-src="{{ $requestUser?->avatarUrl() }}" style="background-image:url('{{ $requestUser?->avatarUrl() }}');"></div>
                            </div>
                        </div>
                        <div class="hh-invited-by-text">
                            <span>{{ __('ui.requested_by') }}</span>
                            <strong>{{ $requestUser?->name }}</strong>
                        </div>
                    </div>
                </div>
            @empty
                <div class="widget-box hh-empty-hub-card">
                    <p class="widget-box-title">{{ __('ui.no_team_invitations_title') }}</p>
                    <div class="widget-box-content">
                        <p>{{ __('ui.no_team_invitations_text') }}</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
