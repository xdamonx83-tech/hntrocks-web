<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CupLeaderboardEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->displayName(),
            'status' => $this->status,
            'points_total' => (int) $this->points_total,
            'bounty_tokens_total' => (int) $this->bounty_tokens_total,
            'kills_total' => (int) $this->kills_total,
            'submissions_approved_count' => (int) $this->submissions_approved_count,
            'last_submission_at' => $this->last_submission_at?->toISOString(),
            'roster_locked' => method_exists($this->resource, 'isRosterLocked') ? $this->isRosterLocked() : false,
            'roster_locked_at' => $this->roster_locked_at?->toISOString(),
            'owner' => new UserResource($this->whenLoaded('owner')),
        ];
    }
}
