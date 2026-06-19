<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use App\Models\FeedPost;
use App\Models\Badge;
use App\Models\FeedComment;
use App\Models\FeedCommentReaction;
use App\Models\FeedReaction;
use App\Models\Friendship;
use App\Models\MediaAsset;
use App\Models\Quest;
use App\Models\Report;
use App\Models\Team;
use App\Models\User;
use App\Services\GamificationService;
use App\Services\Gifs\GifProviderService;
use App\Services\MediaService;
use App\Services\AI\MediaAiDisclosureService;
use App\Services\MentionService;
use App\Services\NotificationService;
use App\Services\Translation\FeedTranslationService;
use App\Support\HntTheme;
use App\Support\FeedTextRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class FeedController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        if (HntTheme::feedEnabled() && HntTheme::hasResolvedOverride('feed.live')) {
            return $this->socialiteIndex($request);
        }

        $allowedFilters = ['all', 'mentions', 'friends', 'teams', 'media'];
        $feedFilter = in_array($request->query('filter'), $allowedFilters, true)
            ? (string) $request->query('filter')
            : 'all';

        $viewer = $request->user();
        $viewerId = (int) $viewer->id;

        $friendIds = Friendship::query()
            ->forUser($viewer)
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->get(['user_one_id', 'user_two_id'])
            ->map(fn (Friendship $friendship): int => (int) ($friendship->user_one_id === $viewerId ? $friendship->user_two_id : $friendship->user_one_id))
            ->values();

        $feedPage = max(1, min(50, (int) $request->query('page', 1)));
        $postsPerLoad = 10;
        $visiblePostLimit = $feedPage * $postsPerLoad;

        $postsQuery = FeedPost::query()
            ->with([
                'user.profile',
                'team',
                'cupTeam.cup',
                'sharedPost.user.profile',
                'sharedPost.team',
                'sharedPost.cupTeam.cup',
                'sharedPost.media.mediaAsset',
                'media.mediaAsset',
                'comments.user.profile',
                'comments.reactions',
                'comments.viewerReaction',
                'reactions',
                'viewerReaction',
                'viewerBookmark',
                'poll.options.votes',
                'poll.votes',
            ])
            ->withCount(['comments', 'reactions', 'bookmarks'])
            ->withCount('sharedByPosts as shares_count')
            ->where('status', 'published')
            ->where(function ($query) use ($viewerId): void {
                $query->where(function ($normalPosts) use ($viewerId): void {
                    $normalPosts->whereNull('team_id')
                        ->where(function ($visibility) use ($viewerId): void {
                            $visibility->where('visibility', '!=', 'private')
                                ->orWhere('user_id', $viewerId);
                        });
                })->orWhere(function ($teamPosts) use ($viewerId): void {
                    $teamPosts->whereNotNull('team_id')
                        ->whereHas('team', function ($teamQuery) use ($viewerId): void {
                            $teamQuery->where('visibility', '!=', 'private')
                                ->orWhereHas('activeMembers', fn ($memberQuery) => $memberQuery->where('user_id', $viewerId));
                        });
                });
            })
            ->when($feedFilter === 'mentions', function ($query) use ($viewerId): void {
                $query->where(function ($mentionQuery) use ($viewerId): void {
                    $mentionQuery
                        ->whereHas('mentions', fn ($mentions) => $mentions->where('mentioned_user_id', $viewerId))
                        ->orWhereHas('comments.mentions', fn ($mentions) => $mentions->where('mentioned_user_id', $viewerId));
                });
            })
            ->when($feedFilter === 'friends', function ($query) use ($friendIds): void {
                $query->whereNull('team_id')
                    ->whereIn('user_id', $friendIds->all());
            })
            ->when($feedFilter === 'teams', function ($query): void {
                $query->whereNotNull('team_id');
            })
            ->when($feedFilter === 'media', function ($query): void {
                $query->where(function ($mediaQuery): void {
                    $mediaQuery->whereHas('media')
                        ->orWhereHas('sharedPost.media');
                });
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('pinned_at')
            ->latest();

        $posts = $postsQuery
            ->paginate($visiblePostLimit, ['*'], 'page', 1)
            ->withQueryString();

        $hasMoreFeedPosts = $posts->total() > $posts->count();
        $nextFeedPageQuery = $request->query();
        $nextFeedPageQuery['page'] = $feedPage + 1;

        if ($feedFilter === 'all') {
            unset($nextFeedPageQuery['filter']);
        } else {
            $nextFeedPageQuery['filter'] = $feedFilter;
        }

        $nextFeedPageUrl = $hasMoreFeedPosts ? route('feed.index', $nextFeedPageQuery) : null;
        $resetFeedPageUrl = route('feed.index', $feedFilter === 'all' ? [] : ['filter' => $feedFilter]);

        $reactionStats = FeedReaction::query()
            ->whereHas('post', fn ($query) => $query->whereNull('team_id')->where('user_id', auth()->id()))
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $featuredBadgeQuests = Quest::query()
            ->with(['progress' => fn ($query) => $query->where('user_id', $viewerId)])
            ->where('is_active', true)
            ->whereNotNull('badge_slug')
            ->whereDoesntHave('progress', function ($query) use ($viewerId): void {
                $query->where('user_id', $viewerId)
                    ->whereNotNull('completed_at');
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(12)
            ->get();

        $badgesBySlug = Badge::query()
            ->where('is_active', true)
            ->whereIn('slug', $featuredBadgeQuests->pluck('badge_slug')->filter()->unique()->all())
            ->get()
            ->keyBy('slug');

        $featuredBadges = $featuredBadgeQuests
            ->map(function (Quest $quest) use ($badgesBySlug): ?array {
                /** @var Badge|null $badge */
                $badge = $badgesBySlug->get($quest->badge_slug);

                if (! $badge) {
                    return null;
                }

                $progress = $quest->progress->first();
                $target = max(1, (int) $quest->target_count);
                $count = min($target, max(0, (int) ($progress?->progress_count ?? 0)));
                $percent = (int) min(100, round(($count / $target) * 100));

                return [
                    'badge' => $badge,
                    'quest' => $quest,
                    'count' => $count,
                    'target' => $target,
                    'percent' => $percent,
                    'xp' => max((int) $quest->xp_reward, (int) ($badge->xp_reward ?? 0)),
                    'image_url' => $badge->iconUrl() ?: $quest->iconUrl(),
                    'icon' => $badge->icon ?: $quest->icon,
                ];
            })
            ->filter()
            ->take(5)
            ->values();

        $publicRecruitingTeams = Team::query()
            ->withCount('activeMembers')
            ->where('visibility', 'public')
            ->where('recruitment_status', 'open')
            ->where('status', 'active')
            ->latest()
            ->limit(5)
            ->get();

        $friendsActivityItems = $this->friendsActivityItems($viewer, $friendIds);

        $reportedFeedKeys = $this->reportedFeedKeysForPosts($posts->getCollection(), $viewerId);

        return view('feed.index', [
            'posts' => $posts,
            'feedFilter' => $feedFilter,
            'reactionStats' => $reactionStats,
            'featuredBadges' => $featuredBadges,
            'publicRecruitingTeams' => $publicRecruitingTeams,
            'friendsActivityItems' => $friendsActivityItems,
            'feedPage' => $feedPage,
            'postsPerLoad' => $postsPerLoad,
            'hasMoreFeedPosts' => $hasMoreFeedPosts,
            'nextFeedPageUrl' => $nextFeedPageUrl,
            'resetFeedPageUrl' => $resetFeedPageUrl,
            'reportedFeedKeys' => $reportedFeedKeys,
        ]);
    }


    private function socialiteIndex(Request $request): View|JsonResponse
    {
        $allowedFilters = ['all', 'mentions', 'friends', 'teams', 'media'];
        $feedFilter = in_array($request->query('filter'), $allowedFilters, true)
            ? (string) $request->query('filter')
            : 'all';

        $viewer = $request->user();
        $viewerId = (int) $viewer->id;

        $friendIds = Friendship::query()
            ->forUser($viewer)
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->get(['user_one_id', 'user_two_id'])
            ->map(fn (Friendship $friendship): int => (int) ($friendship->user_one_id === $viewerId ? $friendship->user_two_id : $friendship->user_one_id))
            ->values();

        $posts = FeedPost::query()
            ->with([
                'user.profile',
                'team',
                'cupTeam.cup',
                'sharedPost.user.profile',
                'sharedPost.team',
                'sharedPost.cupTeam.cup',
                'sharedPost.media.mediaAsset',
                'media.mediaAsset',
                'comments.user.profile',
                'comments.reactions',
                'comments.viewerReaction',
                'reactions',
                'viewerReaction',
                'viewerBookmark',
                'poll.options.votes',
                'poll.votes',
            ])
            ->withCount(['comments', 'reactions', 'bookmarks', 'sharedByPosts as shares_count'])
            ->where('status', 'published')
            ->where(function ($query) use ($viewerId): void {
                $query->where(function ($normalPosts) use ($viewerId): void {
                    $normalPosts->whereNull('team_id')
                        ->where(function ($visibility) use ($viewerId): void {
                            $visibility->where('visibility', '!=', 'private')
                                ->orWhere('user_id', $viewerId);
                        });
                })->orWhere(function ($teamPosts) use ($viewerId): void {
                    $teamPosts->whereNotNull('team_id')
                        ->whereHas('team', function ($teamQuery) use ($viewerId): void {
                            $teamQuery->where('visibility', '!=', 'private')
                                ->orWhereHas('activeMembers', fn ($memberQuery) => $memberQuery->where('user_id', $viewerId));
                        });
                });
            })
            ->when($feedFilter === 'mentions', function ($query) use ($viewerId): void {
                $query->where(function ($mentionQuery) use ($viewerId): void {
                    $mentionQuery
                        ->whereHas('mentions', fn ($mentions) => $mentions->where('mentioned_user_id', $viewerId))
                        ->orWhereHas('comments.mentions', fn ($mentions) => $mentions->where('mentioned_user_id', $viewerId));
                });
            })
            ->when($feedFilter === 'friends', function ($query) use ($friendIds): void {
                $query->whereNull('team_id')
                    ->whereIn('user_id', $friendIds->all());
            })
            ->when($feedFilter === 'teams', function ($query): void {
                $query->whereNotNull('team_id');
            })
            ->when($feedFilter === 'media', function ($query): void {
                $query->where(function ($mediaQuery): void {
                    $mediaQuery->whereHas('media')
                        ->orWhereHas('sharedPost.media');
                });
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('pinned_at')
            ->latest()
            ->paginate(6)
            ->withQueryString();

        $reportedFeedKeys = $this->reportedFeedKeysForPosts($posts->getCollection(), $viewerId);

        if ($request->boolean('fragment')) {
            return response()->json([
                'html' => view(HntTheme::resolve('feed.partials.post-items'), [
                    'socialitePosts' => $posts,
                    'reportedFeedKeys' => $reportedFeedKeys,
                ])->render(),
                'nextPageUrl' => $posts->nextPageUrl(),
                'hasMorePages' => $posts->hasMorePages(),
            ]);
        }

        $members = User::query()
            ->with('profile')
            ->where('status', 'active')
            ->latest()
            ->limit(12)
            ->get();

        $teams = Team::query()
            ->withCount('activeMembers')
            ->where('visibility', 'public')
            ->where('status', 'active')
            ->latest()
            ->limit(5)
            ->get();

        return view(HntTheme::resolve('feed.live'), [
            'socialitePosts' => $posts,
            'socialiteMembers' => $members,
            'socialiteTeams' => $teams,
            'socialiteFeedFilter' => $feedFilter,
            'reportedFeedKeys' => $reportedFeedKeys,
        ]);
    }


    private function reportedFeedKeysForPosts(Collection $posts, int $viewerId): Collection
    {
        $postIds = $posts
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $commentIds = $posts
            ->flatMap(function (FeedPost $post): Collection {
                if (! $post->relationLoaded('comments')) {
                    return collect();
                }

                return $post->comments->pluck('id');
            })
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($postIds->isEmpty() && $commentIds->isEmpty()) {
            return collect();
        }

        return Report::query()
            ->where('reporter_id', $viewerId)
            ->whereIn('status', ['open', 'in_review'])
            ->where(function ($query) use ($postIds, $commentIds): void {
                if ($postIds->isNotEmpty()) {
                    $query->orWhere(function ($postQuery) use ($postIds): void {
                        $postQuery->where('reportable_type', FeedPost::class)
                            ->whereIn('reportable_id', $postIds->all());
                    });
                }

                if ($commentIds->isNotEmpty()) {
                    $query->orWhere(function ($commentQuery) use ($commentIds): void {
                        $commentQuery->where('reportable_type', FeedComment::class)
                            ->whereIn('reportable_id', $commentIds->all());
                    });
                }
            })
            ->get(['reportable_type', 'reportable_id'])
            ->mapWithKeys(function (Report $report): array {
                $type = $report->reportable_type === FeedComment::class ? 'feed_comment' : 'feed_post';

                return [$type . ':' . (int) $report->reportable_id => true];
            });
    }


    private function friendsActivityItems(User $viewer, Collection $friendIds): Collection
    {
        $friendIds = $friendIds
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($friendIds->isEmpty()) {
            return collect();
        }

        $viewerId = (int) $viewer->id;
        $ids = $friendIds->all();
        $items = collect();

        FeedPost::query()
            ->with(['user.profile', 'media.mediaAsset', 'team'])
            ->whereIn('user_id', $ids)
            ->where('status', 'published')
            ->where(function ($query) use ($viewerId): void {
                $this->constrainVisibleActivityPost($query, $viewerId);
            })
            ->where(function ($query): void {
                $this->constrainVisibleActivityUser($query);
            })
            ->latest()
            ->limit(8)
            ->get()
            ->each(function (FeedPost $post) use ($items): void {
                $items->push([
                    'user' => $post->user,
                    'action' => __('ui.activity_posted'),
                    'title' => $this->feedPostActivityLabel($post),
                    'url' => $post->permalink(),
                    'date' => $post->created_at,
                ]);
            });

        FeedComment::query()
            ->with(['user.profile', 'post.media.mediaAsset', 'post.team'])
            ->whereIn('user_id', $ids)
            ->whereHas('post', function ($query) use ($viewerId): void {
                $this->constrainVisibleActivityPost($query, $viewerId);
            })
            ->where(function ($query): void {
                $this->constrainVisibleActivityUser($query);
            })
            ->latest()
            ->limit(8)
            ->get()
            ->each(function (FeedComment $comment) use ($items): void {
                if (! $comment->post || ! $comment->user) {
                    return;
                }

                $items->push([
                    'user' => $comment->user,
                    'action' => __('ui.activity_commented'),
                    'title' => $this->feedPostActivityLabel($comment->post),
                    'url' => $comment->post->permalink($comment),
                    'date' => $comment->created_at,
                ]);
            });

        FeedReaction::query()
            ->with(['user.profile', 'post.media.mediaAsset', 'post.team'])
            ->whereIn('user_id', $ids)
            ->whereHas('post', function ($query) use ($viewerId): void {
                $this->constrainVisibleActivityPost($query, $viewerId);
            })
            ->where(function ($query): void {
                $this->constrainVisibleActivityUser($query);
            })
            ->latest()
            ->limit(8)
            ->get()
            ->each(function (FeedReaction $reaction) use ($items): void {
                if (! $reaction->post || ! $reaction->user) {
                    return;
                }

                $items->push([
                    'user' => $reaction->user,
                    'action' => __('ui.activity_reacted_post'),
                    'title' => $this->feedPostActivityLabel($reaction->post),
                    'url' => $reaction->post->permalink(),
                    'date' => $reaction->created_at,
                ]);
            });

        FeedCommentReaction::query()
            ->with(['user.profile', 'comment.post.media.mediaAsset', 'comment.post.team'])
            ->whereIn('user_id', $ids)
            ->whereHas('comment.post', function ($query) use ($viewerId): void {
                $this->constrainVisibleActivityPost($query, $viewerId);
            })
            ->where(function ($query): void {
                $this->constrainVisibleActivityUser($query);
            })
            ->latest()
            ->limit(8)
            ->get()
            ->each(function (FeedCommentReaction $reaction) use ($items): void {
                $comment = $reaction->comment;
                $post = $comment?->post;

                if (! $post || ! $comment || ! $reaction->user) {
                    return;
                }

                $items->push([
                    'user' => $reaction->user,
                    'action' => __('ui.activity_reacted_comment'),
                    'title' => __('ui.activity_comment_title'),
                    'url' => $post->permalink($comment),
                    'date' => $reaction->created_at,
                ]);
            });

        MediaAsset::query()
            ->with(['user.profile'])
            ->whereIn('user_id', $ids)
            ->whereIn('context', ['profile_avatar', 'profile_cover'])
            ->where(function ($query): void {
                $this->constrainVisibleActivityUser($query);
            })
            ->latest()
            ->limit(8)
            ->get()
            ->each(function (MediaAsset $asset) use ($items): void {
                if (! $asset->user) {
                    return;
                }

                $items->push([
                    'user' => $asset->user,
                    'action' => __('ui.activity_changed'),
                    'title' => $asset->context === 'profile_cover' ? __('ui.activity_cover') : __('ui.activity_avatar'),
                    'url' => route('profile.public', $asset->user),
                    'date' => $asset->created_at,
                ]);
            });

        return $items
            ->filter(fn (array $item): bool => $item['user'] instanceof User && filled($item['title']) && filled($item['url']))
            ->sortByDesc(fn (array $item) => $item['date'])
            ->take(5)
            ->values();
    }

    private function constrainVisibleActivityPost($query, int $viewerId): void
    {
        $query->where('status', 'published')
            ->where(function ($visibleQuery) use ($viewerId): void {
                $visibleQuery->where(function ($normalPosts) use ($viewerId): void {
                    $normalPosts->whereNull('team_id')
                        ->where(function ($visibility) use ($viewerId): void {
                            $visibility->where('visibility', '!=', 'private')
                                ->orWhere('user_id', $viewerId);
                        });
                })->orWhere(function ($teamPosts) use ($viewerId): void {
                    $teamPosts->whereNotNull('team_id')
                        ->whereHas('team', function ($teamQuery) use ($viewerId): void {
                            $teamQuery->where('visibility', '!=', 'private')
                                ->orWhereHas('activeMembers', fn ($memberQuery) => $memberQuery->where('user_id', $viewerId));
                        });
                });
            });
    }

    private function constrainVisibleActivityUser($query): void
    {
        $query->whereHas('user', function ($userQuery): void {
            $userQuery->whereDoesntHave('privacySettings')
                ->orWhereHas('privacySettings', fn ($privacyQuery) => $privacyQuery->where('show_activity_feed', true));
        });
    }

    private function feedPostActivityLabel(FeedPost $post): string
    {
        $post->loadMissing(['media.mediaAsset']);

        if ($post->media->contains(fn ($media): bool => $media->isVideo())) {
            return __('ui.feed_video');
        }

        if ($post->media->contains(fn ($media): bool => $media->isImage())) {
            return __('ui.feed_photo');
        }

        if ($post->isSharedPost()) {
            return __('ui.feed_post');
        }

        return __('ui.feed_status_update');
    }


    public function show(Request $request, FeedPost $post): View|JsonResponse
    {
        $post->loadMissing([
            'user.profile',
            'team',
            'sharedPost.user.profile',
            'sharedPost.team',
            'sharedPost.media.mediaAsset',
            'media.mediaAsset',
            'comments.user.profile',
            'comments.media.mediaAsset',
            'comments.reactions',
            'comments.viewerReaction',
            'reactions',
            'viewerReaction',
            'viewerBookmark',
            'poll.options.votes',
            'poll.votes',
        ]);

        $post->loadCount(['comments', 'reactions', 'bookmarks']);
        $post->loadCount('sharedByPosts as shares_count');

        abort_unless($post->status === 'published' && $post->canBeViewedBy($request->user()), 404);

        if ($request->boolean('hnt_preview_comments') && HntTheme::feedEnabled()) {
            $reportedFeedKeys = $this->reportedFeedKeysForPosts(collect([$post]), (int) $request->user()->id);

            return response()->json([
                'html' => view(HntTheme::resolve('feed.partials.comment-modal-content'), [
                    'post' => $post,
                    'reportedFeedKeys' => $reportedFeedKeys,
                ])->render(),
                'count' => $post->comments->count(),
                'post_id' => $post->id,
                'store_url' => route('feed.comments.store', $post),
            ]);
        }

        if ($request->boolean('socialite_comments')) {
            $comments = $post->comments;
            $html = view('themes.socialite.feed.partials.comment-thread-list', [
                'comments' => $comments,
                'limitThreads' => null,
            ])->render();

            return response()->json([
                'html' => $html,
                'count' => $comments->count(),
            ]);
        }

        if (HntTheme::feedEnabled() && HntTheme::hasResolvedOverride('feed.show')) {
            return view(HntTheme::resolve('feed.show'), [
                'post' => $post,
            ]);
        }

        return view('feed.show', [
            'post' => $post,
            'reportedFeedKeys' => $this->reportedFeedKeysForPosts(collect([$post]), (int) $request->user()->id),
        ]);
    }

    public function store(Request $request, MediaService $mediaService, MediaAiDisclosureService $aiDisclosure, GamificationService $gamification, MentionService $mentions, NotificationService $notifications, GifProviderService $gifs, FeedTranslationService $translations): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'visibility' => ['required', 'string', 'in:public,followers,private'],
            'background_style' => ['nullable', 'string', 'in:none,bayou,blood,gold,night'],
            'feeling_key' => ['nullable', 'string', 'in:none,happy,excited,focused,chill,tired,salty'],
            'poll_question' => ['nullable', 'string', 'max:180'],
            'poll_options' => ['nullable', 'array', 'max:6'],
            'poll_options.*' => ['nullable', 'string', 'max:180'],
            'gif_provider' => ['nullable', 'string', 'max:40'],
            'gif_id' => ['nullable', 'string', 'max:255'],
            'gif_url' => ['nullable', 'url', 'max:2048'],
            'gif_preview_url' => ['nullable', 'url', 'max:2048'],
            'gif_title' => ['nullable', 'string', 'max:180'],
            'gif_source_url' => ['nullable', 'url', 'max:2048'],
            'media' => ['nullable', 'array', 'max:'.config('hunthub.upload_limits.feed_media_count', 12)],
            'media.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm,mov', 'max:'.config('hunthub.upload_limits.feed_media_kb', 51200)],
            'ai_generated' => ['nullable', 'boolean'],
        ]);

        $files = $request->file('media', []);

        foreach ($files as $file) {
            $mediaService->assertAllowed($file, $request->user(), 'feed');
        }

        $pollOptions = $this->validatedPollOptions($validated['poll_options'] ?? []);
        $pollQuestion = trim((string) ($validated['poll_question'] ?? ''));
        $hasPoll = count($pollOptions) >= 2;
        $gifSelection = $gifs->normalizeSelection($validated);
        $hasGif = $gifSelection !== null;

        if (! filled($validated['body'] ?? null) && count($files) === 0 && ! $hasPoll && ! $hasGif) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('ui.feed_body_or_media_required'),
                    'errors' => [
                        'body' => [__('ui.feed_body_or_media_required')],
                    ],
                ], 422);
            }

            return back()
                ->withErrors(['body' => __('ui.feed_body_or_media_required')])
                ->withInput();
        }

        $postBody = $validated['body'] ?? null;
        $backgroundStyle = FeedPost::normalizeBackgroundStyle($validated['background_style'] ?? null);
        $feelingKey = FeedPost::normalizeFeelingKey($validated['feeling_key'] ?? null);
        $hasMediaUploads = count($files) > 0;
        $userDeclaredAi = $hasMediaUploads && $request->boolean('ai_generated');
        $aiDetection = $hasMediaUploads && ! $userDeclaredAi
            ? $aiDisclosure->analyzeUploads($files, 'feed')
            : null;

        $post = FeedPost::create([
            'user_id' => $request->user()->id,
            'body' => $postBody,
            'background_style' => $backgroundStyle,
            'feeling_key' => $feelingKey,
            'gif_provider' => $gifSelection['gif_provider'] ?? null,
            'gif_id' => $gifSelection['gif_id'] ?? null,
            'gif_url' => $gifSelection['gif_url'] ?? null,
            'gif_preview_url' => $gifSelection['gif_preview_url'] ?? null,
            'gif_title' => $gifSelection['gif_title'] ?? null,
            'gif_source_url' => $gifSelection['gif_source_url'] ?? null,
            'source_language' => $translations->detectLanguage($postBody),
            'ai_user_declared' => $userDeclaredAi,
            'ai_detected_possible' => (bool) ($aiDetection['possible'] ?? false),
            'ai_detection_confidence' => $aiDetection ? (float) ($aiDetection['confidence'] ?? 0) : null,
            'ai_detection_reason' => $aiDetection['reason'] ?? null,
            'ai_detection_source' => $aiDetection['source'] ?? null,
            'ai_detection_model' => $aiDetection['model'] ?? null,
            'ai_detection_error' => $aiDetection['error'] ?? null,
            'ai_detection_checked_at' => $aiDetection ? now() : null,
            'visibility' => $validated['visibility'],
            'status' => 'published',
        ]);

        if ($hasPoll) {
            $poll = $post->poll()->create([
                'question' => $pollQuestion !== '' ? $pollQuestion : null,
                'allows_multiple' => false,
            ]);

            foreach ($pollOptions as $index => $optionBody) {
                $poll->options()->create([
                    'body' => $optionBody,
                    'sort_order' => $index,
                ]);
            }
        }

        foreach ($files as $index => $file) {
            $asset = $mediaService->store($file, $request->user(), 'feed', [
                'attachable' => $post,
                'visibility' => $validated['visibility'],
            ]);

            $post->media()->create([
                'user_id' => $request->user()->id,
                'media_asset_id' => $asset->id,
                'disk' => $asset->disk,
                'path' => $asset->path,
                'mime_type' => $asset->mime_type,
                'original_name' => $asset->original_name,
                'size_bytes' => $asset->size_bytes,
                'sort_order' => $index,
            ]);
        }

        $gamification->award($request->user(), 'feed_post_created', source: $post);
        $mentions->syncForFeedPost($post, $request->user(), $post->body, $notifications);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => __('ui.feed_post_published'),
                'redirect_url' => route('feed.index'),
                'post_id' => $post->id,
            ]);
        }

        return redirect()->route('feed.index')->with('status', __('ui.feed_post_published'));
    }


    public function share(Request $request, FeedPost $post, GamificationService $gamification, MentionService $mentions, NotificationService $notifications, GifProviderService $gifs, FeedTranslationService $translations): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:1000'],
        ]);

        $post->loadMissing(['sharedPost', 'team']);

        $sourcePost = $post->sharedPost ?: $post;
        $sourcePost->loadMissing(['team']);

        abort_unless($sourcePost->canBeViewedBy($request->user()), 403);

        if (! $sourcePost->isTeamPost() && $sourcePost->visibility !== 'public') {
            if ($request->expectsJson()) {
                return response()->json([
                    'shared' => false,
                    'count' => $sourcePost->sharedByPosts()->count(),
                    'message' => __('ui.feed_public_only_share'),
                ], 422);
            }

            return redirect()
                ->route('feed.index')
                ->with('status', __('ui.feed_public_only_share'));
        }

        if ($sourcePost->isTeamPost() && $sourcePost->team?->visibility === 'private') {
            if ($request->expectsJson()) {
                return response()->json([
                    'shared' => false,
                    'count' => $sourcePost->sharedByPosts()->count(),
                    'message' => __('ui.feed_private_team_not_shareable'),
                ], 422);
            }

            return redirect()
                ->route('feed.index')
                ->with('status', __('ui.feed_private_team_not_shareable'));
        }

        $existingShare = FeedPost::query()
            ->where('user_id', $request->user()->id)
            ->where('shared_post_id', $sourcePost->id)
            ->first();

        if ($existingShare) {
            if ($request->expectsJson()) {
                return response()->json([
                    'shared' => true,
                    'already_shared' => true,
                    'count' => $sourcePost->sharedByPosts()->count(),
                    'message' => __('ui.feed_already_shared'),
                ]);
            }

            return redirect()
                ->route('feed.index')
                ->with('status', __('ui.feed_already_shared'));
        }

        $shareText = trim((string) ($validated['body'] ?? ''));

        $share = FeedPost::create([
            'user_id' => $request->user()->id,
            'team_id' => $sourcePost->team_id,
            'shared_post_id' => $sourcePost->id,
            'body' => $shareText !== '' ? $shareText : null,
            'background_style' => null,
            'feeling_key' => null,
            'source_language' => $translations->detectLanguage($shareText !== '' ? $shareText : null),
            'visibility' => $sourcePost->isTeamPost() ? 'team' : 'public',
            'status' => 'published',
        ]);

        $gamification->award($request->user(), 'feed_post_shared', source: $share, description: 'Beitrag geteilt');
        $mentions->syncForFeedPost($share, $request->user(), $share->body, $notifications);

        if ($request->expectsJson()) {
            return response()->json([
                'shared' => true,
                'already_shared' => false,
                'count' => $sourcePost->sharedByPosts()->count(),
                'message' => __('ui.feed_post_shared'),
                'share_id' => $share->id,
            ]);
        }

        return redirect()
            ->route('feed.index')
            ->with('status', __('ui.feed_post_shared'));
    }


    public function togglePin(Request $request, FeedPost $post): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless($post->status === 'published' && $post->canBeViewedBy($request->user()), 404);

        if ($post->is_pinned) {
            $post->update([
                'is_pinned' => false,
                'pinned_at' => null,
                'pinned_by_user_id' => null,
            ]);

            return back()->with('status', __('ui.feed_post_unpinned'));
        }

        $post->update([
            'is_pinned' => true,
            'pinned_at' => now(),
            'pinned_by_user_id' => $request->user()->id,
        ]);

        return back()->with('status', __('ui.feed_post_pinned'));
    }

    public function update(Request $request, FeedPost $post, MentionService $mentions, NotificationService $notifications, GifProviderService $gifs, FeedTranslationService $translations): RedirectResponse|JsonResponse
    {
        abort_unless($post->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'visibility' => ['required', 'string', 'in:public,followers,private,team'],
            'background_style' => ['nullable', 'string', 'in:none,bayou,blood,gold,night'],
            'feeling_key' => ['nullable', 'string', 'in:none,happy,excited,focused,chill,tired,salty'],
        ]);

        if ($post->isTeamPost()) {
            $validated['visibility'] = 'team';
        } elseif ($validated['visibility'] === 'team') {
            $validated['visibility'] = 'public';
        }

        $backgroundStyle = array_key_exists('background_style', $validated)
            ? FeedPost::normalizeBackgroundStyle($validated['background_style'] ?? null)
            : $post->background_style;
        $feelingKey = array_key_exists('feeling_key', $validated)
            ? FeedPost::normalizeFeelingKey($validated['feeling_key'] ?? null)
            : $post->feeling_key;

        $post->update([
            'body' => $validated['body'],
            'background_style' => $backgroundStyle,
            'feeling_key' => $feelingKey,
            'source_language' => $translations->detectLanguage($validated['body']),
            'visibility' => $validated['visibility'],
        ]);

        $post->translations()->delete();

        $mentions->syncForFeedPost($post, $request->user(), $post->body, $notifications);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'id' => $post->id,
                'body' => $post->body,
                'body_html' => FeedTextRenderer::render($post->body),
                'internal_link_previews' => FeedTextRenderer::internalLinkPreviews($post->body, 1),
                'visibility' => $post->visibility,
                'background_style' => $post->background_style,
                'visibility_label' => $post->visibilityLabel(),
            ]);
        }

        return redirect($post->redirectRoute())->with('status', __('ui.feed_post_updated'));
    }

    public function destroy(Request $request, FeedPost $post, MediaService $mediaService): RedirectResponse|JsonResponse
    {
        $viewer = $request->user();
        abort_unless(
            (int) $post->user_id === (int) $viewer->id
            || ($viewer && method_exists($viewer, 'isAdmin') && $viewer->isAdmin()),
            403
        );

        $redirectTo = $post->redirectRoute();
        $postId = (int) $post->id;

        foreach ($post->media as $media) {
            if ($media->mediaAsset) {
                $mediaService->delete($media->mediaAsset);
                continue;
            }

            Storage::disk($media->disk)->delete($media->path);
        }

        $post->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'id' => $postId,
                'message' => __('ui.feed_post_deleted'),
            ]);
        }

        return redirect($redirectTo)->with('status', __('ui.feed_post_deleted'));
    }

    private function validatedPollOptions(array $options): array
    {
        return collect($options)
            ->map(fn ($option) => trim((string) $option))
            ->filter(fn (string $option) => $option !== '')
            ->unique()
            ->take(6)
            ->values()
            ->all();
    }
}
