<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LiveLobbyFeedbackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_id' => $this->request?->public_id,
            'lobby_id' => $this->lobby?->public_id,
            'target_user_id' => $this->target_user_id,
            'positive_tags' => $this->positive_tags ?? [],
            'private_flags' => $this->private_flags ?? [],
            'comment' => $this->comment,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
