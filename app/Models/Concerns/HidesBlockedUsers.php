<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Services\UserBlockService;
use Illuminate\Database\Eloquent\Builder;

trait HidesBlockedUsers
{
    public static function bootHidesBlockedUsers(): void
    {
        static::addGlobalScope('blocked_users', function (Builder $builder): void {
            if (! app()->bound('request')) {
                return;
            }

            $viewer = request()->user();

            if (! $viewer instanceof User || $viewer->isAdmin()) {
                return;
            }

            $blockedIds = app(UserBlockService::class)->blockedUserIds($viewer);

            if ($blockedIds === []) {
                return;
            }

            $column = $builder->getModel()->qualifyColumn(
                $builder->getModel()->blockedAuthorColumn()
            );

            $builder->where(function (Builder $visibility) use ($column, $blockedIds): void {
                $visibility
                    ->whereNull($column)
                    ->orWhereNotIn($column, $blockedIds);
            });
        });
    }

    protected function blockedAuthorColumn(): string
    {
        return 'user_id';
    }
}
