<?php

namespace App\Http\Controllers\Socialite;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Throwable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Moment;
use App\Models\LfgPost;
use App\Models\HntMapMarker;
use App\Models\HntMap;
use App\Models\FeedPost;
use App\Models\FeedComment;
use App\Models\Cup;
use App\Models\Friendship;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeaderLiveController extends Controller
{
    public function badges(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'authenticated' => false,
                'notifications_unread' => 0,
                'messages_unread' => 0,
                'friend_request_count' => 0,
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        return response()->json([
            'authenticated' => true,
            'notifications_unread' => $user->notificationItems()->standard()->unread()->count(),
            'messages_unread' => $user->unreadMessagesCount(),
            'friend_request_count' => Friendship::query()
                ->where('recipient_id', $user->id)
                ->where('status', Friendship::STATUS_PENDING)
                ->count(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }


    public function notifications(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'authenticated' => false,
                'html' => '',
                'unread_count' => 0,
                'total_count' => 0,
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        if ($request->query('variant') === 'rework') {
            $notifications = $user
                ->notificationItems()
                ->standard()
                ->with('actor.profile')
                ->orderByRaw('read_at is not null')
                ->latest()
                ->limit(5)
                ->get();

            return response()->json([
                'authenticated' => true,
                'html' => view('themes.rework.feed.partials.header-notifications', [
                    'headerNotifications' => $notifications,
                    'notificationsUrl' => route('notifications.index'),
                    'defaultAvatar' => asset('assets/vikinger/img/default-avatar.svg'),
                ])->render(),
                'unread_count' => $user->notificationItems()->standard()->unread()->count(),
                'total_count' => $user->notificationItems()->standard()->count(),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        $notifications = $user
            ->notificationItems()
            ->standard()
            ->with('actor.profile')
            ->latest()
            ->limit(40)
            ->get();

        return response()->json([
            'authenticated' => true,
            'html' => view('themes.hnt_preview.partials.notification-shell-items', [
                'previewNotifications' => $notifications,
            ])->render(),
            'unread_count' => $user->notificationItems()->standard()->unread()->count(),
            'total_count' => $user->notificationItems()->standard()->count(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function messages(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'authenticated' => false,
                'html' => '',
                'unread_count' => 0,
                'total_count' => 0,
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        if ($request->query('variant') === 'rework') {
            $conversations = Conversation::query()
                ->forUser($user)
                ->where('type', 'private')
                ->with(['users.profile', 'users.privacySettings', 'latestMessage.user'])
                ->latest('updated_at')
                ->limit(5)
                ->get();

            return response()->json([
                'authenticated' => true,
                'html' => view('themes.rework.feed.partials.header-messages', [
                    'headerMessageConversations' => $conversations,
                    'viewer' => $user,
                    'defaultAvatar' => asset('assets/vikinger/img/default-avatar.svg'),
                ])->render(),
                'unread_count' => method_exists($user, 'unreadMessagesCount') ? $user->unreadMessagesCount() : 0,
                'total_count' => Conversation::query()
                    ->forUser($user)
                    ->where('type', 'private')
                    ->count(),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        $conversations = Conversation::query()
            ->forUser($user)
            ->where('type', 'private')
            ->with(['users.profile', 'users.privacySettings', 'latestMessage.user'])
            ->latest('updated_at')
            ->limit(40)
            ->get();

        return response()->json([
            'authenticated' => true,
            'html' => view('themes.hnt_preview.partials.message-shell-items', [
                'previewMessageConversations' => $conversations,
                'previewMessageUser' => $user,
            ])->render(),
            'unread_count' => method_exists($user, 'unreadMessagesCount') ? $user->unreadMessagesCount() : 0,
            'total_count' => Conversation::query()
                ->forUser($user)
                ->where('type', 'private')
                ->count(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function friendRequests(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'authenticated' => false,
                'html' => '',
                'count' => 0,
                'visible_count' => 0,
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        if ($request->query('variant') === 'rework') {
            $requests = Friendship::query()
                ->where('recipient_id', $user->id)
                ->where('status', Friendship::STATUS_PENDING)
                ->with('requester.profile')
                ->latest()
                ->limit(5)
                ->get();

            return response()->json([
                'authenticated' => true,
                'html' => view('themes.rework.feed.partials.header-friend-requests', [
                    'headerFriendRequests' => $requests,
                    'defaultAvatar' => asset('assets/vikinger/img/default-avatar.svg'),
                ])->render(),
                'count' => Friendship::query()
                    ->where('recipient_id', $user->id)
                    ->where('status', Friendship::STATUS_PENDING)
                    ->count(),
                'visible_count' => $requests->count(),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        $requests = Friendship::query()
            ->where('recipient_id', $user->id)
            ->where('status', Friendship::STATUS_PENDING)
            ->with('requester.profile')
            ->latest()
            ->limit(40)
            ->get();

        return response()->json([
            'authenticated' => true,
            'html' => view('themes.hnt_preview.partials.friend-request-shell-items', [
                'previewFriendRequests' => $requests,
            ])->render(),
            'count' => Friendship::query()
                ->where('recipient_id', $user->id)
                ->where('status', Friendship::STATUS_PENDING)
                ->count(),
            'visible_count' => $requests->count(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function search(Request $request): JsonResponse
    {
        $viewer = $request->user();
        $term = trim(Str::limit((string) $request->query('q', ''), 80, ''));

        if (! $viewer || mb_strlen($term) < 2) {
            return response()->json([
                'query' => $term,
                'results' => [],
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        $like = '%' . addcslashes($term, '\\%_') . '%';
        $results = collect();

        $push = static function (
            string $type,
            string $title,
            string $subtitle,
            string $url,
            ?string $avatar = null,
            string $icon = 'ph-magnifying-glass'
        ) use ($results): void {
            $results->push([
                'type' => $type,
                'title' => $title,
                'subtitle' => $subtitle,
                'url' => $url,
                'avatar' => $avatar,
                'icon' => $icon,
            ]);
        };

        $visiblePost = static function (Builder $query) use ($viewer): Builder {
            return $query->where('status', 'published')
                ->where(function (Builder $visibility) use ($viewer): void {
                    $visibility->where('visibility', '!=', 'private')
                        ->orWhere('user_id', $viewer->id);
                });
        };

        $users = User::query()
            ->with('profile')
            ->where('users.status', 'active')
            ->whereNotNull('username')
            ->where('username', '!=', '')
            ->where(function (Builder $query) use ($like): void {
                $query->where('name', 'like', $like)
                    ->orWhere('username', 'like', $like)
                    ->orWhereHas('profile', function (Builder $profileQuery) use ($like): void {
                        $profileQuery->where('headline', 'like', $like)
                            ->orWhere('discord_name', 'like', $like)
                            ->orWhere('platform', 'like', $like)
                            ->orWhere('region', 'like', $like)
                            ->orWhere('playstyle', 'like', $like);
                    });
            })
            ->orderByRaw('CASE WHEN username LIKE ? THEN 0 WHEN name LIKE ? THEN 1 ELSE 2 END', [$like, $like])
            ->orderBy('name')
            ->limit(3)
            ->get();

        $userIds = $users->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $friendIds = empty($userIds)
            ? []
            : Friendship::query()
                ->forUser($viewer)
                ->where('status', Friendship::STATUS_ACCEPTED)
                ->where(function (Builder $query) use ($userIds): void {
                    $query->whereIn('user_one_id', $userIds)->orWhereIn('user_two_id', $userIds);
                })
                ->get(['user_one_id', 'user_two_id'])
                ->map(fn (Friendship $friendship): int => (int) $friendship->user_one_id === (int) $viewer->id ? (int) $friendship->user_two_id : (int) $friendship->user_one_id)
                ->all();

        foreach ($users as $user) {
            $isFriend = in_array((int) $user->id, $friendIds, true);
            $profile = $user->profile;
            $meta = collect([$profile?->platform, $profile?->region])->filter()->implode(' · ');

            $push(
                __('ui.rework_search_result_player'),
                (string) ($user->name ?: $user->username),
                trim(($isFriend ? __('ui.header_result_friend') : __('ui.members')) . ($meta ? ' · ' . $meta : '')),
                route('profile.public', $user),
                $user->avatarUrl(),
                'ph-user'
            );
        }

        FeedPost::query()
            ->with('user.profile')
            ->tap($visiblePost)
            ->where('body', 'like', $like)
            ->latest()
            ->limit(2)
            ->get()
            ->each(fn (FeedPost $post) => $push(
                'Post',
                __('ui.rework_search_result_post_by', ['name' => $post->user?->name ?: $post->user?->username ?: 'HNT Hunter']),
                Str::limit($post->excerpt(90), 90),
                $post->permalink(),
                $post->user?->avatarUrl(),
                'ph-note-pencil'
            ));

        FeedComment::query()
            ->with(['user.profile', 'post.user.profile'])
            ->where('body', 'like', $like)
            ->whereHas('post', $visiblePost)
            ->latest()
            ->limit(2)
            ->get()
            ->each(fn (FeedComment $comment) => $push(
                __('ui.rework_search_result_comment'),
                __('ui.rework_search_result_comment_by', ['name' => $comment->user?->name ?: $comment->user?->username ?: 'HNT Hunter']),
                Str::limit(trim(strip_tags((string) $comment->body)), 90),
                $comment->post?->permalink($comment) ?: route('feed.index'),
                $comment->user?->avatarUrl(),
                'ph-chat-circle'
            ));

        Moment::query()
            ->with(['user.profile', 'media', 'cover'])
            ->published()
            ->where(function (Builder $query) use ($like): void {
                $query->where('caption', 'like', $like)
                    ->orWhere('description', 'like', $like);
            })
            ->latest('published_at')
            ->limit(2)
            ->get()
            ->each(fn (Moment $moment) => $push(
                'Moment',
                (string) ($moment->caption ?: 'HNT Moment'),
                $moment->user?->name ?: $moment->user?->username ?: 'HNT Hunter',
                route('moments.show', $moment),
                $moment->coverUrl(),
                'ph-play-circle'
            ));

        LfgPost::query()
            ->with('user.profile')
            ->where('visibility', 'public')
            ->where(function (Builder $query) use ($like): void {
                $query->where('title', 'like', $like)
                    ->orWhere('body', 'like', $like)
                    ->orWhere('platform', 'like', $like)
                    ->orWhere('playstyle', 'like', $like)
                    ->orWhere('region', 'like', $like)
                    ->orWhere('language', 'like', $like);
            })
            ->latest()
            ->limit(2)
            ->get()
            ->each(fn (LfgPost $post) => $push(
                'LFG',
                (string) $post->title,
                collect([$post->statusLabel(), ...$post->displayTags()])->filter()->take(3)->implode(' · '),
                route('lfg.show', $post),
                $post->user?->avatarUrl(),
                'ph-crosshair'
            ));

        try {
            if (Schema::hasTable('hnt_maps')) {
                HntMap::query()
                    ->where('is_active', true)
                    ->where(function (Builder $query) use ($like): void {
                        $query->where('name', 'like', $like)
                            ->orWhere('slug', 'like', $like);
                    })
                    ->orderBy('sort_order')
                    ->limit(2)
                    ->get()
                    ->each(fn (HntMap $map) => $push(
                        'Map',
                        (string) $map->name,
                        'Hunt Map',
                        route('maps.show', $map->slug),
                        null,
                        'ph-map-trifold'
                    ));
            }

            if (Schema::hasTable('hnt_map_markers')) {
                HntMapMarker::query()
                    ->with('map')
                    ->where('status', 'approved')
                    ->where(function (Builder $query) use ($like): void {
                        $query->where('label_de', 'like', $like)
                            ->orWhere('label_en', 'like', $like)
                            ->orWhere('type', 'like', $like);
                    })
                    ->orderBy('sort_order')
                    ->limit(2)
                    ->get()
                    ->filter(fn (HntMapMarker $marker): bool => $marker->map !== null)
                    ->each(fn (HntMapMarker $marker) => $push(
                        'Map',
                        (string) ($marker->label_de ?: $marker->label_en ?: ucfirst((string) $marker->type)),
                        ($marker->map?->name ?: 'Map') . ' · ' . ucfirst((string) $marker->type),
                        route('maps.show', $marker->map?->slug),
                        null,
                        'ph-map-pin'
                    ));
            }
        } catch (Throwable) {
            // Maps are optional in the header preview. Search page remains functional without them.
        }

        Cup::query()
            ->visible()
            ->where(function (Builder $query) use ($like): void {
                $query->where('title', 'like', $like)
                    ->orWhere('summary', 'like', $like)
                    ->orWhere('platform', 'like', $like)
                    ->orWhere('region', 'like', $like)
                    ->orWhere('language', 'like', $like)
                    ->orWhere('status', 'like', $like);
            })
            ->latest()
            ->limit(2)
            ->get()
            ->each(fn (Cup $cup) => $push(
                'Cup',
                (string) $cup->title,
                collect([$cup->statusLabel(), $cup->platform, $cup->region])->filter()->implode(' · '),
                route('cups.show', $cup),
                null,
                'ph-trophy'
            ));

        return response()->json([
            'query' => $term,
            'results' => $results->take(10)->values(),
            'all_url' => route('search.index', ['q' => $term, 'type' => 'all']),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

}
