<?php

namespace App\Http\Resources\Api;

use App\Models\User;
use App\Support\HunterDna;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Support\CrownCosmetics;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->whenLoaded('profile');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->when((bool) $request->user() && (int) $request->user()->id === (int) $this->id, $this->email),
            'avatar_url' => $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : asset('assets/vikinger/img/default-avatar.svg'),
            'cover_url' => $this->cover_path ? Storage::disk('public')->url($this->cover_path) : asset('assets/vikinger/img/default-cover.svg'),
            'crown_cosmetics' => $this->crownsPayload(),
            'level' => (int) ($this->level ?? 1),
            'xp_total' => (int) ($this->xp_total ?? 0),
            'trust_score' => (int) ($this->trust_score ?? 0),
            'presence' => $this->presencePayload($request),
            'profile' => $this->whenLoaded('profile', fn () => [
                'headline' => $profile?->headline,
                'bio' => $profile?->bio,
                'platform' => $profile?->platform,
                'playstyle' => $profile?->playstyle,
                'region' => $profile?->region,
                'language' => $profile?->language,
                'hunt_role' => $profile?->hunt_role,
                'discord_name' => $profile?->discord_name,
                'twitch_url' => $profile?->twitch_url,
                'is_lfg_available' => (bool) ($profile?->is_lfg_available ?? false),
                'hunter_dna' => HunterDna::payload($profile?->hunter_dna),
                'hunter_dna_completed_at' => $profile?->hunter_dna_completed_at?->toISOString(),
                'hunter_dna_summary' => HunterDna::summary($profile),
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function presencePayload(Request $request): array
    {
        $onlineStatusVisible = $this->resource->allowsOnlineStatusVisibility($request->user());

        return [
            'online_status_visible' => $onlineStatusVisible,
            'is_online' => $onlineStatusVisible ? $this->resource->isOnline() : false,
            'last_seen_at' => $onlineStatusVisible ? $this->last_seen_at?->toISOString() : null,
            'online_window_seconds' => User::ONLINE_WINDOW_SECONDS,
        ];
    }

    private function crownsPayload(): array
    {
        $cosmetics = CrownCosmetics::forUser($this->resource);

        return [
            'avatar_frame' => $this->crownItemPayload($cosmetics['avatar_frame'] ?? null),
            'username_effect' => $this->crownItemPayload($cosmetics['username_effect'] ?? null),
            'profile_banner' => $this->crownItemPayload($cosmetics['profile_banner'] ?? null),
            'profile_title' => $this->crownItemPayload($cosmetics['profile_title'] ?? null),
            'avatar_frame_class' => (string) ($cosmetics['avatar_frame_class'] ?? ''),
            'username_effect_class' => (string) ($cosmetics['username_effect_class'] ?? ''),
            'profile_banner_class' => (string) ($cosmetics['profile_banner_class'] ?? ''),
            'profile_title_label' => (string) ($cosmetics['profile_title_label'] ?? ''),
        ];
    }

    private function crownItemPayload($item): ?array
    {
        if (! $item) {
            return null;
        }

        return [
            'id' => (int) $item->id,
            'key' => (string) $item->key,
            'slot' => (string) $item->slot,
            'rarity' => (string) $item->rarity,
            'name' => (string) $item->displayName(),
            'preview_class' => (string) ($item->preview_class ?? ''),
        ];
    }
}
