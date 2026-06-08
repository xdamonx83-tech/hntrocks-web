@extends('layouts.app')

@section('title', __('ui.manage_teams') . ' · hnt.rocks')

@section('content')
<div class="section-banner hh-account-hub-banner">
    <img class="section-banner-icon" src="{{ asset('assets/vikinger/img/banner/accounthub-icon.png') }}" alt="{{ __('ui.account_hub') }}">
    <p class="section-banner-title">{{ __('ui.account_hub') }}</p>
    <p class="section-banner-text">{{ __('ui.account_hub_groups_text') }}</p>
</div>

<div class="grid grid-3-9 medium-space hh-account-hub-grid hh-teams-hub-grid">
    @include('teams.partials.account-sidebar', ['activeSection' => 'teams', 'activeLink' => 'manage'])

    <div class="account-hub-content">
        <div class="section-header">
            <div class="section-header-info">
                <p class="section-pretitle">{{ __('ui.teams') }}</p>
                <h2 class="section-title">{{ __('ui.manage_teams') }}</h2>
            </div>
        </div>

        @if (session('status'))
            <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
        @endif

        <div class="grid grid-3-3-3 centered-on-mobile hh-account-team-grid">
            <a class="create-entity-box hh-create-team-box" href="{{ route('teams.create') }}">
                <div class="create-entity-box-cover"></div>
                <div class="create-entity-box-avatar">
                    <i class="create-entity-box-avatar-icon hh-ph-action-icon ph ph-users-three" aria-hidden="true"></i>
                </div>
                <div class="create-entity-box-info">
                    <p class="create-entity-box-title">{{ __('ui.create_new_team') }}</p>
                    <p class="create-entity-box-text">{{ __('ui.create_new_team_text') }}</p>
                    <span class="button secondary full">{{ __('ui.start_creating') }}</span>
                </div>
            </a>

            @foreach ($managedTeams as $team)
                @include('teams.partials.account-team-card', [
                    'team' => $team,
                ])
            @endforeach
        </div>
    </div>
</div>
@endsection
