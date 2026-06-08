@extends('layouts.app')

@section('title', __('ui.lfg_index_title') . ' · hnt.rocks')

@section('content')
@php
    $filters = $filters ?? [];
    $activeSort = $filters['sort'] ?? 'newest';
    $activeStatus = $filters['status'] ?? '';
    $baseParams = request()->except(['page']);
    $sortParams = request()->except(['page', 'sort']);
    $statusParams = request()->except(['page', 'status']);
    $sortUrl = fn (string $sort) => route('lfg.index', array_merge($sortParams, ['sort' => $sort]));
    $statusUrl = fn (?string $status) => route('lfg.index', $status ? array_merge($statusParams, ['status' => $status]) : $statusParams);
@endphp

<div class="section-banner hh-lfg-section-banner">
    <img class="section-banner-icon" src="{{ asset('assets/vikinger/img/banner/groups-icon.png') }}" alt="{{ __('ui.lfg') }}">
    <p class="section-banner-title">{{ __('ui.lfg_index_heading', ['count' => $posts->total()]) }}</p>
    <p class="section-banner-text">{{ __('ui.lfg_index_banner_text') }}</p>
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

<div class="section-filters-bar v1 hh-lfg-filterbar">
    <div class="section-filters-bar-actions">
        <form class="form hh-lfg-vikinger-filter" method="GET" action="{{ route('lfg.index') }}">
            @foreach (['status', 'voice_required', 'mine'] as $preservedFilter)
                @if (! blank($filters[$preservedFilter] ?? null))
                    <input type="hidden" name="{{ $preservedFilter }}" value="{{ $filters[$preservedFilter] }}">
                @endif
            @endforeach

            <div class="form-input small with-button hh-lfg-search-input">
                <label for="lfg-search">{{ __('ui.lfg_search_label') }}</label>
                <input type="text" id="lfg-search" name="q" value="{{ $filters['q'] ?? '' }}">
                <button class="button primary" type="submit" aria-label="{{ __('ui.search') }}">
                    <i class="hh-ph-action-icon ph ph-magnifying-glass" aria-hidden="true"></i>
                </button>
            </div>

            <div class="form-select hh-lfg-filter-select">
                <label for="lfg-platform">{{ __('ui.lfg_platform_filter') }}</label>
                <select id="lfg-platform" name="platform" onchange="this.form.submit()">
                    <option value="">{{ __('ui.lfg_filter_all') }}</option>
                    @foreach (['PC', 'PlayStation', 'Xbox', 'Crossplay'] as $option)
                        <option value="{{ $option }}" @selected(($filters['platform'] ?? '') === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
            </div>

            <div class="form-select hh-lfg-filter-select">
                <label for="lfg-sort">{{ __('ui.lfg_sort_label') }}</label>
                <select id="lfg-sort" name="sort" onchange="this.form.submit()">
                    <option value="newest" @selected($activeSort === 'newest')>{{ __('ui.lfg_sort_newest') }}</option>
                    <option value="open_slots" @selected($activeSort === 'open_slots')>{{ __('ui.lfg_sort_open_slots') }}</option>
                    <option value="applications" @selected($activeSort === 'applications')>{{ __('ui.lfg_sort_applications') }}</option>
                    <option value="expiring" @selected($activeSort === 'expiring')>{{ __('ui.lfg_sort_expiring') }}</option>
                </select>
                <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
            </div>

            <label class="hh-lfg-voice-toggle">
                <input type="checkbox" name="voice_required" value="1" @checked((bool) ($filters['voice_required'] ?? false)) onchange="this.form.submit()">
                <span>{{ __('ui.lfg_voice_only') }}</span>
            </label>

            <label class="hh-lfg-voice-toggle">
                <input type="checkbox" name="mine" value="1" @checked((bool) ($filters['mine'] ?? false)) onchange="this.form.submit()">
                <span>{{ __('ui.lfg_only_mine') }}</span>
            </label>
        </form>

        <div class="filter-tabs hh-lfg-status-tabs">
            <a class="filter-tab {{ $activeStatus === '' ? 'active' : '' }}" href="{{ $statusUrl(null) }}">
                <p class="filter-tab-text">{{ __('ui.lfg_status_all') }}</p>
            </a>
            <a class="filter-tab {{ $activeStatus === 'open' ? 'active' : '' }}" href="{{ $statusUrl('open') }}">
                <p class="filter-tab-text">{{ __('ui.lfg_status_open') }}</p>
            </a>
            <a class="filter-tab {{ $activeStatus === 'full' ? 'active' : '' }}" href="{{ $statusUrl('full') }}">
                <p class="filter-tab-text">{{ __('ui.lfg_status_full') }}</p>
            </a>
            <a class="filter-tab {{ $activeStatus === 'closed' ? 'active' : '' }}" href="{{ $statusUrl('closed') }}">
                <p class="filter-tab-text">{{ __('ui.lfg_status_closed') }}</p>
            </a>
        </div>
    </div>

    <div class="section-filters-bar-actions hh-lfg-index-actions">
        <a class="button secondary hh-lfg-create-button" href="{{ route('lfg.create') }}">
            <i class="button-icon hh-ph-action-icon ph ph-plus" aria-hidden="true"></i>
            {{ __('ui.lfg_create') }}
        </a>
    </div>
</div>

@if ($posts->isEmpty())
    <div class="widget-box hh-empty-state hh-lfg-empty-state">
        <p class="widget-box-title">{{ __('ui.lfg_empty_title') }}</p>
        <p class="widget-box-text">{{ __('ui.lfg_empty_text') }}</p>
        <a class="button secondary" href="{{ route('lfg.create') }}">{{ __('ui.lfg_create') }}</a>
    </div>
@else
    <div class="grid grid-4-4-4 centered hh-lfg-vikinger-grid">
        @foreach ($posts as $post)
            @include('lfg.partials.lfg-card', ['post' => $post])
        @endforeach
    </div>
@endif

@if ($posts->hasPages())
    <div class="section-pager-bar hh-lfg-pager">
        <div class="section-pager">
            @if ($posts->onFirstPage())
                <span class="section-pager-item disabled">{{ __('ui.pagination_previous') }}</span>
            @else
                <a class="section-pager-item" href="{{ $posts->previousPageUrl() }}">{{ __('ui.pagination_previous') }}</a>
            @endif

            <span class="section-pager-item active">{{ $posts->currentPage() }}</span>

            @if ($posts->hasMorePages())
                <a class="section-pager-item" href="{{ $posts->nextPageUrl() }}">{{ __('ui.pagination_next') }}</a>
            @else
                <span class="section-pager-item disabled">{{ __('ui.pagination_next') }}</span>
            @endif
        </div>
    </div>
@endif


@endsection
