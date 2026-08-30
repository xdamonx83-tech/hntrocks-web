<?php

namespace App\Http\Resources\Api;

use App\Services\Arcade\ArcadeGameCatalogService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArcadeGameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = in_array($request->query('locale', app()->getLocale()), ['de', 'en'], true) ? $request->query('locale', app()->getLocale()) : 'de';
        [$playable, $reason] = app(ArcadeGameCatalogService::class)->availability($this->resource);

        return ['key' => $this->key, 'name' => $this->{'name_'.$locale} ?: $this->name_de, 'description' => $this->{'description_'.$locale} ?: $this->description_de, 'type' => $this->type->value, 'status' => $this->status->value, 'cover_url' => $this->coverUrl(), 'sort_order' => $this->sort_order, 'min_players' => $this->min_players, 'max_players' => $this->max_players, 'casual_enabled' => $this->casual_enabled, 'ranked_enabled' => $this->ranked_enabled, 'game_version' => $this->game_version, 'client_engine_key' => $this->client_engine_key, 'min_client_version' => $this->min_client_version, 'launch_url' => $this->launch_url, 'badge' => $this->{'badge_'.$locale} ?: $this->badge_de, 'is_playable' => $playable, 'unavailable_reason' => $reason, 'settings' => $this->settings ?? (object) []];
    }
}
