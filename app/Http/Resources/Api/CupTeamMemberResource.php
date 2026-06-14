<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CupTeamMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'role' => (string) $this->role,
            'role_label' => $this->roleLabel(),
            'status' => (string) $this->status,
            'joined_at' => $this->joined_at?->toISOString(),
            'user' => $this->relationLoaded('user') && $this->user
                ? new UserResource($this->user)
                : null,
        ];
    }
}
