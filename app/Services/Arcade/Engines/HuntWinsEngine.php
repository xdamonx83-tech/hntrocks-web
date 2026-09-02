<?php

namespace App\Services\Arcade\Engines;

use Illuminate\Validation\ValidationException;

class HuntWinsEngine implements ArcadeGameEngine
{
    public const ROWS = 6;
    public const COLUMNS = 7;

    public function initialize(): array
    {
        return ['board' => array_fill(0, self::ROWS, array_fill(0, self::COLUMNS, 0)), 'turn_seat' => 1, 'move_count' => 0, 'winner_seat' => null, 'draw' => false];
    }

    public function apply(array $state, int $seat, array $payload): array
    {
        $column = $payload['column'] ?? null;
        if (! is_int($column) || $column < 0 || $column >= self::COLUMNS) {
            throw ValidationException::withMessages(['column' => 'Column must be an integer from 0 through 6.']);
        }
        if (($state['turn_seat'] ?? null) !== $seat) {
            throw ValidationException::withMessages(['move' => 'It is not your turn.']);
        }

        $row = null;
        for ($candidate = self::ROWS - 1; $candidate >= 0; $candidate--) {
            if (($state['board'][$candidate][$column] ?? null) === 0) { $row = $candidate; break; }
        }
        if ($row === null) {
            throw ValidationException::withMessages(['column' => 'This column is full.']);
        }

        $state['board'][$row][$column] = $seat;
        $state['move_count'] = (int) $state['move_count'] + 1;
        if ($this->hasFour($state['board'], $row, $column, $seat)) {
            $state['winner_seat'] = $seat;
        } elseif ($state['move_count'] === self::ROWS * self::COLUMNS) {
            $state['draw'] = true;
        } else {
            $state['turn_seat'] = $seat === 1 ? 2 : 1;
        }

        return $state;
    }

    public function publicState(array $state, ?int $viewerSeat = null): array
    {
        return $state;
    }

    public function moveType(): string
    {
        return 'drop';
    }

    private function hasFour(array $board, int $row, int $column, int $seat): bool
    {
        foreach ([[0, 1], [1, 0], [1, 1], [1, -1]] as [$dr, $dc]) {
            $count = 1;
            foreach ([-1, 1] as $direction) {
                for ($step = 1; $step < 4; $step++) {
                    $r = $row + $dr * $step * $direction;
                    $c = $column + $dc * $step * $direction;
                    if ($r < 0 || $r >= self::ROWS || $c < 0 || $c >= self::COLUMNS || ($board[$r][$c] ?? 0) !== $seat) break;
                    $count++;
                }
            }
            if ($count >= 4) return true;
        }
        return false;
    }
}
