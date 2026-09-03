<?php

namespace App\Services\Arcade;

use App\Enums\Arcade\ArcadeMatchPlayerResult;
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
        private readonly ArcadeMatchResultProcessor $results,
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

    public function forfeit(ArcadeMatch $match, User $actor): ArcadeMatch
    {
        $fresh = DB::transaction(function () use ($match, $actor): ArcadeMatch {
            $match = ArcadeMatch::query()->with('game')->lockForUpdate()->findOrFail($match->id);
            $players = $match->players()->with('user')->lockForUpdate()->get();
            $actorPlayer = $players->first(fn ($player) => (int) $player->user_id === (int) $actor->id);

            if (! $actorPlayer) {
                throw new AccessDeniedHttpException;
            }

            if (in_array($match->status, [ArcadeMatchStatus::Finished, ArcadeMatchStatus::Cancelled, ArcadeMatchStatus::Expired], true)) {
                return $match->fresh(['game', 'players.user']);
            }

            if ($match->status === ArcadeMatchStatus::WaitingReady) {
                $state = (array) $match->state;
                $state['termination'] = [
                    'type' => 'cancelled',
                    'by_seat' => (int) $actorPlayer->seat,
                ];

                $match->fill([
                    'state' => $state,
                    'status' => ArcadeMatchStatus::Cancelled,
                    'current_seat' => null,
                    'winner_seat' => null,
                    'finished_at' => now(),
                    'version' => (int) $match->version + 1,
                ])->save();

                $players->each(fn ($participant) => $participant->update([
                    'result' => ArcadeMatchPlayerResult::Cancelled,
                ]));
            } elseif ($match->status === ArcadeMatchStatus::Active) {
                $opponent = $players->first(fn ($participant) => (int) $participant->seat !== (int) $actorPlayer->seat);
                if (! $opponent) {
                    throw new ConflictHttpException('This match has no opponent to receive the win.');
                }

                $winnerSeat = (int) $opponent->seat;
                $state = (array) $match->state;
                $state['winner_seat'] = $winnerSeat;
                $state['draw'] = false;
                $state['termination'] = [
                    'type' => 'forfeit',
                    'forfeited_seat' => (int) $actorPlayer->seat,
                    'winner_seat' => $winnerSeat,
                ];

                $match->fill([
                    'state' => $state,
                    'status' => ArcadeMatchStatus::Finished,
                    'current_seat' => null,
                    'winner_seat' => $winnerSeat,
                    'finished_at' => now(),
                    'version' => (int) $match->version + 1,
                ])->save();

                $players->each(function ($participant) use ($winnerSeat): void {
                    $participant->update([
                        'result' => (int) $participant->seat === $winnerSeat
                            ? ArcadeMatchPlayerResult::Win
                            : ArcadeMatchPlayerResult::Loss,
                    ]);
                });

                $this->results->process($match);
            } else {
                throw new ConflictHttpException('This match cannot be forfeited.');
            }

            $fresh = $match->fresh(['game', 'players.user']);
            DB::afterCommit(fn () => ArcadeMatchUpdated::dispatch($fresh));

            return $fresh;
        });

        if ($fresh->status === ArcadeMatchStatus::Finished) {
            $this->notifications->matchFinished($fresh);
        }

        return $fresh;
    }
}
