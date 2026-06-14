<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CupTeamFinderPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'platform' => $this->platform,
            'status' => (string) $this->status,
            'message' => $this->message,
            'closed_at' => $this->closed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'user' => $this->relationLoaded('user') && $this->user
                ? new UserResource($this->user)
                : null,
        ];
    }
}
