<?php

namespace App\Services\Arcade;

use App\Enums\Arcade\ArcadeMatchPlayerResult;
use App\Enums\Arcade\ArcadeMatchStatus;
use App\Events\ArcadeMatchUpdated;
use App\Models\Arcade\ArcadeMatch;
use App\Models\Arcade\ArcadeMatchMove;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ArcadeMoveService
{
    public function __construct(
        private readonly ArcadeGameEngineRegistry $engines,
        private readonly ArcadeNotificationService $notifications,
    ) {
    }

    public function move(ArcadeMatch $match, User $actor, string $clientMoveId, array $payload): ArcadeMatch
    {
        $fresh = DB::transaction(function () use ($match, $actor, $clientMoveId, $payload): ArcadeMatch {
            $match = ArcadeMatch::query()->with('game')->lockForUpdate()->findOrFail($match->id);
            $player = $match->players()->where('user_id', $actor->id)->first();
            if (! $player) {
                throw new AccessDeniedHttpException;
            }

            $engine = $this->engines->resolve($match->game);
            $moveType = $engine->moveType();
            $existing = ArcadeMatchMove::query()
                ->where('match_id', $match->id)
                ->where('client_move_id', $clientMoveId)
                ->first();
            if ($existing) {
                if ($existing->match_player_id !== $player->id
                    || $existing->move_type !== $moveType
                    || Arr::sortRecursive($existing->payload) !== Arr::sortRecursive($payload)) {
                    throw new ConflictHttpException('client_move_id was already used for a different move.');
                }

                return $match->fresh(['game', 'players.user']);
            }

            if ($match->status !== ArcadeMatchStatus::Active) {
                throw new ConflictHttpException('This match is not active.');
            }
            if ((int) $match->current_seat !== (int) $player->seat) {
                throw new ConflictHttpException('It is not your turn.');
            }

            $before = (int) $match->version;
            $state = $engine->apply((array) $match->state, (int) $player->seat, $payload);
            $finished = ($state['winner_seat'] ?? null) !== null || ($state['draw'] ?? false) === true;
            $match->fill([
                'state' => $state,
                'version' => $before + 1,
                'current_seat' => $finished ? null : $state['turn_seat'],
                'winner_seat' => $state['winner_seat'] ?? null,
                'status' => $finished ? ArcadeMatchStatus::Finished : ArcadeMatchStatus::Active,
                'finished_at' => $finished ? now() : null,
            ])->save();

            $sequence = ((int) $match->moves()->max('sequence')) + 1;
            $match->moves()->create([
                'match_player_id' => $player->id,
                'sequence' => $sequence,
                'client_move_id' => $clientMoveId,
                'move_type' => $moveType,
                'payload' => $payload,
                'state_version_before' => $before,
                'state_version_after' => $before + 1,
            ]);

            if ($finished) {
                $match->players()->get()->each(function ($participant) use ($state): void {
                    $result = ($state['draw'] ?? false)
                        ? ArcadeMatchPlayerResult::Draw
                        : ((int) $participant->seat === (int) $state['winner_seat']
                            ? ArcadeMatchPlayerResult::Win
                            : ArcadeMatchPlayerResult::Loss);
                    $participant->update(['result' => $result]);
                });
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
