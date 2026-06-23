<?php

namespace App\Services;

use App\Models\LiveLobby;
use App\Models\LiveLobbyFeedbackRequest;

class LiveLobbyFeedbackService
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function createRequestsForLobby(LiveLobby $lobby): int
    {
        $participantIds = $lobby->members()
            ->pluck('user_id')
            ->push($lobby->creator_id)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($participantIds->count() < 2) {
            return 0;
        }

        $availableAt = $lobby->created_at->copy()->addMinutes(60);
        $expiresAt = $availableAt->copy()->addHours(48);
        $created = 0;

        foreach ($participantIds as $reviewerId) {
            foreach ($participantIds as $targetUserId) {
                if ($reviewerId === $targetUserId) {
                    continue;
                }

                $request = LiveLobbyFeedbackRequest::query()->firstOrCreate(
                    [
                        'live_lobby_id' => $lobby->id,
                        'reviewer_id' => $reviewerId,
                        'target_user_id' => $targetUserId,
                    ],
                    [
                        'status' => 'pending',
                        'available_at' => $availableAt,
                        'expires_at' => $expiresAt,
                    ],
                );

                $created += $request->wasRecentlyCreated ? 1 : 0;
            }
        }

        return $created;
    }

    public function expirePendingRequests(): int
    {
        return LiveLobbyFeedbackRequest::query()
            ->where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired', 'updated_at' => now()]);
    }

    public function sendAvailableNotifications(): int
    {
        $sent = 0;

        LiveLobbyFeedbackRequest::query()
            ->with(['reviewer', 'lobby'])
            ->where('status', 'pending')
            ->whereNull('notified_at')
            ->whereNotNull('available_at')
            ->where('available_at', '<=', now())
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->orderBy('id')
            ->chunkById(100, function ($requests) use (&$sent): void {
                foreach ($requests as $request) {
                    if ((int) $request->reviewer_id === (int) $request->target_user_id) {
                        continue;
                    }

                    $notification = $this->notifications->send(
                        $request->reviewer,
                        null,
                        'live_lobby_feedback',
                        'Wie war die Jagd?',
                        'Gib kurzes Feedback zu deiner letzten Ready Lobby.',
                        '/ready-lobbies/'.$request->lobby->public_id.'/feedback',
                    );

                    if ($notification) {
                        $request->forceFill(['notified_at' => now()])->save();
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    public function queueEligibleLobbies(): array
    {
        $created = 0;

        LiveLobby::query()
            ->where(function ($query): void {
                $query->whereIn('status', ['full', 'closed', 'expired'])
                    ->orWhere('created_at', '<=', now()->subMinutes(60));
            })
            ->orderBy('id')
            ->chunkById(100, function ($lobbies) use (&$created): void {
                foreach ($lobbies as $lobby) {
                    $created += $this->createRequestsForLobby($lobby);
                }
            });

        return [
            'created_requests' => $created,
            'expired_requests' => $this->expirePendingRequests(),
            'notifications_sent' => $this->sendAvailableNotifications(),
        ];
    }
}
