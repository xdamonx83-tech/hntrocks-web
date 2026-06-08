@php
    $allComments = collect($comments ?? []);
    $limitThreads = $limitThreads ?? null;
    $limitRepliesPerThread = $limitRepliesPerThread ?? null;
    $commentsById = $allComments->keyBy(fn ($item) => (int) $item->id);

    $rootIdFor = function ($comment) use ($commentsById): int {
        $parentId = (int) ($comment->parent_id ?? 0);
        $guard = 0;

        while ($parentId > 0 && $commentsById->has($parentId) && $guard < 10) {
            $parent = $commentsById->get($parentId);
            $nextParentId = (int) ($parent->parent_id ?? 0);

            if ($nextParentId < 1) {
                return (int) $parent->id;
            }

            $parentId = $nextParentId;
            $guard++;
        }

        return $parentId > 0 ? $parentId : (int) $comment->id;
    };

    $rootComments = $allComments
        ->filter(fn ($item) => empty($item->parent_id) || ! $commentsById->has((int) $item->parent_id))
        ->values();

    $repliesByParent = $allComments
        ->filter(fn ($item) => ! empty($item->parent_id) && $commentsById->has((int) $item->parent_id))
        ->groupBy(fn ($item) => $rootIdFor($item));

    $threads = $rootComments
        ->map(function ($root) use ($repliesByParent) {
            $replies = $repliesByParent->get((int) $root->id, collect())->sortBy('created_at')->values();
            $latestReply = $replies->sortByDesc('created_at')->first();
            $latestAt = $latestReply?->created_at && $latestReply->created_at->gt($root->created_at)
                ? $latestReply->created_at
                : $root->created_at;

            return [
                'root' => $root,
                'replies' => $replies,
                'latest_at' => $latestAt,
            ];
        })
        ->sortByDesc('latest_at')
        ->values();

    if ($limitThreads) {
        $threads = $threads->take((int) $limitThreads)->values();
    }
@endphp

@forelse($threads as $thread)
    @php
        $root = $thread['root'];
        $replies = $thread['replies'];
        $replyCount = $replies->count();
        if ($limitRepliesPerThread && $replies->count() > (int) $limitRepliesPerThread) {
            $replies = $replies->sortByDesc('created_at')->take((int) $limitRepliesPerThread)->sortBy('created_at')->values();
        }
        $replyToggleLabel = $replyCount === 1 ? '1 Antwort laden...' : $replyCount . ' Antworten laden...';
    @endphp

    <div class="space-y-2" data-socialite-comment-thread="{{ $root->id }}">
        @include('themes.socialite.feed.partials.comment', ['comment' => $root, 'isReply' => false, 'rootId' => $root->id])

        @if($replyCount > 0)
            <button
                type="button"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-500 hover:text-blue-600"
                style="margin-left: 92px;"
                data-socialite-replies-toggle
                data-root-id="{{ $root->id }}"
                data-replies-count="{{ $replyCount }}"
                aria-expanded="false"
            >
                <ion-icon name="chevron-down-outline" class="text-sm duration-200" data-socialite-replies-toggle-icon></ion-icon>
                <span data-socialite-replies-toggle-label>{{ $replyToggleLabel }}</span>
            </button>
        @endif

        <div class="hidden space-y-2" style="margin-left: 92px;" data-socialite-replies="{{ $root->id }}">
            @foreach($replies as $reply)
                @include('themes.socialite.feed.partials.comment', ['comment' => $reply, 'isReply' => true, 'rootId' => $root->id])
            @endforeach
        </div>
    </div>
@empty
    <div class="text-sm text-gray-500 dark:text-white/70" data-socialite-empty-comments>{{ __('ui.no_comments_yet') }}</div>
@endforelse
