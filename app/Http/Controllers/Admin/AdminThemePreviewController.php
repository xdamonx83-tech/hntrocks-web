<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeedPost;
use App\Models\Friendship;
use App\Support\HntTheme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class AdminThemePreviewController extends Controller
{
    public function index(Request $request): View
    {
        $this->guardAdmin($request);

        $user = $request->user();
        $previewTheme = HntTheme::previewTheme();
        $fallbackTheme = HntTheme::previewFallbackTheme();

        return view('admin.theme-preview.index', [
            'previewTheme' => $previewTheme,
            'fallbackTheme' => $fallbackTheme,
            'activeTheme' => HntTheme::active(),
            'previewActive' => HntTheme::previewActive($user),
            'previewAvailable' => HntTheme::previewAvailableFor($user),
            'previewRestrictionConfigured' => HntTheme::previewRestrictionConfigured(),
            'allowedUserIds' => HntTheme::previewAllowedUserIds(),
            'allowedEmails' => HntTheme::previewAllowedEmails(),
            'allowAnyAdmin' => (bool) config('hunthub.theme.preview.allow_any_admin', false),
            'sessionKey' => HntTheme::PREVIEW_SESSION_KEY,
            'sampleResolutions' => $this->sampleResolutions(),
            'templateReferences' => $this->templateReferences(),
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $this->guardAdmin($request);

        if (! HntTheme::previewAvailableFor($request->user())) {
            return back()->with('error', 'Theme-Preview ist für diesen Admin nicht freigeschaltet. Bitte HH_THEME_PREVIEW_USER_IDS oder HH_THEME_PREVIEW_USER_EMAILS setzen.');
        }

        HntTheme::activatePreview();

        return back()->with('status', 'Theme-Preview wurde für deine aktuelle Admin-Session aktiviert.');
    }

    public function stop(Request $request): RedirectResponse
    {
        $this->guardAdmin($request);
        HntTheme::deactivatePreview();

        return back()->with('status', 'Theme-Preview wurde für deine aktuelle Session beendet.');
    }

    public function shell(Request $request): View|JsonResponse
    {
        $this->guardAdmin($request);

        abort_unless(HntTheme::previewActive($request->user()), 403);

        if ($request->boolean('data')) {
            return $this->dashboardFeedData($request);
        }

        return view('themes.hnt_preview.feed.live');
    }

    private function dashboardFeedData(Request $request): JsonResponse
    {
        $viewer = $request->user();
        $viewerId = (int) $viewer->id;
        $mode = $request->query('mode') === 'following' ? 'following' : 'for-you';

        $friendIds = Friendship::query()
            ->forUser($viewer)
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->get(['user_one_id', 'user_two_id'])
            ->map(fn (Friendship $friendship): int => (int) (
                $friendship->user_one_id === $viewerId
                    ? $friendship->user_two_id
                    : $friendship->user_one_id
            ))
            ->values();

        $posts = FeedPost::query()
            ->with([
                'user.profile',
                'team',
                'media.mediaAsset',
                'comments.user.profile',
                'comments.reactions',
                'viewerReaction',
                'viewerBookmark',
                'poll.options.votes',
                'poll.votes',
            ])
            ->withCount(['comments', 'reactions', 'bookmarks', 'sharedByPosts as shares_count'])
            ->where('status', 'published')
            ->whereNull('shared_post_id')
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
            ->when(
                $mode === 'following',
                fn ($query) => $friendIds->isEmpty()
                    ? $query->whereRaw('1 = 0')
                    : $query->whereNull('team_id')->whereIn('user_id', $friendIds->all())
            )
            ->orderByDesc('is_pinned')
            ->orderByDesc('pinned_at')
            ->latest()
            ->limit(8)
            ->get();

        $viewer->loadMissing(['profile', 'crownWallet']);

        $friendCount = Friendship::query()
            ->forUser($viewer)
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->count();

        $friendRequestCount = Friendship::query()
            ->where('recipient_id', $viewerId)
            ->where('status', Friendship::STATUS_PENDING)
            ->count();

        $notificationCount = $viewer->notificationItems()
            ->standard()
            ->unread()
            ->count();

        return response()->json([
            'profile' => [
                'name' => $viewer->name ?: ($viewer->username ?: 'HNT Hunter'),
                'handle' => $viewer->username ? '@' . $viewer->username : '@hunter',
                'avatar' => $viewer->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'),
                'rocks' => (int) ($viewer->crownWallet?->balance ?? 0),
                'friends' => $friendCount,
                'posts' => FeedPost::query()
                    ->where('user_id', $viewerId)
                    ->where('status', 'published')
                    ->count(),
                'level' => max(1, (int) ($viewer->level ?: 1)),
            ],
            'badges' => [
                'messages' => $viewer->unreadMessagesCount(),
                'notifications' => $notificationCount,
                'friends' => $friendRequestCount,
            ],
            'mode' => $mode,
            'posts' => $posts->map(fn (FeedPost $post): array => $this->serializePreviewPost($post, $viewerId))->values(),
        ]);
    }

    private function serializePreviewPost(FeedPost $post, int $viewerId): array
    {
        $author = $post->user;
        $poll = $post->poll;
        $pollVotes = $poll?->votes ?? collect();
        $pollTotalVotes = $pollVotes->count();
        $viewerPollVote = $pollVotes->firstWhere('user_id', $viewerId);

        $media = $post->media
            ->map(function ($item) use ($author): ?array {
                try {
                    $url = $item->url();
                } catch (Throwable) {
                    return null;
                }

                return [
                    'url' => $url,
                    'type' => $item->isImage() ? 'image' : ($item->isVideo() ? 'video' : 'file'),
                    'mime' => (string) $item->mime_type,
                    'alt' => $item->original_name ?: (($author?->name ?: 'HNT Hunter') . ' – Medieninhalt'),
                ];
            })
            ->filter()
            ->values();

        $pollPayload = null;

        if ($poll && $poll->options->isNotEmpty()) {
            $pollPayload = [
                'question' => $poll->question ?: 'Community-Umfrage',
                'total_votes' => $pollTotalVotes,
                'options' => $poll->options->map(function ($option) use ($pollTotalVotes, $viewerPollVote): array {
                    $votes = $option->votes->count();

                    return [
                        'id' => (int) $option->id,
                        'body' => (string) $option->body,
                        'votes' => $votes,
                        'percent' => $pollTotalVotes > 0 ? (int) round(($votes / $pollTotalVotes) * 100) : 0,
                        'selected' => (int) ($viewerPollVote?->feed_post_poll_option_id ?? 0) === (int) $option->id,
                    ];
                })->values(),
            ];
        }

        $comments = $post->comments
            ->take(8)
            ->map(fn ($comment): array => [
                'id' => (int) $comment->id,
                'name' => $comment->user?->name ?: ($comment->user?->username ?: 'HNT Hunter'),
                'handle' => $comment->user?->username ? '@' . $comment->user->username : '@hunter',
                'time' => $comment->created_at?->diffForHumans() ?: 'gerade eben',
                'avatar' => $comment->user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'),
                'text' => trim((string) $comment->body),
                'likes' => $comment->reactions->count(),
            ])
            ->values();

        return [
            'id' => (int) $post->id,
            'body' => trim((string) $post->body),
            'permalink' => route('feed.show', $post),
            'created_at' => $post->created_at?->diffForHumans() ?: 'gerade eben',
            'visibility' => $post->visibilityLabel(),
            'is_pinned' => (bool) $post->is_pinned,
            'team' => $post->team ? [
                'name' => $post->team->name,
                'url' => route('teams.show', $post->team),
            ] : null,
            'author' => [
                'id' => (int) ($author?->id ?? 0),
                'name' => $author?->name ?: ($author?->username ?: 'HNT Hunter'),
                'handle' => $author?->username ? '@' . $author->username : '@hunter',
                'avatar' => $author?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'),
                'profile_url' => $author?->username
                    ? ((int) $author->id === $viewerId ? route('profile.show') : route('profile.public', $author))
                    : '#',
            ],
            'badge' => $post->team ? 'Team' : ($pollPayload ? 'Diskussion' : 'Beitrag'),
            'badge_class' => $post->team ? 'team' : ($pollPayload ? 'discussion' : 'discussion'),
            'counts' => [
                'reactions' => (int) ($post->reactions_count ?? 0),
                'comments' => (int) ($post->comments_count ?? 0),
                'bookmarks' => (int) ($post->bookmarks_count ?? 0),
                'shares' => (int) ($post->shares_count ?? 0),
            ],
            'viewer' => [
                'reacted' => (bool) $post->viewerReaction,
                'reaction_type' => $post->viewerReaction?->type,
                'bookmarked' => (bool) $post->viewerBookmark,
            ],
            'media' => $media,
            'poll' => $pollPayload,
            'comments' => $comments,
            'excerpt' => Str::limit(strip_tags((string) $post->body), 180),
        ];
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }

    private function sampleResolutions(): array
    {
        $samples = [
            'feed.live',
            'feed.show',
            'profile.show',
            'profile.edit',
            'members.index',
            'cups.index',
            'cups.show',
            'messages.index',
            'notifications.index',
            'gamification.index',
        ];

        return collect($samples)
            ->map(fn (string $view): array => [
                'view' => $view,
                'resolved' => HntTheme::resolve($view),
            ])
            ->all();
    }

    private function templateReferences(): array
    {
        return [
            'feed.html' => 'Dashboard Feed Preview / globale Shell',
            'profile.html' => 'Profil',
            'profile-edit.html' => 'Profil bearbeiten',
            'members.html' => 'Mitglieder',
            'cups.html' => 'Cup-Übersicht',
            'cup-detail.html' => 'Cup-Detail',
            'cup-feedback.html' => 'Cup-Feedback',
            'gamification.html' => 'Badges / Gamification',
            'crowns.html' => 'Bounty Marks Wallet',
            'crowns-shop.html' => 'Marks Shop',
            'crowns-inventory.html' => 'Marks Inventar',
            'hall-of-fame.html' => 'Hall of Fame',
            'contracts.html' => 'HNT-Aufträge',
            'lfg.html' => 'LFG',
            'lfg-detail.html' => 'LFG Detail',
        ];
    }
}
