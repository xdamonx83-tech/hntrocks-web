@extends('layouts.app')

@section('title', __('ui.moments_page_title'))

@section('content')
@if (session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="hh-alert hh-alert-danger">
        <strong>{{ __('ui.moments_errors_title') }}</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<section class="section hh-moments-overview">
    <div class="section-header hh-moments-section-header">
        <div class="section-header-info">
            <p class="section-pretitle">{{ __('ui.moments_pretitle') }}</p>
            <h2 class="section-title">{{ __('ui.moments') }} <span class="highlighted">{{ $moments->total() }}</span></h2>
            <p class="hh-moments-overview-text">{{ __('ui.moments_overview_text') }}</p>
        </div>

        <div class="section-header-actions hh-moments-header-actions">
            <a class="button primary hh-moments-create-button" href="{{ route('moments.create') }}">
                <svg class="hh-moments-create-icon icon-plus-small"><use xlink:href="#svg-plus-small"></use></svg>
                {{ __('ui.moments_upload') }}
            </a>
        </div>
    </div>

    <div class="hh-moments-quickbar">
        <article>
            <strong>{{ $stats['total'] }}</strong>
            <span>{{ __('ui.moments_public_count') }}</span>
        </article>
        <article>
            <strong>{{ $stats['mine'] }}</strong>
            <span>{{ __('ui.moments_mine_count') }}</span>
        </article>
        <article>
            <strong>{{ $stats['saved'] }}</strong>
            <span>{{ __('ui.moments_saved_count') }}</span>
        </article>
    </div>

    <nav class="hh-moments-tabs" aria-label="{{ __('ui.moments_filter_navigation') }}">
        <a class="hh-moments-tab {{ ! $mine && ! $saved ? 'is-active' : '' }}" href="{{ route('moments.index') }}">{{ __('ui.moments_filter_all') }}</a>
        <a class="hh-moments-tab {{ $mine ? 'is-active' : '' }}" href="{{ route('moments.index', ['mine' => 1]) }}">{{ __('ui.moments_filter_mine') }}</a>
        <a class="hh-moments-tab {{ $saved ? 'is-active' : '' }}" href="{{ route('moments.index', ['saved' => 1]) }}">{{ __('ui.moments_filter_saved') }}</a>
    </nav>

    @if ($moments->count())
        <div class="grid grid-3-3-3-3 centered hh-moments-video-grid">
            @foreach ($moments as $moment)
                @include('moments.partials.moment-card', ['moment' => $moment])
            @endforeach
        </div>
    @else
        <section class="widget-box hh-moments-empty-state">
            <p class="widget-box-title">{{ __('ui.moments_empty_title') }}</p>
            <p class="hh-moments-empty-text">{{ __('ui.moments_empty_text') }}</p>
            <a class="button primary hh-moments-empty-button" href="{{ route('moments.create') }}">{{ __('ui.moments_upload') }}</a>
        </section>
    @endif
</section>

@if ($moments->hasPages())
    <nav class="hh-pagination" aria-label="{{ __('ui.moments_pagination') }}">
        @if ($moments->onFirstPage())
            <span class="is-disabled">{{ __('ui.pagination_previous') }}</span>
        @else
            <a href="{{ $moments->previousPageUrl() }}">{{ __('ui.pagination_previous') }}</a>
        @endif

        <span>{{ __('ui.pagination_page_of', ['current' => $moments->currentPage(), 'last' => $moments->lastPage()]) }}</span>

        @if ($moments->hasMorePages())
            <a href="{{ $moments->nextPageUrl() }}">{{ __('ui.pagination_next') }}</a>
        @else
            <span class="is-disabled">{{ __('ui.pagination_next') }}</span>
        @endif
    </nav>
@endif
@endsection
