<?php

namespace App\Events;

use App\Enums\Arcade\ArcadeMatchStatus;
use App\Models\Arcade\ArcadeMatch;
use App\Services\Arcade\ArcadeGameEngineRegistry;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ArcadeMatchUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public function __construct(public ArcadeMatch $match) {}
    public function broadcastOn(): array { return [new PrivateChannel('arcade.match.'.$this->match->id)]; }
    public function broadcastAs(): string { return 'arcade.match.updated'; }
    public function broadcastWith(): array
    {
        $this->match->loadMissing('game');
        $rawState = (array) ($this->match->state ?? []);

        // A match cancelled before it ever started has no gameplay state that
        // needs engine-specific sanitising. Keeping this path engine-agnostic
        // also lets generic/future arcade games be cancelled safely while they
        // are still waiting for players.
        $state = $this->match->status === ArcadeMatchStatus::Cancelled && $this->match->started_at === null
            ? $rawState
            : app(ArcadeGameEngineRegistry::class)
                ->resolve($this->match->game)
                ->publicState($rawState);

        return ['match_id' => $this->match->id, 'version' => $this->match->version, 'status' => $this->match->status->value, 'state' => $state, 'current_seat' => $this->match->current_seat, 'winner_seat' => $this->match->winner_seat, 'started_at' => $this->match->started_at?->toISOString(), 'finished_at' => $this->match->finished_at?->toISOString()];
    }
}
