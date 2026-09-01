<?php

namespace App\Services\Arcade;

use App\Enums\Arcade\ArcadeGameStatus;
use App\Enums\Arcade\ArcadeGameType;
use App\Models\Arcade\ArcadeGame;
use Illuminate\Database\Eloquent\Collection;

class ArcadeGameCatalogService
{
    public function visibleGames(): Collection
    {
        return ArcadeGame::query()->with('publishedRelease')->publicCatalog()->orderBy('sort_order')->orderBy('id')->get();
    }

    public function visibleGame(string $key): ArcadeGame
    {
        return ArcadeGame::query()->with('publishedRelease')->publicCatalog()->where('key', $key)->firstOrFail();
    }

    public function availability(ArcadeGame $game): array
    {
        return match ($game->status) {
            ArcadeGameStatus::Active, ArcadeGameStatus::Event => $this->activeAvailability($game),
            ArcadeGameStatus::ComingSoon => [false, 'coming_soon'],
            ArcadeGameStatus::Maintenance => [false, 'maintenance'],
            ArcadeGameStatus::Draft => [false, 'draft'],
            default => [false, 'disabled'],
        };
    }

    private function activeAvailability(ArcadeGame $game): array
    {
        if ($game->type === ArcadeGameType::Web) {
            $release = $game->relationLoaded('publishedRelease')
                ? $game->publishedRelease
                : $game->publishedRelease()->first();

            return $release ? [true, null] : [false, 'dynamic_release_missing'];
        }

        return ($game->casual_enabled || $game->ranked_enabled)
            ? [true, null]
            : [false, 'mode_unavailable'];
    }
}
