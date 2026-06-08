@extends('themes.hnt_preview.layouts.app')

@section('title', __('ui.preview_hashtag_page_title', ['tag' => '#'.$tag]))
@section('meta_description', __('ui.preview_hashtag_meta_description', ['tag' => '#'.$tag]))
@section('main_class', 'feed-main hnt-hashtag-main')

@section('content')
<header class="feed-header hnt-preview-feed-header hnt-hashtag-header">
    <div>
        <p class="hnt-preview-kicker">{{ __('ui.preview_hashtag_kicker') }}</p>
        <h1>#{{ $tag }}</h1>
        <p>{{ trans_choice('ui.preview_hashtag_result_count', $totalCount, ['count' => number_format($totalCount), 'tag' => '#'.$tag]) }}</p>
    </div>
    <a class="btn-create hnt-feed-single-back" href="{{ route('feed.index') }}">
        <i class="ph ph-caret-left" aria-hidden="true"></i>
        <span>{{ __('ui.back_to_feed') }}</span>
    </a>
</header>

<div class="hnt-hashtag-summary">
    <span><strong>{{ number_format($posts->count()) }}</strong>{{ __('ui.preview_hashtag_posts') }}</span>
    <span><strong>{{ number_format($moments->count()) }}</strong>{{ __('ui.preview_hashtag_moments') }}</span>
    <span><strong>{{ number_format($lfgPosts->count()) }}</strong>{{ __('ui.preview_hashtag_lfg') }}</span>
</div>

@if($relatedTags->isNotEmpty())
    <div class="hnt-hashtag-related" aria-label="{{ __('ui.preview_hashtag_related_aria') }}">
        @foreach($relatedTags as $relatedTag)
            <a class="tag" href="{{ route('hashtags.show', $relatedTag) }}">#{{ $relatedTag }}</a>
        @endforeach
    </div>
@endif

@if($totalCount <= 0)
    <section class="hnt-hashtag-empty">
        <strong>{{ __('ui.preview_hashtag_empty_title') }}</strong>
        <p>{{ __('ui.preview_hashtag_empty_text', ['tag' => '#'.$tag]) }}</p>
    </section>
@endif

@if($posts->isNotEmpty())
    <section class="hnt-hashtag-section" aria-labelledby="hntHashtagPostsTitle">
        <div class="hnt-hashtag-section-head">
            <div>
                <p class="hnt-preview-kicker">{{ __('ui.preview_hashtag_posts') }}</p>
                <h2 id="hntHashtagPostsTitle">{{ __('ui.preview_hashtag_posts_title') }}</h2>
            </div>
            <span>{{ number_format($posts->count()) }}</span>
        </div>
        <div class="feed-content hnt-preview-feed-content">
            @foreach($posts as $post)
                @include('themes.hnt_preview.feed.partials.post-card', ['post' => $post])
            @endforeach
        </div>
    </section>
@endif

@if($moments->isNotEmpty())
    <section class="hnt-hashtag-section" aria-labelledby="hntHashtagMomentsTitle">
        <div class="hnt-hashtag-section-head">
            <div>
                <p class="hnt-preview-kicker">{{ __('ui.preview_hashtag_moments') }}</p>
                <h2 id="hntHashtagMomentsTitle">{{ __('ui.preview_hashtag_moments_title') }}</h2>
            </div>
            <span>{{ number_format($moments->count()) }}</span>
        </div>
        <div class="hnt-hashtag-moment-grid">
            @foreach($moments as $moment)
                @php
                    $momentTitle = $moment->caption ?: __('ui.moments_default_caption');
                    $momentText = trim((string) ($moment->description ?: $moment->caption));
                @endphp
                <a class="hnt-hashtag-moment-card" href="{{ route('moments.show', $moment) }}">
                    <span class="hnt-hashtag-moment-thumb">
                        <img src="{{ $moment->coverUrl() }}" alt="{{ $momentTitle }}">
                        <i class="ph ph-play" aria-hidden="true"></i>
                    </span>
                    <span class="hnt-hashtag-moment-copy">
                        <strong>{{ $momentTitle }}</strong>
                        @if($momentText !== '')
                            <em>{!! \App\Support\Hashtag::renderText(\Illuminate\Support\Str::limit($momentText, 130)) !!}</em>
                        @endif
                        <small>{{ $moment->user?->name ?: $moment->user?->username }} · {{ $moment->published_at?->diffForHumans() }}</small>
                    </span>
                </a>
            @endforeach
        </div>
    </section>
@endif

@if($lfgPosts->isNotEmpty())
    <section class="hnt-hashtag-section" aria-labelledby="hntHashtagLfgTitle">
        <div class="hnt-hashtag-section-head">
            <div>
                <p class="hnt-preview-kicker">{{ __('ui.preview_hashtag_lfg') }}</p>
                <h2 id="hntHashtagLfgTitle">{{ __('ui.preview_hashtag_lfg_title') }}</h2>
            </div>
            <span>{{ number_format($lfgPosts->count()) }}</span>
        </div>
        <div class="hnt-hashtag-lfg-grid">
            @foreach($lfgPosts as $lfgPost)
                @php
                    $tags = array_values(array_filter($lfgPost->displayTags()));
                    $author = $lfgPost->user;
                @endphp
                <a class="hnt-hashtag-lfg-card" href="{{ route('lfg.show', $lfgPost) }}">
                    <span class="hnt-hashtag-lfg-cover" style="background-image: url('{{ $lfgPost->coverUrl() }}')"></span>
                    <span class="hnt-hashtag-lfg-copy">
                        <strong>{{ $lfgPost->title }}</strong>
                        <em>{!! \App\Support\Hashtag::renderText(\Illuminate\Support\Str::limit((string) $lfgPost->body, 150)) !!}</em>
                        <small>{{ $author?->name ?: $author?->username }} · {{ $lfgPost->statusLabel() }}</small>
                        @if(count($tags))
                            <span class="hnt-hashtag-lfg-tags">
                                @foreach(array_slice($tags, 0, 4) as $item)
                                    <b>{{ $item }}</b>
                                @endforeach
                            </span>
                        @endif
                    </span>
                </a>
            @endforeach
        </div>
    </section>
@endif
@endsection
