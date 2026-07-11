<?php

namespace App\Http\Middleware;

use App\Models\Conversation;
use App\Models\FeedPost;
use App\Models\Friendship;
use App\Models\User;
use App\Support\HntTheme;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PreviewDashboardHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('admin.theme-preview.shell') || ! $request->boolean('dashboard_header')) {
            return $next($request);
        }

        $user = $request->user();

        abort_unless(
            $user?->isAdmin() && HntTheme::previewActive($user),
            403
        );

        return response()->json([
            'header' => $this->payload($user),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function payload(User $user): array
    {
        $user->loadMissing(['profile', 'crownWallet']);

        $friendCount = (int) $this->safe(
            fn (): int => Friendship::query()
                ->forUser($user)
                ->where('status', Friendship::STATUS_ACCEPTED)
                ->count(),
            0
        );

        $notifications = $this->safe(
            fn () => $user->notificationItems()
                ->standard()
                ->with('actor.profile')
                ->orderByRaw('read_at is not null')
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn ($notification): array => [
                    'id' => (int) $notification->id,
                    'title' => $notification->displayTitle(),
                    'body' => $notification->displayBody(),
                    'avatar' => $notification->displayActorAvatarUrl(),
                    'actor' => $notification->displayActorName(),
                    'unread' => $notification->isUnread(),
                    'time' => $notification->created_at?->diffForHumans(null, true, true, 1) ?: '—',
                    'read_url' => route('notifications.read', $notification),
                    'action_url' => $notification->actionUrl(),
                ])
                ->values()
                ->all(),
            []
        );

        $conversations = $this->safe(
            fn () => Conversation::query()
                ->forUser($user)
                ->where('type', 'private')
                ->with(['users.profile', 'users.privacySettings', 'latestMessage.user'])
                ->latest('updated_at')
                ->limit(6)
                ->get()
                ->map(function (Conversation $conversation) use ($user): array {
                    $other = $conversation->otherParticipant($user);
                    $latestAt = $conversation->latestMessage?->created_at ?? $conversation->updated_at;
                    $latestBody = trim((string) $conversation->latestMessage?->body);

                    return [
                        'id' => (int) $conversation->id,
                        'title' => $conversation->displayTitleFor($user),
                        'preview' => $latestBody !== ''
                            ? Str::limit($latestBody, 76)
                            : __('ui.message_no_messages_yet'),
                        'avatar' => $other?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'),
                        'handle' => $other?->username ? '@'.$other->username : __('ui.private_conversation'),
                        'unread' => (int) $conversation->unreadCountFor($user),
                        'time' => $latestAt?->diffForHumans(null, true, true, 1) ?: '—',
                        'url' => route('messages.show', $conversation),
                    ];
                })
                ->values()
                ->all(),
            []
        );

        $friendRequests = $this->safe(
            fn () => Friendship::query()
                ->where('recipient_id', $user->id)
                ->where('status', Friendship::STATUS_PENDING)
                ->with('requester.profile')
                ->latest()
                ->limit(6)
                ->get()
                ->map(function (Friendship $friendship): array {
                    $requester = $friendship->requester;
                    $name = $requester?->name ?: ($requester?->username ?: 'HNT Hunter');

                    return [
                        'id' => (int) $friendship->id,
                        'name' => $name,
                        'handle' => $requester?->username ? '@'.$requester->username : __('ui.members'),
                        'avatar' => $requester?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'),
                        'profile_url' => $requester ? route('profile.public', $requester) : route('members.index'),
                        'time' => $friendship->created_at?->diffForHumans(null, true, true, 1) ?: '—',
                        'accept_url' => route('friends.accept', $friendship),
                        'decline_url' => route('friends.decline', $friendship),
                    ];
                })
                ->values()
                ->all(),
            []
        );

        $notificationUnread = (int) $this->safe(
            fn (): int => $user->notificationItems()->standard()->unread()->count(),
            0
        );
        $messageUnread = (int) $this->safe(fn (): int => $user->unreadMessagesCount(), 0);
        $friendRequestCount = (int) $this->safe(
            fn (): int => Friendship::query()
                ->where('recipient_id', $user->id)
                ->where('status', Friendship::STATUS_PENDING)
                ->count(),
            0
        );

        $locale = app()->getLocale() === 'en' ? 'en' : 'de';
        $nextLocale = $locale === 'de' ? 'en' : 'de';

        return [
            'profile' => [
                'name' => $user->name ?: ($user->username ?: 'HNT Hunter'),
                'handle' => $user->username ? '@'.$user->username : '@hunter',
                'avatar' => $user->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'),
                'level' => max(1, (int) ($user->level ?: 1)),
                'rocks' => (int) ($user->crownWallet?->balance ?? 0),
                'friends' => $friendCount,
                'posts' => FeedPost::query()
                    ->where('user_id', $user->id)
                    ->where('status', 'published')
                    ->count(),
            ],
            'counts' => [
                'notifications' => $notificationUnread,
                'messages' => $messageUnread,
                'friends' => $friendRequestCount,
            ],
            'notifications' => $notifications,
            'messages' => $conversations,
            'friend_requests' => $friendRequests,
            'links' => [
                'notifications' => route('notifications.index'),
                'notifications_read_all' => route('notifications.read-all'),
                'messages' => route('messages.index'),
                'friends' => route('profile.friends'),
                'profile' => route('profile.show'),
                'profile_edit' => route('profile.edit'),
                'logout' => route('logout'),
                'settings' => route('account.settings.edit'),
                'privacy' => route('settings.privacy.edit'),
                'notification_settings' => route('account.settings.edit'),
                'language' => route('locale.switch', ['locale' => $nextLocale]),
                'language_label' => strtoupper($locale),
                'navigation' => [
                    'Mitglieder' => route('members.index'),
                    'Freunde' => route('profile.friends'),
                    'Hashtags' => route('feed.index').'#community-hashtags',
                    'LFG finden' => route('lfg.index'),
                    'LFG erstellen' => route('lfg.create'),
                    'Moments' => route('moments.index'),
                    'Aktive Cups' => route('cups.index'),
                    'Meine Cup-Teams' => route('cups.index'),
                    'Einreichungen' => route('cups.index'),
                    'Hall of Fame' => route('hall-of-fame.index'),
                    'Teams' => route('teams.index'),
                    'Wochenaufträge' => route('contracts.index'),
                    'Quests' => route('gamification.index'),
                    'Loadout Challenges' => route('loadout-challenges.index'),
                    'Badges' => route('profile.badges'),
                    'Shop & Inventar' => route('crowns.shop'),
                    'Aktivitätsverlauf' => route('crowns.history'),
                ],
                'profile_menu' => [
                    'Mein Profil' => route('profile.show'),
                    'Inventar' => route('crowns.inventory'),
                    'Fortschritt' => route('gamification.index'),
                ],
                'settings_menu' => [
                    'Allgemein' => route('account.settings.edit'),
                    'Privatsphäre' => route('settings.privacy.edit'),
                    'Benachrichtigungen' => route('account.settings.edit'),
                    'Sprache' => route('locale.switch', ['locale' => $nextLocale]),
                ],
            ],
        ];
    }

    private function safe(callable $callback, mixed $fallback): mixed
    {
        try {
            return $callback();
        } catch (Throwable $exception) {
            report($exception);

            return $fallback;
        }
    }
}
