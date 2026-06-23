<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LiveLobbyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $isCreator = $viewer && (int) $this->creator_id === (int) $viewer->id;
        $isMember = $viewer && $this->activeMembers->contains('user_id', $viewer->id);
        $canJoin = $viewer && ! $isMember && $this->status === 'open' && $this->expires_at->isFuture();

        return [
            'id' => $this->public_id,
            'public_id' => $this->public_id,
            'status' => $this->status,
            'mode' => $this->mode,
            'slots_total' => (int) $this->slots_total,
            'slots_filled' => (int) $this->slots_filled,
            'missing_slots' => max(0, (int) $this->slots_total - (int) $this->slots_filled),
            'platform' => $this->platform,
            'crossplay_pool' => $this->crossplay_pool,
            'region' => $this->region,
            'language' => $this->language,
            'voice_required' => (bool) $this->voice_required,
            'playstyle' => $this->playstyle,
            'note' => $this->note,
            'creator' => $this->userSummary($this->creator),
            'members' => $this->activeMembers->map(fn ($member): array => [
                ...$this->userSummary($member->user),
                'role' => $member->role,
                'platform' => $member->platform,
            ])->values()->all(),
            'viewer' => [
                'is_creator' => (bool) $isCreator,
                'is_member' => (bool) $isMember,
                'can_join' => (bool) $canJoin,
                'can_leave' => (bool) $isMember && in_array($this->status, ['open', 'full'], true),
                'can_close' => (bool) $isCreator && in_array($this->status, ['open', 'full'], true),
            ],
            'contact' => $this->when($isMember, fn (): array => [
                'lobby_code' => $this->lobby_code,
                'steam_id' => $this->steam_id,
                'psn_id' => $this->psn_id,
                'xbox_gamertag' => $this->xbox_gamertag,
                'discord_handle' => $this->discord_handle,
            ]),
            'expires_at' => $this->expires_at?->toISOString(),
            'full_at' => $this->full_at?->toISOString(),
            'closed_at' => $this->closed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function userSummary($user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'username' => $user->username,
            'display_name' => $user->name,
            'avatar_url' => $user->avatarUrl(),
        ];
    }
}
