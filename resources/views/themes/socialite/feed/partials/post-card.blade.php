@php
    $author = $post->user;
    $authorName = $author?->name ?: 'HNT Hunter';
    $authorAvatar = $author?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $authorUrl = $author?->username ? ((int) $author->id === (int) auth()->id() ? route('profile.show') : route('profile.public', $author)) : '#';
    $authorCosmetics = $author ? \App\Support\CrownCosmetics::forUser($author) : \App\Support\CrownCosmetics::emptyState();
    $authorAvatarFrameClass = trim((string) ($authorCosmetics['avatar_frame_class'] ?? ''));
    $authorUsernameEffectClass = trim((string) ($authorCosmetics['username_effect_class'] ?? ''));
    $authorProfileTitleLabel = trim((string) ($authorCosmetics['profile_title_label'] ?? ''));
    $isSocialiteDesignRoute = request()->routeIs('design.socialite.theme-feed-data') || request()->routeIs('design.socialite.theme-feed-data.show') || request()->routeIs('design.socialite.profile*');
    $postUrl = $isSocialiteDesignRoute
        ? route('design.socialite.theme-feed-data.show', $post)
        : route('feed.show', $post);
    $body = trim((string) $post->body);
    $bodyHtml = \App\Support\FeedTextRenderer::render($body);
    $internalLinkPreviews = \App\Support\FeedTextRenderer::internalLinkPreviews($body, 1);
    $mediaItems = $post->relationLoaded('media') ? $post->media->values() : collect();
    $firstMedia = $mediaItems->first();
    $extraMediaCount = max($mediaItems->count() - 1, 0);
    $gifPayload = method_exists($post, 'gifPayload') ? $post->gifPayload() : null;
    $allComments = $post->relationLoaded('comments') ? $post->comments : collect();
    $previewThreadLimit = 2;
    $viewerReactionType = $post->relationLoaded('viewerReaction') ? $post->viewerReaction?->type : null;
    $reactionTypes = [
        'like' => ['emoji' => '👍', 'label' => __('ui.reaction_like')],
        'love' => ['emoji' => '❤️', 'label' => __('ui.reaction_love')],
        'funny' => ['emoji' => '😂', 'label' => __('ui.reaction_funny')],
        'wow' => ['emoji' => '😯', 'label' => __('ui.reaction_wow')],
        'sad' => ['emoji' => '😢', 'label' => __('ui.reaction_sad')],
        'angry' => ['emoji' => '😡', 'label' => __('ui.reaction_angry')],
    ];
    $reactionCount = (int) ($post->reactions_count ?? ($post->relationLoaded('reactions') ? $post->reactions->count() : 0));
    $commentCount = (int) ($post->comments_count ?? ($post->relationLoaded('comments') ? $post->comments->count() : 0));
    $bookmarkCount = (int) ($post->bookmarks_count ?? ($post->relationLoaded('bookmarks') ? $post->bookmarks->count() : 0));
    $shareCount = (int) ($post->shares_count ?? 0);
    $viewerBookmarked = $post->relationLoaded('viewerBookmark') ? (bool) $post->viewerBookmark : false;
    $viewer = auth()->user();
    $viewerId = (int) ($viewer?->id ?? 0);
    $viewerIsAdmin = $viewer && method_exists($viewer, 'isAdmin') && $viewer->isAdmin();
    $canEditPost = $viewerId > 0 && (int) $post->user_id === $viewerId;
    $canDeletePost = $canEditPost || $viewerIsAdmin;
    $canReportPost = auth()->check() && (int) $post->user_id !== (int) auth()->id();
    $feelingMeta = method_exists($post, 'feelingMeta') ? $post->feelingMeta() : null;
    $visibilityLabel = method_exists($post, 'visibilityLabel') ? $post->visibilityLabel() : ucfirst((string) ($post->visibility ?: 'public'));
    $poll = $post->relationLoaded('poll') ? $post->poll : null;
    $pollOptions = $poll && $poll->relationLoaded('options') ? $poll->options : collect();
    $pollVotes = $poll && $poll->relationLoaded('votes') ? $poll->votes : collect();
    $pollTotalVotes = $pollVotes->count();
    $viewerPollVote = auth()->check() ? $pollVotes->firstWhere('user_id', auth()->id()) : null;
    $viewerPollOptionId = $viewerPollVote?->feed_post_poll_option_id;
@endphp

<!-- post image / HNT live preview -->
<article
    class="bg-white rounded-xl shadow-sm text-sm font-medium border1 dark:bg-dark2"
    data-hnt-socialite-post="{{ $post->id }}"
    data-socialite-post-update-url="{{ route('feed.update', $post) }}"
    data-socialite-post-delete-url="{{ route('feed.destroy', $post) }}"
    data-socialite-post-visibility="{{ $post->visibility ?: 'public' }}"
>

    <!-- post heading -->
    <div class="flex gap-3 sm:p-4 p-2.5 text-sm font-medium">
        <a href="{{ $authorUrl }}" class="w-9 h-9 hh-crowns-avatar-inline hh-crowns-avatar-feed {{ $authorAvatarFrameClass }}" aria-label="{{ $authorName }}">
            <img src="{{ $authorAvatar }}" alt="{{ $authorName }}" class="w-9 h-9 rounded-full object-cover">
        </a>

        <div class="flex-1 min-w-0">
            <a href="{{ $authorUrl }}">
                <h4 class="text-black dark:text-white truncate {{ $authorUsernameEffectClass }}">{{ $authorName }}</h4>
            </a>
            @if($authorProfileTitleLabel !== '')
                <div class="hh-crowns-author-title hh-crowns-feed-author-title">{{ $authorProfileTitleLabel }}</div>
            @endif
            <div class="text-xs text-gray-500 dark:text-white/80 truncate">
                {{ $post->created_at?->diffForHumans() }}
                @if($post->team)
                    · {{ $post->team->name }}
                @else
                    · {{ $visibilityLabel }}
                @endif
                @if($feelingMeta)
                    @php
                        $feelingEmoji = $feelingMeta['emoji'] ?? '';
                        $feelingLabel = $feelingMeta['label'] ?? ($feelingMeta['label_en'] ?? ($feelingMeta['key'] ?? ''));
                    @endphp
                    @if($feelingEmoji || $feelingLabel)
                        · {{ trim($feelingEmoji . ' ' . $feelingLabel) }}
                    @endif
                @endif
            </div>
        </div>

        <div class="-mr-1">
            <button type="button" class="button-icon w-8 h-8" aria-label="{{ __('ui.post_options') }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle>
                </svg>
            </button>
            <div class="w-[245px]" uk-dropdown="pos: bottom-right; animation: uk-animation-scale-up uk-transform-origin-top-right; animate-out: true; mode: click">
                <nav>
                    <a href="{{ $postUrl }}"> <ion-icon class="text-xl shrink-0" name="open-outline"></ion-icon> {{ __('ui.open_post') }} </a>
                    <a href="{{ $authorUrl }}"> <ion-icon class="text-xl shrink-0" name="person-circle-outline"></ion-icon> {{ __('ui.open_profile') }} </a>
                    <form method="post" action="{{ route('feed.bookmarks.toggle', $post) }}" data-socialite-bookmark-form data-post-id="{{ $post->id }}" class="m-0">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 text-left hover:bg-secondery rounded-lg dark:hover:bg-white/10" data-socialite-bookmark-label>
                            <ion-icon class="text-xl shrink-0" name="{{ $viewerBookmarked ? 'bookmark' : 'bookmark-outline' }}"></ion-icon>
                            {{ $viewerBookmarked ? __('ui.remove_bookmark') : __('ui.add_to_favorites') }}
                        </button>
                    </form>
                    <button type="button" class="w-full flex items-center gap-3 px-3 py-2 text-left hover:bg-secondery rounded-lg dark:hover:bg-white/10" data-socialite-share-button data-share-url="{{ $postUrl }}" data-share-title="{{ e($authorName . ' on HNT.rocks') }}">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path><polyline points="16 6 12 2 8 6"></polyline><line x1="12" y1="2" x2="12" y2="15"></line>
                        </svg>
                        {{ __('ui.copy_share_link') }}
                    </button>
                    @if($canEditPost)
                        <button type="button" class="w-full flex items-center gap-3 px-3 py-2 text-left hover:bg-secondery rounded-lg dark:hover:bg-white/10" data-socialite-post-edit>
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                            </svg>
                            Beitrag bearbeiten
                        </button>
                    @endif
                    @if($canDeletePost)
                        <button type="button" class="w-full flex items-center gap-3 px-3 py-2 text-left text-red-600 hover:bg-red-50 rounded-lg dark:hover:bg-red-500/10" data-socialite-post-delete data-admin-delete="{{ $viewerIsAdmin && ! $canEditPost ? '1' : '0' }}">
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line>
                            </svg>
                            Beitrag löschen
                        </button>
                    @endif
                    @if($canReportPost)
                        <button type="button" class="w-full flex items-center gap-3 px-3 py-2 text-left hover:bg-secondery rounded-lg dark:hover:bg-white/10" data-socialite-report-open data-report-type="feed_post" data-report-id="{{ $post->id }}" data-report-label="{{ __('ui.feed_report_post_by', ['name' => e($authorName)]) }}">
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V4s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg> {{ __('ui.report_this_post') }}
                        </button>
                    @endif
                </nav>
            </div>
        </div>
    </div>

    <div class="sm:px-4 px-2.5 pb-3 {{ $body === '' ? 'hidden' : '' }}" data-socialite-post-body-wrap>
        <p class="font-normal leading-6 text-slate-700 dark:text-white/90 break-words" data-socialite-post-body data-raw-body="{{ e((string) $post->body) }}">{!! $bodyHtml !!}</p>
        @if(count($internalLinkPreviews) > 0)
            <div class="mt-3 space-y-2" data-hnt-internal-link-previews>
                @foreach($internalLinkPreviews as $preview)
                    <a href="{{ $preview['url'] }}" class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900/50 dark:hover:bg-slate-800">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-500 dark:bg-blue-500/10">
                            <ion-icon name="{{ $preview['icon'] }}" class="text-xl"></ion-icon>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-xs font-bold uppercase tracking-wide text-blue-500">{{ $preview['label'] }}</span>
                            <span class="block truncate text-sm font-bold text-slate-900 dark:text-white">{{ $preview['title'] }}</span>
                            <span class="mt-0.5 block line-clamp-2 text-xs font-normal leading-5 text-slate-500 dark:text-white/60">{{ $preview['description'] }}</span>
                            <span class="mt-1 block truncate text-xs font-normal text-slate-400 dark:text-white/40">{{ $preview['display_url'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if($poll && $pollOptions->count())
        <div class="sm:px-4 px-2.5 pb-3">
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800" data-socialite-poll-card="{{ $poll->id }}">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ __('ui.poll') }}</div>
                        <h5 class="mt-1 text-sm font-semibold text-black dark:text-white">{{ $poll->question ?: __('ui.poll_default_question') }}</h5>
                    </div>
                    <ion-icon name="stats-chart-outline" class="text-xl text-blue-500"></ion-icon>
                </div>

                <div class="space-y-2" data-socialite-poll-options>
                    @foreach($pollOptions as $pollOption)
                        @php
                            $pollOptionVotes = $pollOption->relationLoaded('votes') ? $pollOption->votes->count() : 0;
                            $pollPercent = $pollTotalVotes > 0 ? (int) round(($pollOptionVotes / max(1, $pollTotalVotes)) * 100) : 0;
                            $pollSelected = $viewerPollOptionId && (int) $viewerPollOptionId === (int) $pollOption->id;
                        @endphp
                        <form method="post" action="{{ route('feed.poll.vote', $post) }}" data-socialite-poll-form data-post-id="{{ $post->id }}" class="m-0">
                            @csrf
                            <input type="hidden" name="poll_option_id" value="{{ $pollOption->id }}">
                            <button
                                type="submit"
                                class="relative w-full overflow-hidden rounded-xl bg-white px-3 py-2 text-left ring-1 ring-slate-100 transition hover:ring-blue-200 dark:bg-dark3 dark:ring-slate-700 {{ $pollSelected ? 'ring-blue-300' : '' }}"
                                data-socialite-poll-option
                                data-selected="{{ $pollSelected ? '1' : '0' }}"
                                data-votes="{{ $pollOptionVotes }}"
                            >
                                <span class="absolute inset-y-0 left-0 bg-blue-100/80 dark:bg-blue-900/40" style="width: {{ $pollPercent }}%" data-socialite-poll-bar></span>
                                <span class="relative flex items-center justify-between gap-3">
                                    <span class="min-w-0 truncate text-sm font-semibold text-slate-700 dark:text-white">{{ $pollOption->body }}</span>
                                    <span class="flex shrink-0 items-center gap-1 text-xs font-bold text-slate-500">
                                        <ion-icon name="checkmark-circle" class="{{ $pollSelected ? '' : 'hidden' }} text-base text-blue-500" data-socialite-poll-check></ion-icon>
                                        <span data-socialite-poll-percent>{{ $pollPercent }}%</span>
                                    </span>
                                </span>
                            </button>
                        </form>
                    @endforeach
                </div>

                <p class="mt-2 text-xs text-slate-500" data-socialite-poll-total data-total-votes="{{ $pollTotalVotes }}">{{ trans_choice('ui.vote_count', $pollTotalVotes, ['count' => number_format($pollTotalVotes)]) }}</p>
            </div>
        </div>
    @endif

    @if($mediaItems->count() > 1)
        <!-- post media slider -->
        <div class="relative uk-visible-toggle sm:px-4" tabindex="-1" uk-slideshow="animation: push; ratio: 4:3">
            <ul class="uk-slideshow-items overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-900" uk-lightbox="animation: fade">
                @foreach($mediaItems as $mediaIndex => $media)
                    @php
                        $mediaUrl = $media->url();
                        $mediaAlt = $media->original_name ?: __('ui.post_media_alt', ['name' => $authorName]);
                    @endphp
                    <li class="w-full">
                        @if($media->isImage())
                            <a class="inline" href="{{ $mediaUrl }}" data-caption="{{ $mediaAlt }}">
                                <img src="{{ $mediaUrl }}" alt="{{ $mediaAlt }}" class="w-full h-full absolute object-cover inset-0">
                            </a>
                        @elseif($media->isVideo())
                            <video class="w-full h-full absolute object-contain inset-0 bg-black" muted playsinline preload="metadata" controls>
                                <source src="{{ $mediaUrl }}" type="{{ $media->mime_type }}">
                            </video>
                        @else
                            <a href="{{ $postUrl }}" class="w-full h-full absolute inset-0 grid place-items-center bg-slate-100 text-slate-500 dark:bg-slate-900 dark:text-white/70">
                                <ion-icon name="document-outline" class="text-4xl"></ion-icon>
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>

            <a class="nav-prev left-6" href="#" uk-slideshow-item="previous">
                <ion-icon name="chevron-back" class="text-2xl"></ion-icon>
            </a>
            <a class="nav-next right-6" href="#" uk-slideshow-item="next">
                <ion-icon name="chevron-forward" class="text-2xl"></ion-icon>
            </a>

            <div class="absolute right-7 bottom-3 rounded-full bg-black/70 px-3 py-1 text-xs font-bold text-white backdrop-blur">
                {{ trans_choice('ui.media_count', $mediaItems->count(), ['count' => $mediaItems->count()]) }}
            </div>
        </div>
    @elseif($firstMedia)
        <!-- post media -->
        <div class="relative w-full lg:h-96 h-full sm:px-4" @if($firstMedia->isImage()) uk-lightbox="animation: fade" @endif>
            @php
                $mediaUrl = $firstMedia->url();
                $mediaAlt = $firstMedia->original_name ?: __('ui.post_media_alt', ['name' => $authorName]);
            @endphp
            @if($firstMedia->isImage())
                <a href="{{ $mediaUrl }}" data-caption="{{ $mediaAlt }}">
                    <img src="{{ $mediaUrl }}" alt="{{ $mediaAlt }}" class="sm:rounded-lg w-full h-full object-cover">
                </a>
            @elseif($firstMedia->isVideo())
                <video class="sm:rounded-lg w-full h-full object-cover bg-black" muted playsinline preload="metadata" controls>
                    <source src="{{ $mediaUrl }}" type="{{ $firstMedia->mime_type }}">
                </video>
            @else
                <a href="{{ $postUrl }}" class="sm:rounded-lg w-full min-h-48 grid place-items-center bg-slate-100 text-slate-500 dark:bg-slate-900 dark:text-white/70">
                    <ion-icon name="document-outline" class="text-4xl"></ion-icon>
                </a>
            @endif
        </div>
    @elseif($gifPayload)
        <!-- post gif -->
        <div class="relative w-full lg:h-96 h-full sm:px-4">
            <a href="{{ $postUrl }}">
                <img src="{{ $gifPayload['gif_url'] }}" alt="{{ $gifPayload['title'] ?? 'GIF' }}" class="sm:rounded-lg w-full h-full object-cover">
            </a>
            <div class="absolute left-7 bottom-3 rounded-full bg-black/70 px-3 py-1 text-xs font-bold text-white backdrop-blur">
                GIF
            </div>
        </div>
    @endif

    <!-- post icons -->
    <div class="sm:p-4 p-2.5 flex items-center gap-4 text-xs font-semibold" data-socialite-post-actions="{{ $post->id }}">
        <div>
            <div class="flex items-center gap-2.5">
                <form method="post" action="{{ route('feed.reactions.toggle', $post) }}" data-socialite-reaction-form data-post-id="{{ $post->id }}" class="m-0">
                    @csrf
                    <input type="hidden" name="type" value="like">
                    <input type="hidden" name="mode" value="toggle">
                    <button type="submit" class="button-icon {{ $viewerReactionType ? 'text-red-500 bg-red-100 dark:bg-slate-700' : 'bg-slate-200/70 dark:bg-slate-700' }}" aria-label="{{ __('ui.react') }}" data-socialite-reaction-main>
                        @if($viewerReactionType && isset($reactionTypes[$viewerReactionType]))
                            <span class="text-base">{{ $reactionTypes[$viewerReactionType]['emoji'] }}</span>
                        @else
                            <ion-icon class="text-lg" name="heart"></ion-icon>
                        @endif
                    </button>
                </form>
                <button type="button" class="hh-feed-reaction-count" data-socialite-reaction-count data-socialite-reaction-list-url="{{ route('feed.reactions.index', $post) }}" aria-label="{{ __('ui.view_reactions') }}">{{ number_format($reactionCount) }}</button>
            </div>
            <div class="p-1 px-2 bg-white rounded-full drop-shadow-md w-[250px] dark:bg-slate-700 text-2xl" data-socialite-reaction-picker uk-drop="offset:10;pos: top-left; animate-out: true; animation: uk-animation-scale-up uk-transform-origin-bottom-left">
                <div class="flex gap-2" uk-scrollspy="target: > form; cls: uk-animation-scale-up; delay: 100 ;repeat: true">
                    @foreach($reactionTypes as $reactionType => $reactionMeta)
                        <form method="post" action="{{ route('feed.reactions.toggle', $post) }}" data-socialite-reaction-form data-post-id="{{ $post->id }}" class="m-0">
                            @csrf
                            <input type="hidden" name="type" value="{{ $reactionType }}">
                            <input type="hidden" name="mode" value="set">
                            <button type="submit" class="text-red-600 hover:scale-125 duration-300 {{ $viewerReactionType === $reactionType ? 'scale-125' : '' }}" title="{{ $reactionMeta['label'] }}" aria-label="{{ $reactionMeta['label'] }}">
                                <span>{{ $reactionMeta['emoji'] }}</span>
                            </button>
                        </form>
                    @endforeach
                </div>
                <div class="w-2.5 h-2.5 absolute -bottom-1 left-3 bg-white rotate-45 hidden"></div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="#socialite-comments-{{ $post->id }}" class="button-icon bg-slate-200/70 dark:bg-slate-700" aria-label="{{ __('ui.open_comments') }}">
                <ion-icon class="text-lg" name="chatbubble-ellipses"></ion-icon>
            </a>
            <span data-socialite-comment-count>{{ number_format($commentCount) }}</span>
        </div>

        <button type="button" class="button-icon ml-auto" aria-label="{{ __('ui.share_post') }}" data-socialite-share-button data-share-url="{{ $postUrl }}" data-share-title="{{ e($authorName . ' on HNT.rocks') }}">
            <ion-icon class="text-xl" name="paper-plane-outline"></ion-icon>
        </button>
        <form method="post" action="{{ route('feed.bookmarks.toggle', $post) }}" data-socialite-bookmark-form data-post-id="{{ $post->id }}" class="m-0">
            @csrf
            <button type="submit" class="button-icon {{ $viewerBookmarked ? 'text-blue-500 bg-blue-50 dark:bg-slate-700' : '' }}" aria-label="{{ $viewerBookmarked ? __('ui.remove_bookmark') : __('ui.bookmark_post') }}" data-socialite-bookmark-button data-bookmarked="{{ $viewerBookmarked ? '1' : '0' }}" data-bookmark-count="{{ $bookmarkCount }}">
                <ion-icon class="text-xl" name="{{ $viewerBookmarked ? 'bookmark' : 'bookmark-outline' }}"></ion-icon>
            </button>
        </form>
    </div>

    <!-- comments -->
    <div id="socialite-comments-{{ $post->id }}" class="sm:p-4 p-2.5 border-t border-gray-100 font-normal space-y-3 relative dark:border-slate-700/40" data-socialite-comments-list>
        @include('themes.socialite.feed.partials.comment-thread-list', ['comments' => $allComments, 'limitThreads' => $previewThreadLimit, 'limitRepliesPerThread' => 2])

        @if($commentCount > $allComments->count() || $commentCount > 2)
            <a href="{{ $postUrl }}" class="inline-flex items-center gap-1.5 text-gray-500 hover:text-blue-500 mt-2" data-socialite-more-comments data-socialite-more-comments-url="{{ $postUrl }}?socialite_comments=1">
                <ion-icon name="chevron-down-outline" class="duration-200"></ion-icon>
                <span data-socialite-more-comments-label>{{ __('ui.view_more_comments') }}</span>
            </a>
        @endif
    </div>

    <!-- add comment -->
    <form method="post" action="{{ route('feed.comments.store', $post) }}" class="sm:px-4 sm:py-3 p-2.5 border-t border-gray-100 flex items-end gap-2 dark:border-slate-700/40" data-socialite-comment-form data-post-id="{{ $post->id }}">
        @csrf
        <img src="{{ auth()->user()?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}" alt="" class="w-6 h-6 rounded-full object-cover mb-2">

        <input type="hidden" name="parent_id" value="" data-socialite-comment-parent>
        <textarea
            name="body"
            maxlength="50000"
            autocomplete="off"
            rows="1"
            class="flex-1 px-4 py-2 text-sm text-slate-700 dark:text-white rounded-lg bg-transparent outline-none resize-none"
            style="min-height: 40px; max-height: 144px; line-height: 1.35; overflow-y: auto;"
            placeholder="{{ __('ui.add_comment_placeholder') }}"
            required
            data-socialite-comment-input
        ></textarea>

        <button type="button" class="hidden text-xs text-gray-500 hover:text-blue-500 mb-2" data-socialite-comment-reply-cancel>{{ __('ui.cancel') }}</button>
        <button type="submit" class="text-sm rounded-full py-1.5 px-3.5 bg-secondery mb-1.5" data-socialite-comment-submit>{{ __('ui.post_submit') }}</button>
    </form>
</article>
