<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
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
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
