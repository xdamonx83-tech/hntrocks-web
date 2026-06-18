<?php

namespace App\Http\Controllers\Socialite;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
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
        $term = trim((string) $request->query('q', ''));

        if (! $viewer || strlen($term) < 2) {
            return response()->json([
                'query' => $term,
                'results' => [],
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';

        $users = User::query()
            ->with('profile')
            ->where('users.status', 'active')
            ->whereHas('profile', function ($profileQuery) use ($viewer): void {
                $profileQuery->where(function ($visibilityQuery) use ($viewer): void {
                    $visibilityQuery
                        ->whereIn('profile_visibility', ['public', 'registered'])
                        ->orWhere('user_id', $viewer->id);
                });
            })
            ->where(function ($query) use ($like): void {
                $query
                    ->where('name', 'like', $like)
                    ->orWhere('username', 'like', $like)
                    ->orWhereHas('profile', function ($profileQuery) use ($like): void {
                        $profileQuery
                            ->where('headline', 'like', $like)
                            ->orWhere('discord_name', 'like', $like);
                    });
            })
            ->orderByRaw('CASE WHEN username LIKE ? THEN 0 WHEN name LIKE ? THEN 1 ELSE 2 END', [$like, $like])
            ->orderBy('name')
            ->limit(6)
            ->get();

        $userIds = $users->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $friendIds = Friendship::query()
            ->forUser($viewer)
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->where(function ($query) use ($userIds): void {
                $query->whereIn('user_one_id', $userIds)->orWhereIn('user_two_id', $userIds);
            })
            ->get(['user_one_id', 'user_two_id'])
            ->map(fn (Friendship $friendship): int => (int) $friendship->user_one_id === (int) $viewer->id ? (int) $friendship->user_two_id : (int) $friendship->user_one_id)
            ->all();

        $teams = Team::query()
            ->withCount(['activeMembers as members_count'])
            ->where('status', 'active')
            ->where(function ($query) use ($viewer): void {
                $query
                    ->where('visibility', 'public')
                    ->orWhere('owner_id', $viewer->id)
                    ->orWhereHas('members', function ($memberQuery) use ($viewer): void {
                        $memberQuery
                            ->where('user_id', $viewer->id)
                            ->where('status', 'active');
                    });
            })
            ->where(function ($query) use ($like): void {
                $query
                    ->where('name', 'like', $like)
                    ->orWhere('tagline', 'like', $like)
                    ->orWhere('description', 'like', $like);
            })
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$like])
            ->orderBy('name')
            ->limit(6)
            ->get();

        $results = collect();

        foreach ($users as $user) {
            $isFriend = in_array((int) $user->id, $friendIds, true);

            $results->push([
                'type' => 'user',
                'title' => $user->name ?: $user->username,
                'subtitle' => $isFriend ? __('ui.header_result_friend') : __('ui.members'),
                'url' => route('profile.public', $user),
                'avatar' => $user->avatarUrl(),
            ]);
        }

        foreach ($teams as $team) {
            $results->push([
                'type' => 'team',
                'title' => $team->name,
                'subtitle' => trans_choice('ui.teams_members_count', (int) $team->members_count, ['count' => (int) $team->members_count]),
                'url' => route('teams.show', $team),
                'avatar' => $team->avatarUrl(),
            ]);
        }

        return response()->json([
            'query' => $term,
            'results' => $results->take(10)->values(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
