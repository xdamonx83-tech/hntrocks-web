<?php

namespace App\Services\Notifications;

use App\Models\UserNotification;

class PushPayloadResolver
{
    public function forNotification(UserNotification $notification): array
    {
        return $this->forRaw(
            (string) $notification->type,
            $notification->actionUrl(),
            (string) $notification->id,
            (string) ($notification->actor_id ?? '')
        );
    }

    public function forRaw(string $type, ?string $actionUrl, string $notificationId = '', string $actorId = ''): array
    {
        $payload = [
            'type' => $type,
            'target' => $this->fallbackTarget($type),
            'notification_id' => $notificationId,
            'action_url' => (string) $actionUrl,
            'actor_id' => $actorId,
        ];

        return array_merge($payload, $this->targetData($type, $actionUrl));
    }

    private function targetData(string $type, ?string $actionUrl): array
    {
        $type = strtolower(trim($type));
        $path = $this->pathFor($actionUrl);

        if ($feed = $this->feedTarget($type, $path)) {
            return $feed;
        }

        if ($moment = $this->momentTarget($path)) {
            return $moment;
        }

        if ($liveLobby = $this->liveLobbyTarget($path)) {
            return $liveLobby;
        }

        if ($this->isFriendRequestType($type) || $path === '/friend-requests') {
            return ['target' => 'friend_request'];
        }

        if ($cup = $this->cupTarget($path)) {
            return $cup;
        }

        if ($profile = $this->profileTarget($path)) {
            return $profile;
        }

        return [];
    }

    private function feedTarget(string $type, string $path): ?array
    {
        if (! preg_match('#^/feed/posts/([0-9]+)(?:/comments)?/?$#', $path, $matches)) {
            return null;
        }

        $postId = (string) $matches[1];

        if (str_contains($type, 'comment')) {
            return [
                'target' => 'comment',
                'post_id' => $postId,
            ];
        }

        return [
            'target' => 'post',
            'post_id' => $postId,
        ];
    }

    private function momentTarget(string $path): ?array
    {
        if (! preg_match('#^/moments(?:/r)?/([0-9]+)/?$#', $path, $matches)) {
            return null;
        }

        return [
            'target' => 'moment',
            'moment_id' => (string) $matches[1],
        ];
    }

    private function liveLobbyTarget(string $path): ?array
    {
        if (! preg_match('#^/ready-lobbies/([0-9a-f-]+)(/feedback)?/?$#i', $path, $matches)) {
            return null;
        }

        return [
            'target' => isset($matches[2]) && $matches[2] !== '' ? 'live_lobby_feedback' : 'live_lobby',
            'live_lobby_id' => (string) $matches[1],
            'ready_lobby_id' => (string) $matches[1],
            ...(isset($matches[2]) && $matches[2] !== '' ? ['feedback_request_id' => ''] : []),
        ];
    }

    private function cupTarget(string $path): ?array
    {
        if (! preg_match('#^/cups/([^/?]+)/?.*$#', $path, $matches)) {
            return null;
        }

        $slug = rawurldecode((string) $matches[1]);

        if ($slug === '' || in_array($slug, ['create'], true)) {
            return null;
        }

        return [
            'target' => 'cup',
            'cup_slug' => $slug,
            'slug' => $slug,
        ];
    }

    private function profileTarget(string $path): ?array
    {
        if (! preg_match('#^/(?:profile|u)/([^/?]+)/?.*$#', $path, $matches)) {
            return null;
        }

        $username = rawurldecode((string) $matches[1]);

        if ($username === '' || in_array($username, ['edit', 'media'], true)) {
            return null;
        }

        return [
            'target' => 'profile',
            'username' => $username,
        ];
    }

    private function fallbackTarget(string $type): string
    {
        return $this->isFriendRequestType($type)
            ? 'friend_request'
            : 'notification';
    }

    private function isFriendRequestType(string $type): bool
    {
        return str_starts_with(strtolower(trim($type)), 'friend_request');
    }

    private function pathFor(?string $actionUrl): string
    {
        $actionUrl = trim((string) $actionUrl);

        if ($actionUrl === '') {
            return '';
        }

        $parts = parse_url($actionUrl);

        if (! is_array($parts)) {
            return '';
        }

        $path = (string) ($parts['path'] ?? '');

        if (($parts['scheme'] ?? null) === 'hntrocks' && isset($parts['host'])) {
            $path = '/' . trim((string) $parts['host'] . '/' . ltrim($path, '/'), '/');
        }

        if ($path === '') {
            return '';
        }

        return '/' . trim($path, '/');
    }
}
