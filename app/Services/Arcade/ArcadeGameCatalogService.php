<?php

namespace App\Services\Arcade;

use App\Enums\Arcade\ArcadeGameStatus;
use App\Models\Arcade\ArcadeGame;
use Illuminate\Database\Eloquent\Collection;

class ArcadeGameCatalogService
{
    public function visibleGames(): Collection
    {
        return ArcadeGame::query()->publicCatalog()->orderBy('sort_order')->orderBy('id')->get();
    }

    public function visibleGame(string $key): ArcadeGame
    {
        return ArcadeGame::query()->publicCatalog()->where('key', $key)->firstOrFail();
    }

    public function availability(ArcadeGame $game): array
    {
        return match ($game->status) {
            ArcadeGameStatus::Active, ArcadeGameStatus::Event => ($game->casual_enabled || $game->ranked_enabled) ? [true, null] : [false, 'mode_unavailable'],
            ArcadeGameStatus::ComingSoon => [false, 'coming_soon'],
            ArcadeGameStatus::Maintenance => [false, 'maintenance'],
            default => [false, 'disabled'],
        };
    }
}
