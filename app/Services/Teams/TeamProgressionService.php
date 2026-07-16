<?php

namespace App\Services\Teams;

use App\Models\Team;
use App\Models\TeamProgression;
use App\Models\TeamXpEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TeamProgressionService
{
    public function progressionFor(Team $team): TeamProgression
    {
        return TeamProgression::query()->firstOrCreate(
            ['team_id' => $team->id],
            ['level' => 1, 'xp_total' => 0]
        );
    }

    public function award(Team $team, int $amount, string $eventType, string $sourceKey, ?User $actor = null, array $metadata = []): ?TeamXpEvent
    {
        if ($amount <= 0) {
            return null;
        }

        $durableKey = "team:{$team->id}:{$sourceKey}";

        return DB::transaction(function () use ($team, $amount, $eventType, $durableKey, $actor, $metadata): ?TeamXpEvent {
            Team::query()->whereKey($team->id)->lockForUpdate()->firstOrFail();

            if (TeamXpEvent::query()->where('source_key', $durableKey)->exists()) {
                return null;
            }

            $this->progressionFor($team);
            $progression = TeamProgression::query()->where('team_id', $team->id)->lockForUpdate()->firstOrFail();
            $before = $this->breakdown((int) $progression->xp_total);
            $after = $this->breakdown((int) $progression->xp_total + $amount);

            $progression->forceFill([
                'level' => $after['level'],
                'xp_total' => (int) $progression->xp_total + $amount,
            ])->save();

            return TeamXpEvent::query()->create([
                'team_id' => $team->id,
                'user_id' => $actor?->id,
                'event_type' => $eventType,
                'source_key' => $durableKey,
                'amount' => $amount,
                'level_before' => $before['level'],
                'level_after' => $after['level'],
                'metadata' => $metadata ?: null,
            ]);
        });
    }

    public function summary(Team $team): array
    {
        $progression = $this->progressionFor($team);
        $breakdown = $this->breakdown((int) $progression->xp_total);

        if ((int) $progression->level !== $breakdown['level']) {
            $progression->forceFill(['level' => $breakdown['level']])->save();
        }

        return [
            'level' => $breakdown['level'],
            'xp_total' => (int) $progression->xp_total,
            'xp_current_level' => $breakdown['xp_current_level'],
            'xp_required_for_next_level' => $breakdown['xp_required_for_next_level'],
            'progress_percent' => $breakdown['progress_percent'],
        ];
    }

    public function breakdown(int $xpTotal): array
    {
        $remaining = max(0, $xpTotal);
        $level = 1;
        $required = $this->xpRequiredForLevel($level);

        while ($remaining >= $required) {
            $remaining -= $required;
            $level++;
            $required = $this->xpRequiredForLevel($level);
        }

        return [
            'level' => $level,
            'xp_current_level' => $remaining,
            'xp_required_for_next_level' => $required,
            'progress_percent' => $required > 0 ? round(($remaining / $required) * 100, 2) : 100.0,
        ];
    }

    private function xpRequiredForLevel(int $level): int
    {
        $base = max(1, (int) config('team_progression.level_curve.base_xp', 500));
        $growth = max(0, (int) config('team_progression.level_curve.growth_xp', 250));

        return $base + (($level - 1) * $growth);
    }
}
