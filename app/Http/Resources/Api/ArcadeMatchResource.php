<?php

namespace App\Http\Resources\Api;

use App\Enums\Arcade\ArcadeMatchStatus;
use App\Services\Arcade\ArcadeGameEngineRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArcadeMatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['game', 'players.user']);
        $mine = $this->players->firstWhere('user_id', $request->user()->id);
        $rawState = (array) ($this->state ?? []);
        $state = $this->status === ArcadeMatchStatus::Cancelled && $this->started_at === null
            ? $rawState
            : app(ArcadeGameEngineRegistry::class)
                ->resolve($this->game)
                ->publicState($rawState, $mine?->seat);

        return ['id' => $this->id, 'game_key' => $this->game->key, 'mode' => $this->mode->value, 'status' => $this->status->value, 'version' => $this->version, 'state' => $state, 'current_seat' => $this->current_seat, 'winner_seat' => $this->winner_seat, 'started_at' => $this->started_at?->toISOString(), 'finished_at' => $this->finished_at?->toISOString(), 'players' => $this->players->map(fn ($player) => ['user' => $player->user ? ['id' => $player->user->id, 'username' => $player->user->username, 'name' => $player->user->name, 'avatar_url' => method_exists($player->user, 'avatarUrl') ? $player->user->avatarUrl() : null] : null, 'seat' => $player->seat, 'status' => $player->status->value, 'ready' => $player->ready_at !== null, 'result' => $player->result?->value]), 'my_seat' => $mine?->seat, 'can_ready' => $mine && $this->status === ArcadeMatchStatus::WaitingReady && $mine->ready_at === null, 'can_move' => $mine && $this->status === ArcadeMatchStatus::Active && (int) $this->current_seat === (int) $mine->seat];
    }
}
