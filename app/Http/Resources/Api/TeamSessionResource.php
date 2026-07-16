<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $ownResponse = $this->relationLoaded('responses') ? $this->responses->firstWhere('user_id', $request->user()?->id) : null;

        return [
            'id' => $this->id,
            'status' => $this->status,
            'title' => $this->title,
            'description' => $this->description,
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'timezone' => $this->timezone,
            'platform' => $this->platform,
            'region' => $this->region,
            'game_mode' => $this->game_mode,
            'max_participants' => $this->max_participants !== null ? (int) $this->max_participants : null,
            'voice_required' => (bool) $this->voice_required,
            'creator' => $this->creator ? $this->userSummary($this->creator) : null,
            'responses' => $this->whenLoaded('responses', fn () => $this->responses->map(fn ($response): array => [
                'user' => $this->userSummary($response->user),
                'response' => $response->response,
                'attendance_confirmed' => (bool) $response->attendance_confirmed,
                'responded_at' => $response->updated_at?->toISOString(),
            ])->values()->all()),
            'viewer' => [
                'response' => $ownResponse?->response,
                'attendance_confirmed' => (bool) $ownResponse?->attendance_confirmed,
            ],
            'completed_at' => $this->completed_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function userSummary($user): ?array
    {
        return $user ? ['id' => $user->id, 'username' => $user->username, 'display_name' => $user->name, 'avatar_url' => $user->avatarUrl()] : null;
    }
}
