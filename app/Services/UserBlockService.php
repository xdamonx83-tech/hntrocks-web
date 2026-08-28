<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Database\Eloquent\Builder;

class UserBlockService
{
    /**
     * @var array<int, array<int, int>>
     */
    private array $blockedIds = [];

    /**
     * @return array<int, int>
     */
    public function blockedUserIds(User $viewer): array
    {
        if ($viewer->isAdmin()) {
            return [];
        }

        $viewerId = (int) $viewer->id;

        if (array_key_exists($viewerId, $this->blockedIds)) {
            return $this->blockedIds[$viewerId];
        }

        return $this->blockedIds[$viewerId] = UserBlock::query()
            ->where('user_id', $viewerId)
            ->pluck('blocked_user_id')
            ->merge(
                UserBlock::query()
                    ->where('blocked_user_id', $viewerId)
                    ->pluck('user_id')
            )
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function areBlocked(User $first, User $second): bool
    {
        if ((int) $first->id === (int) $second->id) {
            return false;
        }

        return in_array((int) $second->id, $this->blockedUserIds($first), true);
    }

    public function applyToUserQuery(Builder $query, User $viewer): Builder
    {
        $blockedIds = $this->blockedUserIds($viewer);

        return $blockedIds === []
            ? $query
            : $query->whereNotIn($query->getModel()->qualifyColumn('id'), $blockedIds);
    }

    public function applyToConversationQuery(Builder $query, User $viewer): Builder
    {
        $blockedIds = $this->blockedUserIds($viewer);

        if ($blockedIds === []) {
            return $query;
        }

        return $query->whereDoesntHave('users', function (Builder $users) use ($blockedIds): void {
            $users->whereIn('users.id', $blockedIds);
        });
    }

    public function applyToFriendshipQuery(Builder $query, User $viewer): Builder
    {
        $blockedIds = $this->blockedUserIds($viewer);

        if ($blockedIds === []) {
            return $query;
        }

        return $query
            ->whereNotIn('user_one_id', $blockedIds)
            ->whereNotIn('user_two_id', $blockedIds);
    }

    public function forget(User|int $viewer): void
    {
        unset($this->blockedIds[(int) ($viewer instanceof User ? $viewer->id : $viewer)]);
    }
}
