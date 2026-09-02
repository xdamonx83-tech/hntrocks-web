<?php

namespace App\Services\Arcade\Engines;

interface ArcadeGameEngine
{
    public function initialize(): array;

    public function apply(array $state, int $seat, array $payload): array;

    public function publicState(array $state, ?int $viewerSeat = null): array;

    public function moveType(): string;
}
