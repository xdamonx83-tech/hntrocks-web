@extends('themes.hnt_preview.layouts.app')

@php
    $socialiteFeedFilter = $socialiteFeedFilter ?? 'all';
    $feedFilters = [
        'all' => __('ui.preview_feed_filter_all'),
        'friends' => __('ui.preview_feed_filter_friends'),
        'media' => __('ui.preview_feed_filter_media'),
        'mentions' => __('ui.preview_feed_filter_mentions'),
    ];

    $filterUrl = function (string $filterKey): string {
        $query = request()->query();
        unset($query['page'], $query['fragment']);

        if ($filterKey === 'all') {
            unset($query['filter']);
        } else {
            $query['filter'] = $filterKey;
        }

        return request()->url() . (count($query) ? '?' . http_build_query($query) : '');
    };

    $postCount = method_exists($socialitePosts, 'total') ? (int) $socialitePosts->total() : (int) $socialitePosts->count();
@endphp

@section('title', __('ui.preview_feed_page_title'))
@section('meta_description', __('ui.preview_feed_meta_description'))

@section('content')
<header class="feed-header hnt-preview-feed-header">
    <div>
        <h1>HNT Feed</h1>
        <p>{{ __('ui.preview_feed_count_line', ['count' => number_format($postCount)]) }}</p>
    </div>
</header>

@if(session('status'))
    <div class="hnt-preview-status-line">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="hnt-preview-status-line hnt-preview-status-error">
        {{ $errors->first() }}
    </div>
@endif

<div class="hnt-feed-filter-row" aria-label="{{ __('ui.preview_feed_filter_aria') }}">
    @foreach($feedFilters as $filterKey => $filterLabel)
        <a href="{{ $filterUrl($filterKey) }}" class="tag {{ $socialiteFeedFilter === $filterKey ? 'active' : '' }}" @if($socialiteFeedFilter === $filterKey) aria-current="page" @endif>{{ $filterLabel }}</a>
    @endforeach
</div>

<div class="feed-content hnt-preview-feed-content">
    @include('themes.hnt_preview.feed.partials.post-items', ['socialitePosts' => $socialitePosts])
</div>
@endsection
