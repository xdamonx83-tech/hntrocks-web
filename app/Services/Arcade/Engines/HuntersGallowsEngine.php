<?php

namespace App\Services\Arcade\Engines;

use Illuminate\Validation\ValidationException;

class HuntersGallowsEngine implements ArcadeGameEngine
{
    public const MAX_MISTAKES = 6;

    private const WORDS = [
        'weapons' => ['WINFIELD', 'CROSSBOW', 'MACHETE', 'REVOLVER'],
        'gear' => ['LANTERN', 'MEDKIT', 'DYNAMITE', 'DECOY'],
        'monsters' => ['HELLHOUND', 'IMMOLATOR', 'BUTCHER', 'SPIDER'],
        'western' => ['SALOON', 'SHERIFF', 'OUTLAW', 'FRONTIER'],
        'horror' => ['NIGHTMARE', 'HAUNTED', 'SPECTER', 'DARKNESS'],
    ];

    public function __construct(
        private readonly ?string $fixedWord = null,
        private readonly ?string $fixedCategory = null,
    ) {
    }

    public function initialize(): array
    {
        [$category, $word] = $this->selectWord();

        return [
            'secret_word' => $word,
            'category_key' => $category,
            'revealed_positions' => array_fill(0, strlen($word), false),
            'guessed_letters' => [],
            'turn_seat' => 1,
            'max_mistakes' => self::MAX_MISTAKES,
            'players' => [
                '1' => ['score' => 0, 'mistakes' => 0],
                '2' => ['score' => 0, 'mistakes' => 0],
            ],
            'winner_seat' => null,
            'draw' => false,
            'solved_by_seat' => null,
        ];
    }

    public function apply(array $state, int $seat, array $payload): array
    {
        if (($state['winner_seat'] ?? null) !== null || ($state['draw'] ?? false) === true || ($state['turn_seat'] ?? null) === null) {
            throw ValidationException::withMessages(['move' => 'This match is already finished.']);
        }
        if (! in_array($seat, [1, 2], true)) {
            throw ValidationException::withMessages(['seat' => 'Seat must be 1 or 2.']);
        }
        if (($state['turn_seat'] ?? null) !== $seat) {
            throw ValidationException::withMessages(['move' => 'It is not your turn.']);
        }

        $action = $payload['action'] ?? null;
        if ($action === 'guess_letter') {
            $state = $this->guessLetter($state, $seat, $payload);
        } elseif ($action === 'solve') {
            $state = $this->solve($state, $seat, $payload);
        } else {
            throw ValidationException::withMessages(['action' => 'Action must be guess_letter or solve.']);
        }

        return $state;
    }

    public function publicState(array $state, ?int $viewerSeat = null): array
    {
        $word = (string) ($state['secret_word'] ?? '');
        $revealed = (array) ($state['revealed_positions'] ?? []);
        $isTerminal = ($state['winner_seat'] ?? null) !== null || ($state['draw'] ?? false) === true;

        return [
            'masked_word' => array_map(
                fn (int $position): string => ($revealed[$position] ?? false) ? $word[$position] : '*',
                array_keys(str_split($word)),
            ),
            'category_key' => $state['category_key'] ?? null,
            'guessed_letters' => array_values((array) ($state['guessed_letters'] ?? [])),
            'current_seat' => $state['turn_seat'] ?? null,
            'max_mistakes' => (int) ($state['max_mistakes'] ?? self::MAX_MISTAKES),
            // Keep seat keys as an object in the serialized API payload.
            // Laravel JsonResource recursively reindexes arrays whose keys are
            // purely numeric, which would otherwise turn seats 1/2 into a
            // zero-based JSON list and break the public state contract.
            'players' => (object) [
                '1' => $this->publicPlayer($state, 1),
                '2' => $this->publicPlayer($state, 2),
            ],
            'winner_seat' => $state['winner_seat'] ?? null,
            'draw' => (bool) ($state['draw'] ?? false),
            'solved_by_seat' => $state['solved_by_seat'] ?? null,
            'solution_word' => $isTerminal ? $word : null,
        ];
    }

    public function moveType(): string
    {
        return 'gallows_move';
    }

    private function guessLetter(array $state, int $seat, array $payload): array
    {
        if (! $this->hasExactKeys($payload, ['action', 'letter']) || ! is_string($payload['letter'] ?? null)) {
            throw ValidationException::withMessages(['payload' => 'Letter moves must contain only action and a string letter.']);
        }
        $letter = strtoupper($payload['letter']);
        if (! preg_match('/^[A-Z]$/', $letter)) {
            throw ValidationException::withMessages(['letter' => 'Letter must be exactly one character from A through Z.']);
        }
        if (in_array($letter, (array) $state['guessed_letters'], true)) {
            throw ValidationException::withMessages(['letter' => 'This letter was already guessed.']);
        }

        $state['guessed_letters'][] = $letter;
        $hits = 0;
        foreach (str_split($state['secret_word']) as $position => $character) {
            if ($character === $letter && ! $state['revealed_positions'][$position]) {
                $state['revealed_positions'][$position] = true;
                $hits++;
            }
        }
        if ($hits > 0) {
            $state['players'][(string) $seat]['score'] += $hits;
        } else {
            $state['players'][(string) $seat]['mistakes']++;
        }

        if ($state['players'][(string) $seat]['mistakes'] >= self::MAX_MISTAKES) {
            return $this->finish($state, $seat === 1 ? 2 : 1);
        }
        if (! in_array(false, $state['revealed_positions'], true)) {
            return $this->finishRevealedWord($state);
        }

        $state['turn_seat'] = $seat === 1 ? 2 : 1;
        return $state;
    }

    private function solve(array $state, int $seat, array $payload): array
    {
        if (! $this->hasExactKeys($payload, ['action', 'word']) || ! is_string($payload['word'] ?? null)) {
            throw ValidationException::withMessages(['payload' => 'Solve moves must contain only action and a string word.']);
        }
        $word = strtoupper(trim($payload['word']));
        if ($word === '' || ! preg_match('/^[A-Z]+$/', $word)) {
            throw ValidationException::withMessages(['word' => 'Word must contain only letters A through Z.']);
        }
        if ($word === $state['secret_word']) {
            $state['solved_by_seat'] = $seat;
            return $this->finish($state, $seat);
        }

        $state['players'][(string) $seat]['mistakes'] += 2;
        if ($state['players'][(string) $seat]['mistakes'] >= self::MAX_MISTAKES) {
            return $this->finish($state, $seat === 1 ? 2 : 1);
        }
        $state['turn_seat'] = $seat === 1 ? 2 : 1;
        return $state;
    }

    private function finishRevealedWord(array $state): array
    {
        $one = $state['players']['1'];
        $two = $state['players']['2'];
        $winner = $one['score'] <=> $two['score'];
        if ($winner === 0) {
            $winner = $two['mistakes'] <=> $one['mistakes'];
        }
        if ($winner === 0) {
            $state['draw'] = true;
            return $this->finish($state, null);
        }

        return $this->finish($state, $winner > 0 ? 1 : 2);
    }

    private function finish(array $state, ?int $winnerSeat): array
    {
        $state['winner_seat'] = $winnerSeat;
        $state['turn_seat'] = null;
        return $state;
    }

    private function publicPlayer(array $state, int $seat): array
    {
        $player = (array) ($state['players'][(string) $seat] ?? []);
        return ['score' => (int) ($player['score'] ?? 0), 'mistakes' => (int) ($player['mistakes'] ?? 0)];
    }

    private function hasExactKeys(array $payload, array $expected): bool
    {
        $keys = array_keys($payload);
        sort($keys);
        sort($expected);

        return $keys === $expected;
    }

    private function selectWord(): array
    {
        if ($this->fixedWord !== null) {
            $word = strtoupper(trim($this->fixedWord));
            if (! preg_match('/^[A-Z]+$/', $word)) {
                throw ValidationException::withMessages(['word' => 'Configured word must contain only A through Z.']);
            }
            return [$this->fixedCategory ?? 'weapons', $word];
        }
        $category = array_rand(self::WORDS);
        return [$category, self::WORDS[$category][array_rand(self::WORDS[$category])]];
    }
}
