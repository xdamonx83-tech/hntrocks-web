<?php

namespace App\Events;

use App\Models\Arcade\ArcadeMatch;
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
    public function broadcastWith(): array { return ['match_id' => $this->match->id, 'version' => $this->match->version, 'status' => $this->match->status->value, 'current_seat' => $this->match->current_seat, 'winner_seat' => $this->match->winner_seat, 'started_at' => $this->match->started_at?->toISOString(), 'finished_at' => $this->match->finished_at?->toISOString()]; }
}
