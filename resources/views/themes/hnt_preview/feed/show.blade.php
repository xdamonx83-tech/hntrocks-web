@extends('themes.hnt_preview.layouts.app')

@php
    /** @var \App\Models\FeedPost $post */
    $author = $post->user;
    $authorName = $author?->name ?: ($author?->username ?: __('ui.preview_hnt_hunter'));
    $currentUser = auth()->user();
    $displayName = $currentUser?->name ?: ($currentUser?->username ?: __('ui.preview_hnt_hunter'));
    $avatarUrl = $currentUser?->avatarUrl();
    $comments = $post->relationLoaded('comments') ? $post->comments->sortBy('created_at')->values() : collect();
    $rootComments = $comments->whereNull('parent_id')->values();
    $replyGroups = $comments->whereNotNull('parent_id')->groupBy('parent_id');
    $reportedFeedKeys = $reportedFeedKeys ?? collect();
    $postTeam = $post->team;
@endphp

@section('title', __('ui.feed_show_title'))
@section('meta_description', $post->excerpt(150) ?: __('ui.feed_show_text'))
@section('main_class', 'feed-main hnt-feed-single-main')

@section('content')
<header class="feed-header hnt-preview-feed-header hnt-feed-single-header">
    <div>
        <h1>{{ __('ui.feed_post') }}</h1>
        <p>{{ __('ui.feed_show_text') }}</p>
    </div>
    <div class="hnt-feed-single-actions">
        <a class="btn-create hnt-feed-single-back" href="{{ route('feed.index') }}">
            <i class="ph ph-caret-left" aria-hidden="true"></i>
            <span>{{ __('ui.back_to_feed') }}</span>
        </a>
        @if($postTeam)
            <a class="btn-create hnt-feed-single-back" href="{{ route('teams.show', $postTeam) }}">{{ __('ui.to_team') }}</a>
        @endif
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

<div class="feed-content hnt-preview-feed-content hnt-feed-single-content" data-hnt-lightbox-scope>
    @include('themes.hnt_preview.feed.partials.post-card', [
        'post' => $post,
        'reportedFeedKeys' => $reportedFeedKeys,
    ])

    <section class="hnt-feed-single-comments" aria-labelledby="feedSingleCommentsTitle">
        <div class="hnt-feed-single-comments-head">
            <div>
                <p class="hnt-preview-kicker">{{ __('ui.comments') }}</p>
                <h2 id="feedSingleCommentsTitle">{{ __('ui.preview_feed_single_comments_title') }}</h2>
            </div>
            <span>{{ trans_choice('ui.comment_count', $comments->count(), ['count' => number_format($comments->count())]) }}</span>
        </div>

        <form class="hnt-feed-single-comment-form" method="post" action="{{ route('feed.comments.store', $post) }}">
            @csrf
            <span class="avatar avatar-sm hnt-avatar-shell">
                @if($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="{{ $displayName }}">
                @endif
            </span>
            <div class="hnt-feed-single-comment-input">
                <textarea name="body" maxlength="50000" placeholder="{{ __('ui.preview_post_modal_comment_placeholder', ['name' => $displayName]) }}" required></textarea>
                <div class="hnt-feed-single-comment-actions">
                    <span>{{ __('ui.preview_feed_single_comment_help') }}</span>
                    <button class="btn-create" type="submit">{{ __('ui.preview_feed_single_comment_submit') }}</button>
                </div>
            </div>
        </form>

        <div class="post-modal-comments hnt-comment-modal-comments hnt-feed-single-comment-list" data-hnt-comment-list>
            @forelse($rootComments as $comment)
                @include('themes.hnt_preview.feed.partials.comment-item', [
                    'post' => $post,
                    'comment' => $comment,
                    'replies' => $replyGroups->get($comment->id, collect()),
                    'reportedFeedKeys' => $reportedFeedKeys,
                ])
            @empty
                <div class="hnt-comment-empty" data-hnt-comment-empty>
                    <strong>{{ __('ui.no_comments_yet') }}</strong>
                    <span>{{ __('ui.preview_comment_empty_prompt') }}</span>
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
