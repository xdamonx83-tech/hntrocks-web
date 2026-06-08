<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LfgPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'platform' => $this->platform,
            'playstyle' => $this->playstyle,
            'region' => $this->region,
            'language' => $this->language,
            'preferred_time' => $this->preferred_time,
            'experience_level' => $this->experience_level,
            'voice_required' => (bool) $this->voice_required,
            'slots_total' => (int) $this->slots_total,
            'slots_filled' => (int) $this->slots_filled,
            'slots_open' => $this->slotsOpen(),
            'status' => $this->status,
            'visibility' => $this->visibility,
            'author' => new UserResource($this->whenLoaded('user')),
            'viewer' => $this->viewerPayload($request),
            'pending_applications' => $this->pendingApplicationsPayload($request),
            'created_at' => $this->created_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
        ];
    }

    private function viewerPayload(Request $request): array
    {
        $user = $request->user();
        if (! $user) {
            return [
                'is_owner' => false,
                'can_manage' => false,
                'can_apply' => false,
                'application' => null,
            ];
        }

        $application = $this->applicationFor($user);
        $canManage = $this->canManage($user);

        return [
            'is_owner' => $this->isOwner($user),
            'can_manage' => $canManage,
            'can_apply' => $this->canApply($user),
            'application' => $application ? [
                'id' => $application->id,
                'status' => $application->status,
                'message' => $application->message,
                'created_at' => $application->created_at?->toISOString(),
                'decided_at' => $application->decided_at?->toISOString(),
            ] : null,
        ];
    }

    private function pendingApplicationsPayload(Request $request): array
    {
        $user = $request->user();
        if (! $user || ! $this->canManage($user) || ! $this->relationLoaded('pendingApplications')) {
            return [];
        }

        return $this->pendingApplications
            ->sortByDesc('created_at')
            ->values()
            ->map(fn ($application): array => [
                'id' => $application->id,
                'status' => $application->status,
                'message' => $application->message,
                'created_at' => $application->created_at?->toISOString(),
                'user' => $application->user ? (new UserResource($application->user))->resolve($request) : null,
            ])
            ->all();
    }
}
