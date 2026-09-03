<?php

namespace App\Services\Arcade;

use App\Models\Arcade\ArcadeGame;
use App\Services\Arcade\Engines\ArcadeGameEngine;
use App\Services\Arcade\Engines\HuntWinsEngine;
use App\Services\Arcade\Engines\MemoryEngine;
use RuntimeException;

class ArcadeGameEngineRegistry
{
    public function resolve(ArcadeGame $game): ArcadeGameEngine
    {
        return match ($game->client_engine_key ?: $game->key) {
            'hunt-wins' => app(HuntWinsEngine::class),
            'hunt-memory' => app(MemoryEngine::class),
            default => throw new RuntimeException("No server game engine registered for {$game->key}."),
        };
    }
}
