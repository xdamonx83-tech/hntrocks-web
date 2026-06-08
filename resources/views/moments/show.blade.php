@extends('layouts.app')

@php
    $viewer = auth()->user();
    $caption = $moment->caption ?: __('ui.moments_default_caption');
    $authorName = $moment->user->name ?: $moment->user->username;
    $authorHandle = '@'.$moment->user->username;
    $isLiked = $moment->isLikedBy($viewer);
    $isBookmarked = $moment->isBookmarkedBy($viewer);
    $videoUrl = $moment->mediaUrl();
    $videoMime = $moment->media?->mime_type ?: 'video/mp4';
    $videoPoster = $moment->cover?->thumbnailUrl() ?: ($moment->media?->thumbnail_path ? $moment->media->thumbnailUrl() : null);
@endphp

@section('title', $caption.' · hnt.rocks')

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

<section class="hh-moment-reel-page" data-hh-moment-fullscreen aria-label="{{ __('ui.moment_reel_aria') }}">
    <div class="hh-moment-reel-topbar">
        <a class="hh-moment-reel-back" href="{{ route('moments.index') }}">
            <svg class="hh-moment-reel-back-icon icon-back-arrow"><use xlink:href="#svg-back-arrow"></use></svg>
            <span>{{ __('ui.moment_back_to_overview') }}</span>
        </a>

        <div class="hh-moment-reel-context">
            <p>{{ __('ui.moment_reel_pretitle') }}</p>
            <h1>{{ \Illuminate\Support\Str::limit($caption, 64) }}</h1>
        </div>
    </div>

    <div class="hh-moment-reel-layout">
        <aside class="hh-moment-reel-nav" aria-label="{{ __('ui.moment_reel_navigation') }}">
            @if (! empty($previousMoment))
                <a class="hh-moment-reel-nav-button text-tooltip-tft-medium" href="{{ route('moments.show', $previousMoment) }}" data-title="{{ __('ui.moment_previous') }}" aria-label="{{ __('ui.moment_previous') }}">
                    <svg class="icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                </a>
            @else
                <span class="hh-moment-reel-nav-button is-disabled" aria-hidden="true">
                    <svg class="icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                </span>
            @endif

            @if (! empty($nextMoment))
                <a class="hh-moment-reel-nav-button is-next text-tooltip-tft-medium" href="{{ route('moments.show', $nextMoment) }}" data-title="{{ __('ui.moment_next') }}" aria-label="{{ __('ui.moment_next') }}">
                    <svg class="icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                </a>
            @else
                <span class="hh-moment-reel-nav-button is-next is-disabled" aria-hidden="true">
                    <svg class="icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                </span>
            @endif
        </aside>

        <section class="hh-moment-reel-stage">
            <div class="hh-moment-reel-phone" data-hh-moment-reel>
                <video class="hh-moment-reel-video" controls playsinline webkit-playsinline preload="metadata" @if ($videoPoster) poster="{{ $videoPoster }}" @endif data-hh-moment-reel-video>
                    <source src="{{ $videoUrl }}" type="{{ $videoMime }}">
                    {{ __('ui.moment_video_not_supported') }}
                </video>

                <button class="hh-moment-reel-play-toggle" type="button" data-hh-moment-reel-play aria-label="{{ __('ui.moment_play_video') }}">
                    <svg class="icon-play"><use xlink:href="#svg-play"></use></svg>
                </button>

                <div class="hh-moment-reel-gradient" aria-hidden="true"></div>

                <div class="hh-moment-reel-overlay">
                    <a class="hh-moment-reel-author" href="{{ route('profile.public', $moment->user) }}">
                        <img src="{{ $moment->user->avatarUrl() }}" alt="">
                        <span>{{ $authorHandle }}</span>
                    </a>

                    <div class="hh-moment-reel-copy">
                        <h2>{{ $caption }}</h2>
                        @if ($moment->description)
                            <p>{{ \Illuminate\Support\Str::limit($moment->description, 180) }}</p>
                        @endif
                    </div>
                </div>

                <div class="hh-moment-reel-actions" aria-label="{{ __('ui.moment_actions') }}">
                    <form class="hh-moment-reel-action-form" method="post" action="{{ route('moments.reactions.toggle', $moment) }}">
                        @csrf
                        <button class="hh-moment-reel-action {{ $isLiked ? 'is-active' : '' }} text-tooltip-tft-medium" type="submit" data-title="{{ $isLiked ? __('ui.moment_unlike') : __('ui.moment_like') }}" aria-label="{{ $isLiked ? __('ui.moment_unlike') : __('ui.moment_like') }}">
                            <svg class="icon-thumbs-up"><use xlink:href="#svg-thumbs-up"></use></svg>
                        </button>
                        <span>{{ $moment->likes_count }}</span>
                    </form>

                    <button class="hh-moment-reel-action text-tooltip-tft-medium" type="button" data-hh-moment-comments-toggle data-title="{{ __('ui.moment_comments') }}" aria-label="{{ __('ui.moment_comments') }}">
                        <svg class="icon-comment"><use xlink:href="#svg-comment"></use></svg>
                    </button>
                    <span class="hh-moment-reel-action-count">{{ $moment->comments_count }}</span>

                    <form class="hh-moment-reel-action-form" method="post" action="{{ route('moments.bookmarks.toggle', $moment) }}">
                        @csrf
                        <button class="hh-moment-reel-action {{ $isBookmarked ? 'is-active' : '' }} text-tooltip-tft-medium" type="submit" data-title="{{ $isBookmarked ? __('ui.moment_unsave') : __('ui.moment_save') }}" aria-label="{{ $isBookmarked ? __('ui.moment_unsave') : __('ui.moment_save') }}">
                            <svg class="icon-star"><use xlink:href="#svg-star"></use></svg>
                        </button>
                        <span>{{ $moment->bookmarks_count }}</span>
                    </form>

                    <span class="hh-moment-reel-views" title="{{ __('ui.moment_views') }}">
                        <strong>{{ $moment->views_count }}</strong>
                        <small>{{ __('ui.moment_views_short') }}</small>
                    </span>

                    @if ((int) $moment->user_id !== (int) auth()->id())
                        <button class="hh-moment-reel-action hh-moment-report-action text-tooltip-tft-medium" type="button" data-title="{{ __('ui.moment_report') }}" aria-label="{{ __('ui.moment_report') }}" data-hh-report-open data-hh-report-type="moment" data-hh-report-id="{{ $moment->id }}" data-hh-report-label="{{ __('ui.moment_report_label', ['title' => $caption]) }}">
                            <i class="hh-ph-action-icon ph ph-warning-octagon" aria-hidden="true"></i>
                        </button>
                    @endif
                </div>
            </div>
        </section>

        <aside class="hh-moment-reel-panel">
            <section class="hh-moment-reel-card hh-moment-reel-author-card">
                <a class="hh-moment-panel-author" href="{{ route('profile.public', $moment->user) }}">
                    <img src="{{ $moment->user->avatarUrl() }}" alt="">
                    <span>
                        <strong>{{ $authorName }}</strong>
                        <small>{{ $authorHandle }}</small>
                    </span>
                </a>

                <p class="hh-moment-panel-time">{{ __('ui.moment_posted_at', ['time' => $moment->created_at->diffForHumans()]) }}</p>

                @if ($moment->description)
                    <p class="hh-moment-panel-description">{{ $moment->description }}</p>
                @else
                    <p class="hh-moment-panel-description is-muted">{{ __('ui.moment_no_description') }}</p>
                @endif

                <div class="hh-moment-panel-stats" aria-label="{{ __('ui.moments_stats_label') }}">
                    <span><strong>{{ $moment->likes_count }}</strong>{{ __('ui.moment_likes') }}</span>
                    <span><strong>{{ $moment->comments_count }}</strong>{{ __('ui.moment_comments') }}</span>
                    <span><strong>{{ $moment->bookmarks_count }}</strong>{{ __('ui.moment_saves') }}</span>
                    <span><strong>{{ $moment->views_count }}</strong>{{ __('ui.moment_views') }}</span>
                </div>

                @if ($moment->canBeManagedBy($viewer))
                    <form class="hh-moment-archive-form" method="post" action="{{ route('moments.destroy', $moment) }}">
                        @csrf
                        @method('DELETE')
                        <button class="hh-moment-archive-button" type="submit">{{ __('ui.moment_archive') }}</button>
                    </form>
                @endif
            </section>

            <section id="moment-comments" class="hh-moment-reel-card hh-moment-comments-card" data-hh-moment-comments-panel>
                <div class="hh-moment-card-title-row">
                    <h2>{{ __('ui.moment_comments') }}</h2>
                    <span>{{ $moment->comments_count }}</span>
                    <button class="hh-moment-comments-close" type="button" data-hh-moment-comments-close aria-label="{{ __('ui.close') }}">
                        <i class="ph ph-x" aria-hidden="true"></i>
                    </button>
                </div>

                <form class="hh-moment-comment-form" method="post" action="{{ route('moments.comments.store', $moment) }}">
                    @csrf
                    <textarea name="body" rows="3" placeholder="{{ __('ui.moment_comment_placeholder') }}" required>{{ old('body') }}</textarea>
                    <button class="hh-moment-comment-submit text-tooltip-tft-medium" type="submit" data-title="{{ __('ui.moment_comment_submit') }}" aria-label="{{ __('ui.moment_comment_submit') }}">
                        <svg class="icon-send-message"><use xlink:href="#svg-send-message"></use></svg>
                    </button>
                </form>

                <div class="hh-moment-comments-list">
                    @forelse ($moment->comments as $comment)
                        <article class="hh-moment-comment-item">
                            <img src="{{ $comment->user->avatarUrl() }}" alt="">
                            <div>
                                <header>
                                    <strong>{{ $comment->user->username }}</strong>
                                    <small>{{ $comment->created_at->diffForHumans() }}</small>
                                </header>
                                <p>{{ $comment->body }}</p>
                                <div class="hh-moment-comment-actions">
                                    @if ((int) $comment->user_id !== (int) auth()->id())
                                        <button class="hh-moment-comment-action hh-moment-comment-report" type="button" data-hh-report-open data-hh-report-type="moment_comment" data-hh-report-id="{{ $comment->id }}" data-hh-report-label="{{ __('ui.moment_comment_report_label', ['name' => $comment->user->name]) }}">{{ __('ui.report_short') }}</button>
                                    @endif

                                    @if ((int) $comment->user_id === (int) auth()->id() || (int) $moment->user_id === (int) auth()->id())
                                        <form method="post" action="{{ route('moments.comments.destroy', $comment) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit">{{ __('ui.moment_comment_delete') }}</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <p class="hh-moment-comments-empty">{{ __('ui.moment_no_comments') }}</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</section>

@endsection
