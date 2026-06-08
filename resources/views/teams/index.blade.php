@extends('layouts.app')

@section('title', __('ui.teams_index_title'))

@section('content')
@php
    $filters = $filters ?? [];
    $activeSort = $filters['sort'] ?? 'newest';
    $baseFilterParams = request()->except(['page', 'sort']);
    $sortUrl = fn (string $sort) => route('teams.index', array_merge($baseFilterParams, ['sort' => $sort]));
@endphp

<div class="section-banner hh-teams-section-banner">
    <img class="section-banner-icon" src="{{ asset('assets/vikinger/img/banner/groups-icon.png') }}" alt="{{ __('ui.teams') }}">
    <p class="section-banner-title">{{ __('ui.teams_index_banner_title', ['count' => $teams->total()]) }}</p>
    <p class="section-banner-text">{{ __('ui.teams_index_banner_text') }}</p>
</div>

@if (session('status'))
    <div class="hh-toast-stack" aria-live="polite" aria-atomic="true">
        <div class="hh-toast hh-toast-success">
            <i class="hh-toast-icon hh-ph-action-icon ph ph-check" aria-hidden="true"></i>
            <span>{{ session('status') }}</span>
        </div>
    </div>
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

<div class="section-filters-bar v1 hh-teams-filterbar">
    <div class="section-filters-bar-actions">
        <form class="form hh-teams-vikinger-filter" method="GET" action="{{ route('teams.index') }}">
            @foreach (['platform', 'playstyle', 'region', 'language'] as $preservedFilter)
                @if (! blank($filters[$preservedFilter] ?? null))
                    <input type="hidden" name="{{ $preservedFilter }}" value="{{ $filters[$preservedFilter] }}">
                @endif
            @endforeach

            <div class="form-input small with-button hh-teams-search-input">
                <label for="teams-search">{{ __('ui.teams_search_label') }}</label>
                <input type="text" id="teams-search" name="q" value="{{ $filters['q'] ?? '' }}">
                <button class="button primary" type="submit" aria-label="{{ __('ui.search') }}">
                    <i class="hh-ph-action-icon ph ph-magnifying-glass" aria-hidden="true"></i>
                </button>
            </div>

            <div class="form-select hh-teams-filter-select">
                <label for="teams-filter-category">{{ __('ui.filter_by') }}</label>
                <select id="teams-filter-category" name="sort" onchange="this.form.submit()">
                    <option value="newest" @selected($activeSort === 'newest')>{{ __('ui.sort_newest') }}</option>
                    <option value="members" @selected($activeSort === 'members')>{{ __('ui.sort_members') }}</option>
                    <option value="name" @selected($activeSort === 'name')>{{ __('ui.sort_name') }}</option>
                </select>
                <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
            </div>

            <label class="hh-teams-recruiting-toggle">
                <input type="checkbox" name="recruiting" value="1" @checked((bool) ($filters['recruiting'] ?? false)) onchange="this.form.submit()">
                <span>{{ __('ui.teams_recruiting_only') }}</span>
            </label>
        </form>

        <div class="filter-tabs hh-team-sort-tabs">
            <a class="filter-tab {{ $activeSort === 'newest' ? 'active' : '' }}" href="{{ $sortUrl('newest') }}">
                <p class="filter-tab-text">{{ __('ui.sort_newest') }}</p>
            </a>
            <a class="filter-tab {{ $activeSort === 'members' ? 'active' : '' }}" href="{{ $sortUrl('members') }}">
                <p class="filter-tab-text">{{ __('ui.sort_members') }}</p>
            </a>
            <a class="filter-tab {{ $activeSort === 'name' ? 'active' : '' }}" href="{{ $sortUrl('name') }}">
                <p class="filter-tab-text">{{ __('ui.sort_name') }}</p>
            </a>
        </div>
    </div>

    <div class="section-filters-bar-actions hh-team-index-actions">
        <a class="button secondary hh-team-create-button" href="{{ route('teams.create') }}">
            <i class="button-icon hh-ph-action-icon ph ph-plus" aria-hidden="true"></i>
            {{ __('ui.create_team') }}
        </a>

    </div>
</div>

@if ($teams->isEmpty())
    <div class="widget-box hh-empty-state hh-teams-empty-state">
        <p class="widget-box-title">{{ __('ui.teams_no_results_title') }}</p>
        <p class="widget-box-text">{{ __('ui.teams_no_results_text') }}</p>
        <a class="button secondary" href="{{ route('teams.create') }}">{{ __('ui.create_team') }}</a>
    </div>
@else
    <div class="grid grid-4-4-4 centered hh-teams-vikinger-grid">
        @foreach ($teams as $team)
            @include('teams.partials.team-card', ['team' => $team])
        @endforeach
    </div>
@endif

@if ($teams->hasPages())
    <div class="section-pager-bar hh-teams-pager">
        <div class="section-pager">
            @if ($teams->onFirstPage())
                <span class="section-pager-item disabled">{{ __('ui.previous') }}</span>
            @else
                <a class="section-pager-item" href="{{ $teams->previousPageUrl() }}">{{ __('ui.previous') }}</a>
            @endif

            <span class="section-pager-item active">{{ $teams->currentPage() }}</span>

            @if ($teams->hasMorePages())
                <a class="section-pager-item" href="{{ $teams->nextPageUrl() }}">{{ __('ui.next') }}</a>
            @else
                <span class="section-pager-item disabled">{{ __('ui.next') }}</span>
            @endif
        </div>
    </div>
@endif
@endsection
