<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $membership = null;

        if ($viewer && $this->relationLoaded('members')) {
            $membership = $this->members->firstWhere('user_id', $viewer->id);
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'tagline' => $this->tagline,
            'description' => $this->description,
            'platform' => $this->platform,
            'playstyle' => $this->playstyle,
            'region' => $this->region,
            'language' => $this->language,
            'visibility' => $this->visibility,
            'recruitment_status' => $this->recruitment_status,
            'avatar_url' => $this->avatarUrl(),
            'cover_url' => $this->coverUrl(),
            'members_count' => (int) ($this->active_members_count ?? 0),
            'owner' => new UserResource($this->whenLoaded('owner')),
            'viewer' => [
                'membership' => $membership ? [
                    'id' => $membership->id,
                    'role' => $membership->role,
                    'status' => $membership->status,
                    'joined_at' => $membership->joined_at?->toISOString(),
                ] : null,
                'is_member' => $membership?->status === 'active',
                'is_pending' => $membership?->status === 'pending',
                'is_owner' => $viewer ? (int) $this->owner_id === (int) $viewer->id : false,
                'can_manage' => $membership?->status === 'active'
                    && in_array($membership?->role, ['owner', 'officer'], true),
                'can_join' => $this->visibility === 'public'
                    && $this->recruitment_status === 'open'
                    && ! in_array($membership?->status, ['active', 'pending'], true),
            ],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
