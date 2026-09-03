<?php

namespace App\Services\Arcade\Engines;

use Illuminate\Validation\ValidationException;

class HuntersMarkEngine implements ArcadeGameEngine
{
    public const CELLS = 9;

    private const WINNING_LINES = [
        [0, 1, 2],
        [3, 4, 5],
        [6, 7, 8],
        [0, 3, 6],
        [1, 4, 7],
        [2, 5, 8],
        [0, 4, 8],
        [2, 4, 6],
    ];

    public function initialize(): array
    {
        return [
            'board' => array_fill(0, self::CELLS, null),
            'turn_seat' => 1,
            'move_count' => 0,
            'winner_seat' => null,
            'draw' => false,
            'winning_cells' => [],
        ];
    }

    public function apply(array $state, int $seat, array $payload): array
    {
        if (($state['winner_seat'] ?? null) !== null || ($state['draw'] ?? false) === true) {
            throw ValidationException::withMessages(['move' => 'This match is already finished.']);
        }

        if (array_keys($payload) !== ['action', 'cell_index']) {
            throw ValidationException::withMessages(['payload' => 'Move payload must contain only action and cell_index.']);
        }

        if (($payload['action'] ?? null) !== 'place') {
            throw ValidationException::withMessages(['action' => 'Action must be place.']);
        }

        $cell = $payload['cell_index'] ?? null;
        if (! is_int($cell) || $cell < 0 || $cell >= self::CELLS) {
            throw ValidationException::withMessages(['cell_index' => 'Cell index must be an integer from 0 through 8.']);
        }

        if (($state['turn_seat'] ?? null) !== $seat) {
            throw ValidationException::withMessages(['move' => 'It is not your turn.']);
        }

        $board = (array) ($state['board'] ?? []);
        if (count($board) !== self::CELLS) {
            throw ValidationException::withMessages(['state' => 'Hunter\'s Mark board must contain exactly 9 cells.']);
        }

        if (($board[$cell] ?? null) !== null) {
            throw ValidationException::withMessages(['cell_index' => 'This cell is already occupied.']);
        }

        $board[$cell] = $seat;
        $state['board'] = array_values($board);
        $state['move_count'] = (int) ($state['move_count'] ?? 0) + 1;

        $winningCells = $this->winningCells($state['board'], $seat);
        if ($winningCells !== []) {
            $state['winner_seat'] = $seat;
            $state['winning_cells'] = $winningCells;
        } elseif ($state['move_count'] >= self::CELLS) {
            $state['draw'] = true;
            $state['winning_cells'] = [];
        } else {
            $state['turn_seat'] = $seat === 1 ? 2 : 1;
        }

        return $state;
    }

    public function publicState(array $state, ?int $viewerSeat = null): array
    {
        $finished = ($state['winner_seat'] ?? null) !== null || ($state['draw'] ?? false) === true;

        return [
            'board' => array_values((array) ($state['board'] ?? [])),
            'current_seat' => $finished ? null : ($state['turn_seat'] ?? null),
            'winner_seat' => $state['winner_seat'] ?? null,
            'draw' => (bool) ($state['draw'] ?? false),
            'winning_cells' => array_values((array) ($state['winning_cells'] ?? [])),
        ];
    }

    public function moveType(): string
    {
        return 'place';
    }

    private function winningCells(array $board, int $seat): array
    {
        foreach (self::WINNING_LINES as $line) {
            if (($board[$line[0]] ?? null) === $seat
                && ($board[$line[1]] ?? null) === $seat
                && ($board[$line[2]] ?? null) === $seat) {
                return $line;
            }
        }

        return [];
    }
}
