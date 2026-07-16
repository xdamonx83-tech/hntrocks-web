<?php

namespace App\Services\Teams;

use App\Models\Team;
use App\Models\TeamParticipation;
use App\Models\TeamParticipationEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TeamParticipationService
{
    public function ensureForTeam(Team $team): void
    {
        $now = now();
        $rows = $team->activeMembers()->pluck('user_id')->map(fn ($userId): array => [
            'team_id' => $team->id,
            'user_id' => $userId,
            'points_total' => 0,
            'badge_key' => 'member',
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($rows !== []) {
            TeamParticipation::query()->insertOrIgnore($rows);
        }
    }

    public function award(Team $team, User $user, string $eventType, string $sourceKey, ?int $points = null, array $metadata = []): ?TeamParticipationEvent
    {
        $definition = (array) config("team_progression.participation.activities.{$eventType}", []);
        $points ??= (int) ($definition['points'] ?? 0);

        if ($points <= 0 || ! $team->activeMembers()->where('user_id', $user->id)->exists()) {
            return null;
        }

        $durableKey = "team:{$team->id}:{$sourceKey}";

        return DB::transaction(function () use ($team, $user, $eventType, $durableKey, $points, $metadata, $definition): ?TeamParticipationEvent {
            Team::query()->whereKey($team->id)->lockForUpdate()->firstOrFail();

            if (TeamParticipationEvent::query()->where('team_id', $team->id)->where('source_key', $durableKey)->exists()) {
                return null;
            }

            $dailyLimit = $definition['daily_limit'] ?? null;
            if ($dailyLimit !== null) {
                $countToday = TeamParticipationEvent::query()
                    ->where('team_id', $team->id)
                    ->where('user_id', $user->id)
                    ->where('event_type', $eventType)
                    ->whereDate('created_at', now()->toDateString())
                    ->count();

                if ($countToday >= (int) $dailyLimit) {
                    return null;
                }
            }

            TeamParticipation::query()->firstOrCreate(
                ['team_id' => $team->id, 'user_id' => $user->id],
                ['points_total' => 0, 'badge_key' => 'member']
            );
            $participation = TeamParticipation::query()
                ->where('team_id', $team->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $total = (int) $participation->points_total + $points;
            $participation->forceFill([
                'points_total' => $total,
                'badge_key' => $this->badgeFor($total),
                'last_activity_at' => now(),
            ])->save();

            return TeamParticipationEvent::query()->create([
                'team_id' => $team->id,
                'user_id' => $user->id,
                'event_type' => $eventType,
                'source_key' => $durableKey,
                'points' => $points,
                'metadata' => $metadata ?: null,
            ]);
        });
    }

    public function badgeFor(int $points): string
    {
        $badge = 'member';

        foreach ((array) config('team_progression.participation.badges', []) as $key => $threshold) {
            if ($points >= (int) $threshold) {
                $badge = (string) $key;
            }
        }

        return $badge;
    }
}
