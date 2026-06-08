@php
    $commentAuthor = $comment->user;
    $commentName = $commentAuthor?->name ?: 'Hunter';
    $commentAvatar = $commentAuthor?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $commentProfileUrl = $commentAuthor?->username ? ((int) $commentAuthor->id === (int) auth()->id() ? route('profile.show') : route('profile.public', $commentAuthor)) : '#';
    $commentCosmetics = $commentAuthor ? \App\Support\CrownCosmetics::forUser($commentAuthor) : \App\Support\CrownCosmetics::emptyState();
    $commentAvatarFrameClass = trim((string) ($commentCosmetics['avatar_frame_class'] ?? ''));
    $commentUsernameEffectClass = trim((string) ($commentCosmetics['username_effect_class'] ?? ''));
    $commentProfileTitleLabel = trim((string) ($commentCosmetics['profile_title_label'] ?? ''));
    $commentBodyHtml = \App\Support\FeedTextRenderer::render((string) $comment->body);
    $commentCreatedLabel = $comment->created_at?->diffForHumans() ?: 'now';
    $commentReactionCount = $comment->relationLoaded('reactions') ? $comment->reactions->count() : 0;
    $commentViewerReaction = $comment->relationLoaded('viewerReaction') ? $comment->viewerReaction?->type : null;
    $isReply = (bool) ($isReply ?? $comment->parent_id);
    $rootId = (int) ($rootId ?? ($comment->parent_id ?: $comment->id));
    $viewer = auth()->user();
    $viewerId = (int) ($viewer?->id ?? 0);
    $viewerIsAdmin = $viewer && method_exists($viewer, 'isAdmin') && $viewer->isAdmin();
    $commentPost = isset($post) && $post instanceof \App\Models\FeedPost
        ? $post
        : ($comment->relationLoaded('post') ? $comment->post : null);
    $canEditComment = $viewerId > 0 && (int) $comment->user_id === $viewerId;
    $canDeleteComment = $canEditComment || $viewerIsAdmin || ($commentPost && (int) $commentPost->user_id === $viewerId);
@endphp

<div
    class="flex gap-3 relative socialite-live-comment {{ $isReply ? 'socialite-live-comment-reply' : '' }}"
    data-socialite-comment-id="{{ $comment->id }}"
    data-socialite-comment-parent-id="{{ $comment->parent_id ?: '' }}"
    data-socialite-root-id="{{ $rootId }}"
    data-socialite-comment-update-url="{{ route('feed.comments.update', $comment) }}"
    data-socialite-comment-delete-url="{{ route('feed.comments.destroy', $comment) }}"
>
    <a href="{{ $commentProfileUrl }}" class="shrink-0 hh-crowns-avatar-inline hh-crowns-avatar-comment {{ $isReply ? 'hh-crowns-avatar-reply w-6 h-6 mt-1' : 'w-8 h-8' }} {{ $commentAvatarFrameClass }}" aria-label="{{ $commentName }}">
        <img src="{{ $commentAvatar }}" alt="{{ $commentName }}" class="{{ $isReply ? 'w-6 h-6' : 'w-8 h-8' }} rounded-full object-cover">
    </a>

    <div class="flex-1 min-w-0">
        <div class="{{ $isReply ? 'bg-slate-50 dark:bg-slate-800/70' : 'bg-secondery' }} rounded-xl px-4 py-2" data-socialite-comment-bubble>
            <div class="flex items-start gap-2">
                <div class="min-w-0 flex-1">
                    <a href="{{ $commentProfileUrl }}" class="font-semibold text-black dark:text-white {{ $commentUsernameEffectClass }}">{{ $commentName }}</a>
                    @if($commentProfileTitleLabel !== '')
                        <div class="hh-crowns-author-title hh-crowns-comment-author-title">{{ $commentProfileTitleLabel }}</div>
                    @endif
                    <div class="text-sm mt-0.5 leading-6 text-slate-700 dark:text-white/90 break-words" data-socialite-comment-body data-raw-body="{{ e((string) $comment->body) }}">{!! $commentBodyHtml !!}</div>
                </div>

                @if($canEditComment || $canDeleteComment)
                    <div class="relative shrink-0 -mr-1">
                        <button type="button" class="grid h-7 w-7 place-items-center rounded-full text-slate-500 hover:bg-white/70 hover:text-slate-900 dark:text-white/60 dark:hover:bg-white/10 dark:hover:text-white" aria-label="Kommentaroptionen">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle>
                            </svg>
                        </button>
                        <div class="w-[190px]" uk-dropdown="pos: bottom-right; animation: uk-animation-scale-up uk-transform-origin-top-right; animate-out: true; mode: click">
                            <nav>
                                @if($canEditComment)
                                    <button type="button" class="w-full flex items-center gap-3 px-3 py-2 text-left hover:bg-secondery rounded-lg dark:hover:bg-white/10" data-socialite-comment-edit>
                                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                                        </svg>
                                        Bearbeiten
                                    </button>
                                @endif
                                @if($canDeleteComment)
                                    <button type="button" class="w-full flex items-center gap-3 px-3 py-2 text-left text-red-600 hover:bg-red-50 rounded-lg dark:hover:bg-red-500/10" data-socialite-comment-delete data-delete-url="{{ route('feed.comments.destroy', $comment) }}">
                                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line>
                                        </svg>
                                        Löschen
                                    </button>
                                @endif
                            </nav>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-3 mt-1 text-xs text-gray-500 dark:text-white/60">
            <span>{{ $commentCreatedLabel }}</span>

            <form method="post" action="{{ route('feed.comments.reactions.toggle', $comment) }}" class="m-0 inline-flex" data-socialite-comment-reaction-form>
                @csrf
                <input type="hidden" name="type" value="like">
                <button type="submit" class="hover:text-blue-500 {{ $commentViewerReaction ? 'text-blue-500 font-semibold' : '' }}" data-socialite-comment-like-button>
                    {{ $commentViewerReaction ? __('ui.liked') : __('ui.like') }}@if($commentReactionCount > 0) <span data-socialite-comment-like-count>{{ $commentReactionCount }}</span>@else<span data-socialite-comment-like-count class="hidden">0</span>@endif
                </button>
            </form>

            @unless($isReply)
                <button type="button" class="hover:text-blue-500" data-socialite-comment-reply data-comment-id="{{ $comment->id }}" data-comment-author="{{ e($commentName) }}">{{ __('ui.reply') }}</button>
            @endunless
        </div>
    </div>
</div>
