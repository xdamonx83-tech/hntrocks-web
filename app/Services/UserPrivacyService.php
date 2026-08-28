<?php

namespace App\Services;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserPrivacyService
{
    public function canMessage(User $sender, User $recipient): bool
    {
        $recipient->loadMissing('privacySettings');

        return match ((string) ($recipient->privacySettings?->allow_messages_from ?? 'registered')) {
            'everyone', 'registered' => true,
            'following' => Friendship::query()
                ->between($sender, $recipient)
                ->where('status', Friendship::STATUS_ACCEPTED)
                ->exists(),
            'nobody' => false,
            default => false,
        };
    }

    public function canViewActivity(?User $viewer, User $profileUser): bool
    {
        if ($viewer?->is($profileUser)) {
            return true;
        }

        $profileUser->loadMissing('privacySettings');

        return $profileUser->privacySettings?->show_activity_feed !== false;
    }

    public function canViewGamification(?User $viewer, User $profileUser): bool
    {
        if ($viewer?->is($profileUser)) {
            return true;
        }

        $profileUser->loadMissing('privacySettings');

        return $profileUser->privacySettings?->show_gamification !== false;
    }

    public function applyToMessageRecipientQuery(Builder $query, User $sender): Builder
    {
        $acceptedFriendIds = Friendship::query()
            ->forUser($sender)
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->get(['user_one_id', 'user_two_id'])
            ->map(fn (Friendship $friendship): int => (int) $friendship->user_one_id === (int) $sender->id
                ? (int) $friendship->user_two_id
                : (int) $friendship->user_one_id)
            ->values()
            ->all();

        return $query->where(function (Builder $privacyQuery) use ($acceptedFriendIds): void {
            $privacyQuery
                ->whereDoesntHave('privacySettings')
                ->orWhereHas('privacySettings', fn (Builder $settingsQuery) => $settingsQuery
                    ->whereIn('allow_messages_from', ['everyone', 'registered']));

            if ($acceptedFriendIds !== []) {
                $privacyQuery->orWhere(function (Builder $contactQuery) use ($acceptedFriendIds): void {
                    $contactQuery
                        ->whereIn('users.id', $acceptedFriendIds)
                        ->whereHas('privacySettings', fn (Builder $settingsQuery) => $settingsQuery
                            ->where('allow_messages_from', 'following'));
                });
            }
        });
    }
}
