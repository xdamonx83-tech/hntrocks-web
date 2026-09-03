<?php

namespace App\Services\Arcade\Engines;

use Closure;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class MemoryEngine implements ArcadeGameEngine
{
    public const DEFAULT_PAIR_COUNT = 12;
    public const MOTIF_POOL_SIZE = 24;

    private readonly Closure $randomInt;

    public function __construct(?Closure $randomInt = null, private readonly int $pairCount = self::DEFAULT_PAIR_COUNT)
    {
        if ($pairCount < 1 || $pairCount > self::MOTIF_POOL_SIZE) {
            throw new InvalidArgumentException('Memory pair count must be between 1 and 24.');
        }

        $this->randomInt = $randomInt ?? static fn (int $min, int $max): int => random_int($min, $max);
    }

    public function initialize(): array
    {
        $pool = array_map(static fn (int $number): string => sprintf('memory_%02d', $number), range(1, self::MOTIF_POOL_SIZE));
        $selected = array_slice($this->shuffle($pool), 0, $this->pairCount);
        $deck = [];

        foreach ($selected as $motifId) {
            $deck[] = $motifId;
            $deck[] = $motifId;
        }

        $deck = $this->shuffle($deck);
        $cards = [];
        foreach ($deck as $index => $motifId) {
            $cards[] = [
                'index' => $index,
                'motif_id' => $motifId,
                'status' => 'hidden',
            ];
        }

        return [
            'cards' => $cards,
            'turn_seat' => 1,
            'scores' => ['1' => 0, '2' => 0],
            'matched_pairs' => 0,
            'total_pairs' => $this->pairCount,
            'revealed_indices' => [],
            'pending_mismatch' => null,
            'winner_seat' => null,
            'draw' => false,
        ];
    }

    public function apply(array $state, int $seat, array $payload): array
    {
        if (($state['winner_seat'] ?? null) !== null || ($state['draw'] ?? false) === true) {
            throw ValidationException::withMessages(['move' => 'This match is already finished.']);
        }
        if (! in_array($seat, [1, 2], true) || (int) ($state['turn_seat'] ?? 0) !== $seat) {
            throw ValidationException::withMessages(['move' => 'It is not your turn.']);
        }

        $this->validatePayload($payload);
        $cardIndex = $payload['card_index'];

        if (($state['pending_mismatch'] ?? null) !== null) {
            foreach ((array) $state['pending_mismatch'] as $pendingIndex) {
                if (isset($state['cards'][$pendingIndex]) && ($state['cards'][$pendingIndex]['status'] ?? null) === 'revealed') {
                    $state['cards'][$pendingIndex]['status'] = 'hidden';
                }
            }
            $state['pending_mismatch'] = null;
            $state['revealed_indices'] = [];
        }

        if ($cardIndex < 0 || $cardIndex >= count($state['cards'] ?? [])) {
            throw ValidationException::withMessages(['card_index' => 'Card index is outside the deck.']);
        }
        if (($state['cards'][$cardIndex]['status'] ?? null) !== 'hidden') {
            throw ValidationException::withMessages(['card_index' => 'This card cannot be revealed.']);
        }

        $state['cards'][$cardIndex]['status'] = 'revealed';
        $revealed = array_values(array_map('intval', (array) ($state['revealed_indices'] ?? [])));
        $revealed[] = $cardIndex;
        $state['revealed_indices'] = $revealed;

        if (count($revealed) === 1) {
            return $state;
        }

        if (count($revealed) !== 2) {
            throw ValidationException::withMessages(['move' => 'Memory reveal state is invalid.']);
        }

        [$firstIndex, $secondIndex] = $revealed;
        if ($firstIndex === $secondIndex) {
            throw ValidationException::withMessages(['card_index' => 'The same card cannot be revealed twice.']);
        }

        $firstMotif = $state['cards'][$firstIndex]['motif_id'] ?? null;
        $secondMotif = $state['cards'][$secondIndex]['motif_id'] ?? null;

        if (! is_string($firstMotif) || ! is_string($secondMotif)) {
            throw ValidationException::withMessages(['move' => 'Memory deck state is invalid.']);
        }

        if ($firstMotif === $secondMotif) {
            $state['cards'][$firstIndex]['status'] = 'matched';
            $state['cards'][$secondIndex]['status'] = 'matched';
            $state['cards'][$firstIndex]['matched_by_seat'] = $seat;
            $state['cards'][$secondIndex]['matched_by_seat'] = $seat;
            $state['scores'][(string) $seat] = (int) ($state['scores'][(string) $seat] ?? 0) + 1;
            $state['matched_pairs'] = (int) ($state['matched_pairs'] ?? 0) + 1;
            $state['revealed_indices'] = [];
            $state['pending_mismatch'] = null;

            if ($state['matched_pairs'] >= (int) ($state['total_pairs'] ?? $this->pairCount)) {
                $one = (int) ($state['scores']['1'] ?? 0);
                $two = (int) ($state['scores']['2'] ?? 0);
                if ($one === $two) {
                    $state['draw'] = true;
                    $state['winner_seat'] = null;
                } else {
                    $state['winner_seat'] = $one > $two ? 1 : 2;
                    $state['draw'] = false;
                }
            }

            return $state;
        }

        $state['pending_mismatch'] = [$firstIndex, $secondIndex];
        $state['turn_seat'] = $seat === 1 ? 2 : 1;

        return $state;
    }

    public function publicState(array $state, ?int $viewerSeat = null): array
    {
        $public = $state;
        $public['cards'] = array_map(static function (array $card): array {
            $projected = [
                'index' => (int) $card['index'],
                'status' => (string) $card['status'],
            ];

            if (in_array($card['status'], ['revealed', 'matched'], true)) {
                $projected['motif_id'] = $card['motif_id'];
            }

            if (($card['status'] ?? null) === 'matched' && in_array((int) ($card['matched_by_seat'] ?? 0), [1, 2], true)) {
                $projected['matched_by_seat'] = (int) $card['matched_by_seat'];
            }

            return $projected;
        }, (array) ($state['cards'] ?? []));

        return $public;
    }

    public function moveType(): string
    {
        return 'reveal';
    }

    private function validatePayload(array $payload): void
    {
        $keys = array_keys($payload);
        sort($keys);
        if ($keys !== ['action', 'card_index']) {
            throw ValidationException::withMessages(['payload' => 'Memory moves only accept action and card_index.']);
        }
        if (($payload['action'] ?? null) !== 'reveal') {
            throw ValidationException::withMessages(['action' => 'Unknown Memory action.']);
        }
        if (! is_int($payload['card_index'] ?? null)) {
            throw ValidationException::withMessages(['card_index' => 'Card index must be an integer.']);
        }
    }

    private function shuffle(array $values): array
    {
        $values = array_values($values);
        for ($index = count($values) - 1; $index > 0; $index--) {
            $swap = ($this->randomInt)(0, $index);
            if (! is_int($swap) || $swap < 0 || $swap > $index) {
                throw new InvalidArgumentException('Memory random source returned an invalid index.');
            }
            [$values[$index], $values[$swap]] = [$values[$swap], $values[$index]];
        }

        return $values;
    }
}
