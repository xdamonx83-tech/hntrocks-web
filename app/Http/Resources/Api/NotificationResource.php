<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $actionUrl = method_exists($this->resource, 'actionUrl')
            ? $this->resource->actionUrl()
            : ($this->action_url ?? $this->url ?? null);
        $actor = $this->relationLoaded('actor') ? $this->actor : null;
        $actorAvatarUrl = method_exists($this->resource, 'displayActorAvatarUrl')
            ? $this->resource->displayActorAvatarUrl()
            : ($actor ? (method_exists($actor, 'avatarUrl') ? $actor->avatarUrl() : null) : null);
        $actorName = method_exists($this->resource, 'displayActorName')
            ? $this->resource->displayActorName()
            : ($actor?->name ?: $actor?->username ?: null);

        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => method_exists($this->resource, 'displayTitle') ? $this->resource->displayTitle() : $this->title,
            'body' => method_exists($this->resource, 'displayBody') ? $this->resource->displayBody() : $this->body,
            'url' => $actionUrl,
            'action_url' => $actionUrl,
            'data' => $this->data,
            'actor_id' => $actor ? (int) $actor->id : null,
            'actor_name' => $actorName,
            'actor_avatar_url' => $actorAvatarUrl,
            'actor' => $actor ? [
                'id' => (int) $actor->id,
                'name' => $actor->name,
                'username' => $actor->username,
                'avatar_url' => $actorAvatarUrl,
            ] : null,
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'created_at_local' => $this->created_at?->timezone(config('app.timezone', 'UTC'))->format('Y-m-d H:i:s'),
            'created_at_human' => $this->created_at?->diffForHumans(),
        ];
    }
}
