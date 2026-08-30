<?php

namespace App\Services\Arcade\Engines;

interface ArcadeGameEngine
{
    public function initialize(): array;
    public function apply(array $state, int $seat, array $payload): array;
}
