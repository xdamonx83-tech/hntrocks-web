<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LiveLobbyFeedbackRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $targetPayload = $this->targetUser
            ? (new UserResource($this->targetUser))->resolve($request)
            : null;

        return [
            'id' => $this->public_id,
            'public_id' => $this->public_id,
            'lobby_id' => $this->lobby?->public_id,
            'lobby_public_id' => $this->lobby?->public_id,
            'lobby' => $this->lobby ? [
                'id' => $this->lobby->public_id,
                'public_id' => $this->lobby->public_id,
            ] : null,
            'target_user' => $this->targetUser ? [
                'id' => $this->targetUser->id,
                'username' => $this->targetUser->username,
                'display_name' => $this->targetUser->name,
                'avatar_url' => $this->targetUser->avatarUrl(),
                'crown_cosmetics' => $targetPayload['crown_cosmetics'] ?? null,
            ] : null,
            'available_at' => $this->available_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'status' => $this->status,
        ];
    }
}
