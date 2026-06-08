@extends('layouts.app')

@section('title', __('ui.team_lfg_index_title') . ' · hnt.rocks')

@section('content')
<div class="hh-page-header">
    <div>
        <p class="hh-kicker">{{ __('ui.team_lfg_index_kicker') }}</p>
        <h1>{{ __('ui.team_lfg_index_heading') }}</h1>
        <p>{{ __('ui.team_lfg_index_text') }}</p>
    </div>
    <div class="hh-page-header-actions">
        <a class="hh-primary-button" href="{{ route('team-lfg.create') }}">{{ __('ui.team_lfg_create') }}</a>
    </div>
</div>

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

<section class="hh-card hh-card-compact hh-filter-card">
    <form method="get" action="{{ route('team-lfg.index') }}" class="hh-lfg-filter">
        <div>
            <label for="q">{{ __('ui.team_lfg_search_label') }}</label>
            <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('ui.team_lfg_search_placeholder') }}">
        </div>
        <div>
            <label for="type">{{ __('ui.team_lfg_type_label') }}</label>
            <select id="type" name="type">
                <option value="">{{ __('ui.all') }}</option>
                <option value="team_seeks_players" @selected(($filters['type'] ?? '') === 'team_seeks_players')>{{ __('ui.team_lfg_type_team_seeks_players') }}</option>
                <option value="player_seeks_team" @selected(($filters['type'] ?? '') === 'player_seeks_team')>{{ __('ui.team_lfg_type_player_seeks_team') }}</option>
            </select>
        </div>
        <div>
            <label for="platform">{{ __('ui.team_lfg_platform') }}</label>
            <select id="platform" name="platform">
                <option value="">{{ __('ui.all') }}</option>
                @foreach (['PC', 'PlayStation', 'Xbox', 'Crossplay'] as $option)
                    <option value="{{ $option }}" @selected(($filters['platform'] ?? '') === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="playstyle">{{ __('ui.team_lfg_playstyle') }}</label>
            <select id="playstyle" name="playstyle">
                <option value="">{{ __('ui.all') }}</option>
                @foreach (['Entspannt', 'Taktisch', 'Aggressiv', 'Competitive', 'Einsteigerfreundlich'] as $option)
                    <option value="{{ $option }}" @selected(($filters['playstyle'] ?? '') === $option)>{{ \App\Models\TeamLfgPost::localizedOptionLabelFor('playstyle', $option) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="region">{{ __('ui.team_lfg_region') }}</label>
            <select id="region" name="region">
                <option value="">{{ __('ui.all') }}</option>
                @foreach (['EU', 'US East', 'US West', 'Asia', 'Oceania'] as $option)
                    <option value="{{ $option }}" @selected(($filters['region'] ?? '') === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="status">{{ __('ui.status') }}</label>
            <select id="status" name="status">
                <option value="">{{ __('ui.all') }}</option>
                <option value="open" @selected(($filters['status'] ?? '') === 'open')>{{ __('ui.team_lfg_status_open') }}</option>
                <option value="filled" @selected(($filters['status'] ?? '') === 'filled')>{{ __('ui.team_lfg_status_filled') }}</option>
                <option value="closed" @selected(($filters['status'] ?? '') === 'closed')>{{ __('ui.team_lfg_status_closed') }}</option>
            </select>
        </div>
        <label class="hh-checkline hh-checkline-card hh-lfg-check-filter">
            <input type="checkbox" name="voice_required" value="1" @checked((bool) ($filters['voice_required'] ?? false))>
            {{ __('ui.team_lfg_voice_only') }}
        </label>
        <label class="hh-checkline hh-checkline-card hh-lfg-check-filter">
            <input type="checkbox" name="mine" value="1" @checked((bool) ($filters['mine'] ?? false))>
            {{ __('ui.team_lfg_only_mine') }}
        </label>
        <div class="hh-filter-actions">
            <button class="hh-primary-button" type="submit">{{ __('ui.team_lfg_filter_submit') }}</button>
            <a class="hh-secondary-button" href="{{ route('team-lfg.index') }}">{{ __('ui.team_lfg_filter_reset') }}</a>
        </div>
    </form>
</section>

<div class="hh-lfg-grid">
    @forelse ($posts as $post)
        @include('team-lfg.partials.team-lfg-card', ['post' => $post])
    @empty
        <section class="hh-card hh-card-compact hh-empty-state">
            <h2>{{ __('ui.team_lfg_empty_title') }}</h2>
            <p>{{ __('ui.team_lfg_empty_index_text') }}</p>
            <a class="hh-primary-button" href="{{ route('team-lfg.create') }}">{{ __('ui.team_lfg_create') }}</a>
        </section>
    @endforelse
</div>

@if ($posts->hasPages())
    <nav class="hh-pagination" aria-label="{{ __('ui.team_lfg_pagination_aria') }}">
        @if ($posts->onFirstPage())
            <span class="is-disabled">{{ __('ui.pagination_previous') }}</span>
        @else
            <a href="{{ $posts->previousPageUrl() }}">{{ __('ui.pagination_previous') }}</a>
        @endif

        <span>{{ __('ui.team_lfg_page_of', ['current' => $posts->currentPage(), 'last' => $posts->lastPage()]) }}</span>

        @if ($posts->hasMorePages())
            <a href="{{ $posts->nextPageUrl() }}">{{ __('ui.pagination_next') }}</a>
        @else
            <span class="is-disabled">{{ __('ui.pagination_next') }}</span>
        @endif
    </nav>
@endif
@endsection
