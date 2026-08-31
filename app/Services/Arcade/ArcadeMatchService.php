<?php

namespace App\Services\Arcade;

use App\Enums\Arcade\ArcadeMatchPlayerStatus;
use App\Enums\Arcade\ArcadeMatchStatus;
use App\Events\ArcadeMatchUpdated;
use App\Models\Arcade\ArcadeMatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ArcadeMatchService
{
    public function __construct(
        private readonly ArcadeGameEngineRegistry $engines,
        private readonly ArcadeNotificationService $notifications,
    ) {
    }

    public function ready(ArcadeMatch $match, User $actor): ArcadeMatch
    {
        $fresh = DB::transaction(function () use ($match, $actor): ArcadeMatch {
            $match = ArcadeMatch::query()->with('game')->lockForUpdate()->findOrFail($match->id);
            if ($match->status !== ArcadeMatchStatus::WaitingReady) {
                throw new ConflictHttpException('This match is not waiting for players.');
            }

            $player = $match->players()->where('user_id', $actor->id)->lockForUpdate()->first();
            if (! $player) {
                throw new AccessDeniedHttpException;
            }
            if ($player->status === ArcadeMatchPlayerStatus::Ready) {
                return $match->fresh(['game', 'players.user']);
            }

            $player->update([
                'status' => ArcadeMatchPlayerStatus::Ready,
                'ready_at' => now(),
            ]);
            $match->version++;
            $required = (int) $match->game->min_players;

            if ($match->players()->where('status', ArcadeMatchPlayerStatus::Ready->value)->count() >= $required) {
                $match->fill([
                    'state' => $this->engines->resolve($match->game)->initialize(),
                    'status' => ArcadeMatchStatus::Active,
                    'started_at' => now(),
                    'current_seat' => 1,
                ]);
            }

            $match->save();
            $fresh = $match->fresh(['game', 'players.user']);
            DB::afterCommit(fn () => ArcadeMatchUpdated::dispatch($fresh));

            return $fresh;
        });

        $this->notifications->opponentReady($fresh, $actor);

        return $fresh;
    }
}
