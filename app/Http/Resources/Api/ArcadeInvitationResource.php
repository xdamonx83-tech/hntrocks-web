<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArcadeInvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['game', 'inviter', 'invitee']);
        $public = fn ($user) => $user ? ['id' => $user->id, 'username' => $user->username, 'name' => $user->name] : null;
        return ['id' => $this->id, 'game_key' => $this->game->key, 'mode' => $this->mode->value, 'status' => $this->status->value, 'inviter' => $public($this->inviter), 'invitee' => $public($this->invitee), 'match_id' => $this->match_id, 'expires_at' => $this->expires_at->toISOString(), 'created_at' => $this->created_at->toISOString()];
    }
}
