@extends('themes.hnt_preview.layouts.app')

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
    $canManageMoment = $moment->canBeManagedBy($viewer);
    $momentFriendship = $friendship ?? null;
    $isMomentAuthor = $viewer && (int) $viewer->id === (int) $moment->user_id;
    $isMomentAuthorFriend = $momentFriendship?->isAccepted() ?? false;
    $isMomentFriendPending = $momentFriendship?->isPending() ?? false;
    $formatCount = static function (int $value): string {
        if ($value >= 1000000) {
            return str_replace('.', ',', rtrim(rtrim(number_format($value / 1000000, 1, '.', ''), '0'), '.')).' M';
        }
        if ($value >= 1000) {
            return str_replace('.', ',', rtrim(rtrim(number_format($value / 1000, 1, '.', ''), '0'), '.')).' K';
        }
        return number_format($value, 0, ',', '.');
    };
@endphp

@section('title', $caption.' · HNT.rocks Moments')
@section('app_window_class', 'hnt-moments-window')
@section('main_class', 'hnt-moments-main')
@section('right_sidebar')
    <aside class="hnt-moments-right-placeholder" hidden></aside>
@endsection

@section('content')
<div class="hnt-moment-screen" data-socialite-moment-viewer data-hnt-moment-viewer data-next-url="{{ ! empty($nextMoment) ? route('moments.show', $nextMoment) : '' }}" data-previous-url="{{ ! empty($previousMoment) ? route('moments.show', $previousMoment) : '' }}">
    <header class="hnt-moment-mobile-topbar" aria-label="{{ __('ui.preview_nav_moments') }}">
        <a class="hnt-moment-mobile-icon" href="{{ route('feed.index') }}" data-socialite-moment-back aria-label="{{ __('ui.back') }}">
            <i class="ph ph-caret-left" aria-hidden="true"></i>
        </a>
        <strong>{{ __('ui.preview_nav_moments') }}</strong>
        @auth
            <a class="hnt-moment-mobile-icon" href="{{ route('moments.create') }}" aria-label="{{ __('ui.moment_add') }}">
                <i class="ph ph-plus" aria-hidden="true"></i>
            </a>
        @else
            <span class="hnt-moment-mobile-icon" aria-hidden="true"></span>
        @endauth
    </header>

    <section class="hnt-moment-stage-wrap" aria-label="{{ __('ui.moment_reel_aria') }}">
        <article class="hnt-moment-stage">
            <video class="hnt-moment-video" autoplay muted loop playsinline webkit-playsinline preload="auto" disablepictureinpicture controlslist="nodownload noplaybackrate noremoteplayback" @if ($videoPoster) poster="{{ $videoPoster }}" @endif data-socialite-moment-video>
                <source src="{{ $videoUrl }}" type="{{ $videoMime }}">
                {{ __('ui.moment_video_not_supported') }}
            </video>

            <div class="hnt-moment-vignette" aria-hidden="true"></div>

            @auth
                <a class="hnt-moment-create-overlay" href="{{ route('moments.create') }}" aria-label="{{ __('ui.moment_add') }}" title="{{ __('ui.moment_add') }}">
                    <i class="ph ph-plus" aria-hidden="true"></i>
                </a>
            @endauth

            <button class="hnt-moment-mute" type="button" data-socialite-moment-mute aria-label="{{ __('ui.unmute') }}" title="{{ __('ui.unmute') }}" data-muted-label="{{ __('ui.unmute') }}" data-unmuted-label="{{ __('ui.mute') }}">
                <i class="ph ph-speaker-x" aria-hidden="true" data-socialite-moment-mute-on></i>
                <i class="ph ph-speaker-high" aria-hidden="true" data-socialite-moment-mute-off hidden></i>
            </button>

            <div class="hnt-moment-copy">
                <a class="hnt-moment-author" href="{{ route('profile.public', $moment->user) }}">
                    <img src="{{ $moment->user->avatarUrl() }}" alt="">
                    <span>{{ $authorName }}</span>
                </a>
                <p class="hnt-moment-caption">{!! \App\Support\Hashtag::renderText($caption) !!}</p>
                @if ($moment->description)
                    <p class="hnt-moment-description" data-socialite-moment-description>{!! \App\Support\Hashtag::renderText(\Illuminate\Support\Str::limit($moment->description, 150)) !!}</p>
                @else
                    <p class="hnt-moment-description" data-socialite-moment-description hidden></p>
                @endif
            </div>

            <div class="hnt-moment-pause-state" data-socialite-moment-pause-state hidden aria-hidden="true">
                <i class="ph ph-pause" aria-hidden="true"></i>
            </div>

            <label class="hnt-moment-progress" aria-label="{{ __('ui.moment_progress') }}">
                <input type="range" min="0" max="100" value="0" step="0.05" data-socialite-moment-progress aria-label="{{ __('ui.moment_progress') }}">
            </label>
        </article>

        <aside class="hnt-moment-action-rail" aria-label="{{ __('ui.moment_actions') }}">
            <div class="hnt-moment-action-avatar-wrap">
                <a class="hnt-moment-action-avatar" href="{{ route('profile.public', $moment->user) }}" aria-label="{{ $authorName }}">
                    <img src="{{ $moment->user->avatarUrl() }}" alt="">
                </a>

                @auth
                    @unless($isMomentAuthor)
                        @if($isMomentAuthorFriend || $isMomentFriendPending)
                            <span class="hnt-moment-action-friend-badge is-connected" aria-label="{{ $isMomentAuthorFriend ? __('ui.preview_profile_friend_connected') : __('ui.preview_profile_friend_requested') }}" title="{{ $isMomentAuthorFriend ? __('ui.preview_profile_friend_connected') : __('ui.preview_profile_friend_requested') }}">
                                <i class="ph ph-check" aria-hidden="true"></i>
                            </span>
                        @else
                            <form class="hnt-moment-action-friend-form" method="post" action="{{ route('friends.store', $moment->user) }}" data-socialite-moment-friend-form data-connected-label="{{ __('ui.preview_profile_friend_connected') }}" data-requested-label="{{ __('ui.preview_profile_friend_requested') }}">
                                @csrf
                                <button class="hnt-moment-action-friend-badge" type="submit" aria-label="{{ __('ui.preview_profile_friend_add') }}" title="{{ __('ui.preview_profile_friend_add') }}">
                                    <i class="ph ph-plus" aria-hidden="true"></i>
                                </button>
                            </form>
                        @endif
                    @endunless
                @endauth
            </div>

            <form class="hnt-moment-action-form hnt-moment-like-action" method="post" action="{{ route('moments.reactions.toggle', $moment) }}">
                @csrf
                <button class="hnt-moment-action {{ $isLiked ? 'is-active' : '' }}" type="submit" aria-label="{{ $isLiked ? __('ui.moment_unlike') : __('ui.moment_like') }}">
                    <i class="ph ph-heart" aria-hidden="true"></i>
                </button>
                <span>{{ $formatCount((int) $moment->likes_count) }}</span>
            </form>

            <button class="hnt-moment-action-wrap hnt-moment-comments-action" type="button" data-socialite-moment-comments-open aria-label="{{ __('ui.moment_comments') }}">
                <span class="hnt-moment-action"><i class="ph ph-chat-circle-text" aria-hidden="true"></i></span>
                <span>{{ $formatCount((int) $moment->comments_count) }}</span>
            </button>

            <form class="hnt-moment-action-form hnt-moment-bookmark-action" method="post" action="{{ route('moments.bookmarks.toggle', $moment) }}">
                @csrf
                <button class="hnt-moment-action {{ $isBookmarked ? 'is-active' : '' }}" type="submit" aria-label="{{ $isBookmarked ? __('ui.moment_unsave') : __('ui.moment_save') }}">
                    <i class="ph ph-bookmark-simple" aria-hidden="true"></i>
                </button>
                <span>{{ $formatCount((int) $moment->bookmarks_count) }}</span>
            </form>

            <button class="hnt-moment-action-wrap hnt-moment-share-action" type="button" data-socialite-moment-share aria-label="{{ __('ui.share') }}">
                <span class="hnt-moment-action"><i class="ph ph-paper-plane-tilt" aria-hidden="true"></i></span>
                <span>{{ __('ui.share') }}</span>
            </button>

            <button class="hnt-moment-action-wrap hnt-moment-more-trigger" type="button" data-socialite-moment-more-open aria-label="{{ __('ui.more_options') }}">
                <span class="hnt-moment-action"><i class="ph ph-dots-three-vertical" aria-hidden="true"></i></span>
                <span>{{ __('ui.more') }}</span>
            </button>

            <button class="hnt-moment-action-wrap hnt-moment-mobile-mute-action" type="button" data-socialite-moment-mute aria-label="{{ __('ui.unmute') }}" title="{{ __('ui.unmute') }}" data-muted-label="{{ __('ui.unmute') }}" data-unmuted-label="{{ __('ui.mute') }}">
                <span class="hnt-moment-action">
                    <i class="ph ph-speaker-x" aria-hidden="true" data-socialite-moment-mute-on></i>
                    <i class="ph ph-speaker-high" aria-hidden="true" data-socialite-moment-mute-off hidden></i>
                </span>
            </button>
        </aside>
    </section>

    <nav class="hnt-moment-desktop-nav" aria-label="{{ __('ui.moment_reel_navigation') }}">
        @if (! empty($previousMoment))
            <a href="{{ route('moments.show', $previousMoment) }}" aria-label="{{ __('ui.moment_previous') }}"><i class="ph ph-caret-up" aria-hidden="true"></i></a>
        @else
            <span aria-hidden="true"><i class="ph ph-caret-up" aria-hidden="true"></i></span>
        @endif
        @if (! empty($nextMoment))
            <a href="{{ route('moments.show', $nextMoment) }}" aria-label="{{ __('ui.moment_next') }}"><i class="ph ph-caret-down" aria-hidden="true"></i></a>
        @else
            <span aria-hidden="true"><i class="ph ph-caret-down" aria-hidden="true"></i></span>
        @endif
    </nav>
</div>

<div class="hnt-moment-comments-backdrop" data-socialite-moment-comments-backdrop data-socialite-moment-comments-close></div>
<aside class="hnt-moment-comments" data-socialite-moment-comments aria-hidden="true">
    <header class="hnt-moment-panel-head">
        <div>
            <span>{{ __('ui.preview_nav_moments') }}</span>
            <h2>{{ __('ui.moment_comments') }}</h2>
        </div>
        <button type="button" data-socialite-moment-comments-close aria-label="{{ __('ui.close') }}"><i class="ph ph-x" aria-hidden="true"></i></button>
    </header>

    <div class="hnt-moment-comment-list">
        @forelse ($moment->comments as $comment)
            @php
                $commentLiked = $viewer && $comment->relationLoaded('reactions') && $comment->reactions->isNotEmpty();
                $commentCanEdit = $comment->canBeEditedBy($viewer);
                $commentCanDelete = $comment->canBeDeletedBy($viewer);
            @endphp
            <article class="hnt-moment-comment" id="moment-comment-{{ $comment->id }}">
                <a href="{{ route('profile.public', $comment->user) }}" class="hnt-moment-comment-avatar" aria-label="{{ $comment->user->username }}">
                    <img src="{{ $comment->user->avatarUrl() }}" alt="">
                </a>
                <div class="hnt-moment-comment-main">
                    <div class="hnt-moment-comment-body">
                        <header>
                            <a href="{{ route('profile.public', $comment->user) }}">{{ $comment->user->username }}</a>
                            <small>{{ $comment->created_at->diffForHumans() }}</small>
                            @if($comment->updated_at && $comment->updated_at->gt($comment->created_at->copy()->addSeconds(5)))
                                <small>{{ __('ui.moment_comment_edited') }}</small>
                            @endif
                        </header>
                        <p>{{ $comment->body }}</p>
                    </div>

                    <div class="hnt-moment-comment-actions">
                        @auth
                            <form method="post" action="{{ route('moments.comments.reactions.toggle', $comment) }}">
                                @csrf
                                <button class="hnt-moment-comment-action {{ $commentLiked ? 'is-active' : '' }}" type="submit">
                                    <i class="ph ph-heart" aria-hidden="true"></i>
                                    <span>{{ $commentLiked ? __('ui.moment_comment_unlike') : __('ui.moment_comment_like') }}</span>
                                    @if((int) $comment->likes_count > 0)
                                        <strong>{{ (int) $comment->likes_count }}</strong>
                                    @endif
                                </button>
                            </form>

                            <details class="hnt-moment-comment-inline-form">
                                <summary class="hnt-moment-comment-action">
                                    <i class="ph ph-chat-circle-text" aria-hidden="true"></i>
                                    <span>{{ __('ui.moment_comment_reply') }}</span>
                                </summary>
                                <form method="post" action="{{ route('moments.comments.store', $moment) }}">
                                    @csrf
                                    <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                    <textarea name="body" rows="2" maxlength="1000" placeholder="{{ __('ui.moment_comment_reply_placeholder', ['name' => $comment->user->username]) }}" required></textarea>
                                    <button type="submit">{{ __('ui.moment_comment_reply_submit') }}</button>
                                </form>
                            </details>
                        @endauth

                        @if($commentCanEdit)
                            <details class="hnt-moment-comment-inline-form">
                                <summary class="hnt-moment-comment-action">
                                    <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                                    <span>{{ __('ui.moment_comment_edit') }}</span>
                                </summary>
                                <form method="post" action="{{ route('moments.comments.update', $comment) }}">
                                    @csrf
                                    @method('PATCH')
                                    <textarea name="body" rows="2" maxlength="1000" required>{{ $comment->body }}</textarea>
                                    <button type="submit">{{ __('ui.moment_comment_update') }}</button>
                                </form>
                            </details>
                        @endif

                        @if ((int) $comment->user_id !== (int) auth()->id())
                            <button class="hnt-moment-comment-action hnt-moment-comment-report" type="button" data-hh-report-open data-hh-report-type="moment_comment" data-hh-report-id="{{ $comment->id }}" data-hh-report-label="{{ __('ui.moment_comment_report_label', ['name' => $comment->user->name]) }}">
                                <i class="ph ph-warning-octagon" aria-hidden="true"></i>
                                <span>{{ __('ui.report_short') }}</span>
                            </button>
                        @endif

                        @if($commentCanDelete)
                            <form method="post" action="{{ route('moments.comments.destroy', $comment) }}" onsubmit="return confirm('{{ __('ui.moment_comment_delete_confirm') }}');">
                                @csrf
                                @method('DELETE')
                                <button class="hnt-moment-comment-action is-danger" type="submit">
                                    <i class="ph ph-trash" aria-hidden="true"></i>
                                    <span>{{ __('ui.moment_comment_delete') }}</span>
                                </button>
                            </form>
                        @endif
                    </div>

                    @if($comment->replies->isNotEmpty())
                        <div class="hnt-moment-comment-replies" aria-label="{{ __('ui.moment_comment_replies') }}">
                            @foreach($comment->replies as $reply)
                                @php
                                    $replyLiked = $viewer && $reply->relationLoaded('reactions') && $reply->reactions->isNotEmpty();
                                    $replyCanEdit = $reply->canBeEditedBy($viewer);
                                    $replyCanDelete = $reply->canBeDeletedBy($viewer);
                                @endphp
                                <article class="hnt-moment-comment hnt-moment-comment-reply" id="moment-comment-{{ $reply->id }}">
                                    <a href="{{ route('profile.public', $reply->user) }}" class="hnt-moment-comment-avatar" aria-label="{{ $reply->user->username }}">
                                        <img src="{{ $reply->user->avatarUrl() }}" alt="">
                                    </a>
                                    <div class="hnt-moment-comment-main">
                                        <div class="hnt-moment-comment-body">
                                            <header>
                                                <a href="{{ route('profile.public', $reply->user) }}">{{ $reply->user->username }}</a>
                                                <small>{{ $reply->created_at->diffForHumans() }}</small>
                                                @if($reply->updated_at && $reply->updated_at->gt($reply->created_at->copy()->addSeconds(5)))
                                                    <small>{{ __('ui.moment_comment_edited') }}</small>
                                                @endif
                                            </header>
                                            <p>{{ $reply->body }}</p>
                                        </div>

                                        <div class="hnt-moment-comment-actions">
                                            @auth
                                                <form method="post" action="{{ route('moments.comments.reactions.toggle', $reply) }}">
                                                    @csrf
                                                    <button class="hnt-moment-comment-action {{ $replyLiked ? 'is-active' : '' }}" type="submit">
                                                        <i class="ph ph-heart" aria-hidden="true"></i>
                                                        <span>{{ $replyLiked ? __('ui.moment_comment_unlike') : __('ui.moment_comment_like') }}</span>
                                                        @if((int) $reply->likes_count > 0)
                                                            <strong>{{ (int) $reply->likes_count }}</strong>
                                                        @endif
                                                    </button>
                                                </form>
                                            @endauth

                                            @if($replyCanEdit)
                                                <details class="hnt-moment-comment-inline-form">
                                                    <summary class="hnt-moment-comment-action">
                                                        <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                                                        <span>{{ __('ui.moment_comment_edit') }}</span>
                                                    </summary>
                                                    <form method="post" action="{{ route('moments.comments.update', $reply) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <textarea name="body" rows="2" maxlength="1000" required>{{ $reply->body }}</textarea>
                                                        <button type="submit">{{ __('ui.moment_comment_update') }}</button>
                                                    </form>
                                                </details>
                                            @endif

                                            @if ((int) $reply->user_id !== (int) auth()->id())
                                                <button class="hnt-moment-comment-action hnt-moment-comment-report" type="button" data-hh-report-open data-hh-report-type="moment_comment" data-hh-report-id="{{ $reply->id }}" data-hh-report-label="{{ __('ui.moment_comment_report_label', ['name' => $reply->user->name]) }}">
                                                    <i class="ph ph-warning-octagon" aria-hidden="true"></i>
                                                    <span>{{ __('ui.report_short') }}</span>
                                                </button>
                                            @endif

                                            @if($replyCanDelete)
                                                <form method="post" action="{{ route('moments.comments.destroy', $reply) }}" onsubmit="return confirm('{{ __('ui.moment_comment_delete_confirm') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="hnt-moment-comment-action is-danger" type="submit">
                                                        <i class="ph ph-trash" aria-hidden="true"></i>
                                                        <span>{{ __('ui.moment_comment_delete') }}</span>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="hnt-moment-empty-note">{{ __('ui.moment_no_comments') }}</div>
        @endforelse
    </div>

    @auth
        <form class="hnt-moment-comment-form" method="post" action="{{ route('moments.comments.store', $moment) }}">
            @csrf
            <textarea name="body" rows="1" placeholder="{{ __('ui.moment_comment_placeholder') }}" required>{{ old('body') }}</textarea>
            <button type="submit">{{ __('ui.moment_comment_submit') }}</button>
        </form>
    @else
        <div class="hnt-moment-login-note">{{ __('ui.login_to_comment') }}</div>
    @endauth
</aside>

<div class="hnt-moment-more-backdrop" data-socialite-moment-more-backdrop data-socialite-moment-more-close></div>
<aside class="hnt-moment-more-sheet" data-socialite-moment-more aria-hidden="true">
    <header class="hnt-moment-panel-head">
        <div>
            <span>{{ __('ui.preview_nav_moments') }}</span>
            <h2>{{ __('ui.moment_options') }}</h2>
        </div>
        <button type="button" data-socialite-moment-more-close aria-label="{{ __('ui.close') }}"><i class="ph ph-x" aria-hidden="true"></i></button>
    </header>

    <div class="hnt-moment-more-content">
        <a class="hnt-moment-more-action" href="{{ $videoUrl }}" download>
            <i class="ph ph-download-simple" aria-hidden="true"></i>
            {{ __('ui.download') }}
        </a>

        @if ($canManageMoment)
            <button class="hnt-moment-more-action" type="button" data-socialite-moment-edit-toggle>
                <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                {{ __('ui.edit_description') }}
            </button>

            <form class="hnt-moment-edit-form" method="post" action="{{ route('moments.update', $moment) }}" data-socialite-moment-edit-form>
                @csrf
                @method('PATCH')
                <label for="moment-description-{{ $moment->id }}">{{ __('ui.description') }}</label>
                <textarea id="moment-description-{{ $moment->id }}" name="description" maxlength="2000" placeholder="{{ __('ui.description_placeholder') }}">{{ old('description', $moment->description) }}</textarea>
                <button type="submit" data-saving-label="{{ __('ui.saving') }}">{{ __('ui.save') }}</button>
            </form>

            <form method="post" action="{{ route('moments.destroy', $moment) }}" onsubmit="return confirm('{{ __('ui.moment_delete_confirm') }}');">
                @csrf
                @method('DELETE')
                <button class="hnt-moment-more-action is-danger" type="submit">
                    <i class="ph ph-trash" aria-hidden="true"></i>
                    {{ __('ui.delete') }}
                </button>
            </form>
        @endif
    </div>
</aside>
@endsection

@push('scripts')
    <script src="{{ asset('assets/socialite/js/hnt-socialite-moments-viewer.js') }}?v=730" defer></script>
    <script>
        (() => {
            const ready = (fn) => document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', fn, { once: true }) : fn();
            ready(() => {
                const viewer = document.querySelector('[data-hnt-moment-viewer]');
                if (!viewer) return;

                let startY = 0;
                let startX = 0;
                let touchActive = false;
                let locked = false;
                let wheelLocked = false;
                let resetTimer = null;

                const clearGesture = () => {
                    viewer.classList.remove('is-dragging', 'is-dragging-next', 'is-dragging-previous');
                    viewer.style.removeProperty('--hnt-swipe-offset');
                };

                const directionForUrl = (url) => {
                    if (!url) return '';
                    if (url === viewer.dataset.nextUrl) return 'next';
                    if (url === viewer.dataset.previousUrl) return 'previous';
                    return 'next';
                };

                const go = (url, direction = '') => {
                    if (!url || locked) return;
                    locked = true;
                    clearGesture();
                    const resolvedDirection = direction || directionForUrl(url);
                    viewer.classList.add(resolvedDirection === 'previous' ? 'is-leaving-previous' : 'is-leaving-next');
                    window.setTimeout(() => window.location.assign(url), 135);
                };

                viewer.addEventListener('touchstart', (event) => {
                    const touch = event.changedTouches[0];
                    startY = touch.clientY;
                    startX = touch.clientX;
                    touchActive = true;
                    window.clearTimeout(resetTimer);
                    clearGesture();
                }, { passive: true });

                viewer.addEventListener('touchmove', (event) => {
                    if (!touchActive || locked) return;
                    const touch = event.changedTouches[0];
                    const deltaY = touch.clientY - startY;
                    const deltaX = touch.clientX - startX;
                    if (Math.abs(deltaY) < 10 || Math.abs(deltaY) < Math.abs(deltaX) * 1.15) return;
                    event.preventDefault();
                    const clamped = Math.max(-96, Math.min(96, deltaY));
                    viewer.style.setProperty('--hnt-swipe-offset', `${clamped}px`);
                    viewer.classList.add('is-dragging');
                    viewer.classList.toggle('is-dragging-next', deltaY < 0);
                    viewer.classList.toggle('is-dragging-previous', deltaY > 0);
                }, { passive: false });

                viewer.addEventListener('touchend', (event) => {
                    if (!touchActive || locked) return;
                    touchActive = false;
                    const touch = event.changedTouches[0];
                    const deltaY = touch.clientY - startY;
                    const deltaX = touch.clientX - startX;
                    if (Math.abs(deltaY) < 70 || Math.abs(deltaY) < Math.abs(deltaX) * 1.4) {
                        clearGesture();
                        return;
                    }
                    go(deltaY < 0 ? viewer.dataset.nextUrl : viewer.dataset.previousUrl, deltaY < 0 ? 'next' : 'previous');
                }, { passive: true });

                viewer.addEventListener('touchcancel', clearGesture, { passive: true });

                viewer.addEventListener('wheel', (event) => {
                    if (locked || wheelLocked || Math.abs(event.deltaY) < 42 || Math.abs(event.deltaY) < Math.abs(event.deltaX) * 1.25) return;
                    const target = event.target;
                    if (target?.closest?.('[data-socialite-moment-comments], [data-socialite-moment-more], textarea, input, select')) return;
                    event.preventDefault();
                    wheelLocked = true;
                    const direction = event.deltaY > 0 ? 'next' : 'previous';
                    go(direction === 'next' ? viewer.dataset.nextUrl : viewer.dataset.previousUrl, direction);
                    window.setTimeout(() => { wheelLocked = false; }, 500);
                }, { passive: false });

                document.querySelectorAll('.hnt-moment-desktop-nav a').forEach((link) => {
                    link.addEventListener('click', (event) => {
                        const url = link.getAttribute('href');
                        if (!url) return;
                        event.preventDefault();
                        go(url, directionForUrl(url));
                    });
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'ArrowDown') go(viewer.dataset.nextUrl, 'next');
                    if (event.key === 'ArrowUp') go(viewer.dataset.previousUrl, 'previous');
                });
            });
        })();    </script>
@endpush
