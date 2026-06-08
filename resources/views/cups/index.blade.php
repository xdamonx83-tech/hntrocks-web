@extends('layouts.app')

@section('title', __('ui.cup_index_title'))

@section('content')
@php
    $canCreateCups = auth()->user()?->isAdmin() === true;
    $filters = $filters ?? [];
    $activeStatus = $filters['status'] ?? '';
    $baseFilterParams = request()->except(['page', 'status', 'q']);
    $statusUrl = fn (?string $status) => route('cups.index', array_filter(array_merge($baseFilterParams, ['status' => $status]), fn ($value) => $value !== null && $value !== ''));
@endphp

<div class="section-banner hh-cups-section-banner">
    <img class="section-banner-icon" src="{{ asset('assets/vikinger/img/banner/events-icon.png') }}" alt="Cups">
    <p class="section-banner-title">{{ __('ui.cup_index_banner_title', ['count' => $cups->total()]) }}</p>
    <p class="section-banner-text">{{ __('ui.cup_index_banner_text') }}</p>
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

<div class="section-filters-bar v1 hh-cups-filterbar">
    <div class="section-filters-bar-actions">
        <form class="form hh-cups-vikinger-filter hh-cups-vikinger-filter-compact" method="GET" action="{{ route('cups.index') }}">
            @if (! blank($filters['status'] ?? null))
                <input type="hidden" name="status" value="{{ $filters['status'] }}">
            @endif

            <div class="form-select hh-cups-filter-select">
                <label for="cups-filter-platform">{{ __('ui.cup_platform_filter') }}</label>
                <select id="cups-filter-platform" name="platform" onchange="this.form.submit()">
                    <option value="">{{ __('ui.cup_all_platforms') }}</option>
                    @foreach (['PC', 'PlayStation', 'Xbox', 'PC / PlayStation / Xbox'] as $option)
                        <option value="{{ $option }}" @selected(($filters['platform'] ?? '') === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
            </div>

            @auth
                <label class="hh-cups-mine-toggle">
                    <input type="checkbox" name="mine" value="1" @checked((bool) ($filters['mine'] ?? false)) onchange="this.form.submit()">
                    <span>{{ __('ui.cup_only_mine') }}</span>
                </label>
            @endauth
        </form>

        <div class="filter-tabs hh-cup-status-tabs">
            <a class="filter-tab {{ $activeStatus === '' ? 'active' : '' }}" href="{{ $statusUrl(null) }}">
                <p class="filter-tab-text">{{ __('ui.cup_filter_all') }}</p>
            </a>
            @foreach (['planned' => __('ui.cup_status_planned'), 'active' => __('ui.cup_status_active'), 'finished' => __('ui.cup_status_finished'), 'archived' => __('ui.cup_status_archived')] as $value => $label)
                <a class="filter-tab {{ $activeStatus === $value ? 'active' : '' }}" href="{{ $statusUrl($value) }}">
                    <p class="filter-tab-text">{{ $label }}</p>
                </a>
            @endforeach
        </div>
    </div>

    <div class="section-filters-bar-actions hh-cup-index-actions">
        <a class="button secondary hh-cup-create-button" href="{{ route('hall-of-fame.index') }}">
            <i class="button-icon hh-ph-action-icon ph ph-trophy" aria-hidden="true"></i>
            {{ __('ui.hall_of_fame') }}
        </a>

        @if ($canCreateCups)
            <a class="button secondary hh-cup-create-button" href="{{ route('cups.create') }}">
                <i class="button-icon hh-ph-action-icon ph ph-plus" aria-hidden="true"></i>
                {{ __('ui.cup_create_title') }}
            </a>
        @endif
    </div>
</div>

@if ($cups->isEmpty())
    <div class="widget-box hh-empty-state hh-cups-empty-state">
        <p class="widget-box-title">{{ __('ui.cup_empty_title') }}</p>
        <p class="widget-box-text">{{ __('ui.cup_empty_text') }}</p>
        @if ($canCreateCups)
            <a class="button secondary" href="{{ route('cups.create') }}">{{ __('ui.cup_create_title') }}</a>
        @endif
    </div>
@else
    <div class="grid grid-4-4-4 centered hh-cups-vikinger-grid">
        @foreach ($cups as $cup)
            @include('cups.partials.cup-card', ['cup' => $cup])
        @endforeach
    </div>
@endif

@if ($cups->hasPages())
    <div class="section-pager-bar hh-cups-pager">
        <div class="section-pager">
            @if ($cups->onFirstPage())
                <span class="section-pager-item disabled">{{ __('ui.pagination_previous') }}</span>
            @else
                <a class="section-pager-item" href="{{ $cups->previousPageUrl() }}">{{ __('ui.pagination_previous') }}</a>
            @endif

            <span class="section-pager-item active">{{ $cups->currentPage() }}</span>

            @if ($cups->hasMorePages())
                <a class="section-pager-item" href="{{ $cups->nextPageUrl() }}">{{ __('ui.pagination_next') }}</a>
            @else
                <span class="section-pager-item disabled">{{ __('ui.pagination_next') }}</span>
            @endif
        </div>
    </div>
@endif
@endsection
