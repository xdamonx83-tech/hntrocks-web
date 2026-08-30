<?php

namespace App\Services\Guides;

use App\Models\GuideRevision;

class GuideRevisionDiffService
{
    /** @return array<string, mixed> */
    public function compare(?GuideRevision $baseline, GuideRevision $candidate): array
    {
        if (! $baseline || (int) $baseline->id === (int) $candidate->id) {
            return [
                'applicable' => false,
                'has_changes' => false,
                'metadata' => [],
                'blocks' => [],
                'counts' => ['added' => 0, 'removed' => 0, 'changed' => 0, 'moved' => 0],
            ];
        }

        $baseline->loadMissing('category');
        $candidate->loadMissing('category');

        $metadata = [];
        $this->pushTextChange($metadata, 'Titel', (string) $baseline->title, (string) $candidate->title);
        $this->pushTextChange($metadata, 'Kurzbeschreibung', (string) $baseline->summary, (string) $candidate->summary);
        $this->pushValueChange(
            $metadata,
            'Kategorie',
            $baseline->category?->label(app()->getLocale() === 'en' ? 'en' : 'de') ?: '—',
            $candidate->category?->label(app()->getLocale() === 'en' ? 'en' : 'de') ?: '—',
        );
        $this->pushValueChange($metadata, 'Sprache', strtoupper((string) $baseline->language), strtoupper((string) $candidate->language));
        $this->pushValueChange($metadata, 'Schwierigkeit', ucfirst((string) $baseline->difficulty), ucfirst((string) $candidate->difficulty));
        $this->pushValueChange($metadata, 'Plattform', strtoupper((string) $baseline->platform), strtoupper((string) $candidate->platform));

        $baselineTags = collect((array) $baseline->tags)->filter()->map(fn ($tag) => (string) $tag)->values()->all();
        $candidateTags = collect((array) $candidate->tags)->filter()->map(fn ($tag) => (string) $tag)->values()->all();
        $this->pushValueChange($metadata, 'Tags', implode(', ', $baselineTags) ?: '—', implode(', ', $candidateTags) ?: '—');

        if ((int) $baseline->cover_media_id !== (int) $candidate->cover_media_id) {
            $metadata[] = [
                'label' => 'Titelbild',
                'kind' => 'value',
                'old' => $baseline->cover_media_id ? 'Vorheriges Titelbild' : 'Kein Titelbild',
                'new' => $candidate->cover_media_id ? 'Neues Titelbild' : 'Titelbild entfernt',
            ];
        }

        $blockDiff = $this->compareBlocks((array) $baseline->content_blocks, (array) $candidate->content_blocks);

        return [
            'applicable' => true,
            'baseline_version' => (int) $baseline->version,
            'candidate_version' => (int) $candidate->version,
            'has_changes' => $metadata !== [] || $blockDiff['blocks'] !== [],
            'metadata' => $metadata,
            'blocks' => $blockDiff['blocks'],
            'counts' => $blockDiff['counts'],
        ];
    }

    /** @return array<int, array{type:string,text:string}> */
    public function textDiff(string $old, string $new): array
    {
        if ($old === $new) {
            return $old === '' ? [] : [['type' => 'same', 'text' => $old]];
        }

        $a = $this->tokenize($old);
        $b = $this->tokenize($new);
        $n = count($a);
        $m = count($b);

        if ($n === 0) {
            return $new === '' ? [] : [['type' => 'add', 'text' => $new]];
        }

        if ($m === 0) {
            return [['type' => 'remove', 'text' => $old]];
        }

        $max = $n + $m;
        $v = [1 => 0];
        $trace = [];
        $finalD = 0;

        for ($d = 0; $d <= $max; $d++) {
            $next = $v;

            for ($k = -$d; $k <= $d; $k += 2) {
                $left = $v[$k - 1] ?? PHP_INT_MIN;
                $right = $v[$k + 1] ?? PHP_INT_MIN;

                if ($k === -$d || ($k !== $d && $left < $right)) {
                    $x = $right === PHP_INT_MIN ? 0 : $right;
                } else {
                    $x = ($left === PHP_INT_MIN ? 0 : $left) + 1;
                }

                $y = $x - $k;
                while ($x < $n && $y < $m && $a[$x] === $b[$y]) {
                    $x++;
                    $y++;
                }

                $next[$k] = $x;

                if ($x >= $n && $y >= $m) {
                    $trace[$d] = $next;
                    $finalD = $d;
                    break 2;
                }
            }

            $trace[$d] = $next;
            $v = $next;
        }

        $x = $n;
        $y = $m;
        $edits = [];

        for ($d = $finalD; $d > 0; $d--) {
            $previous = $trace[$d - 1] ?? [1 => 0];
            $k = $x - $y;
            $left = $previous[$k - 1] ?? PHP_INT_MIN;
            $right = $previous[$k + 1] ?? PHP_INT_MIN;
            $previousK = ($k === -$d || ($k !== $d && $left < $right)) ? $k + 1 : $k - 1;
            $previousX = $previous[$previousK] ?? 0;
            $previousY = $previousX - $previousK;

            while ($x > $previousX && $y > $previousY) {
                $edits[] = ['type' => 'same', 'text' => $a[$x - 1]];
                $x--;
                $y--;
            }

            if ($x === $previousX) {
                if ($y > 0) {
                    $edits[] = ['type' => 'add', 'text' => $b[$y - 1]];
                    $y--;
                }
            } elseif ($x > 0) {
                $edits[] = ['type' => 'remove', 'text' => $a[$x - 1]];
                $x--;
            }
        }

        while ($x > 0 && $y > 0) {
            $edits[] = ['type' => 'same', 'text' => $a[$x - 1]];
            $x--;
            $y--;
        }
        while ($x > 0) {
            $edits[] = ['type' => 'remove', 'text' => $a[$x - 1]];
            $x--;
        }
        while ($y > 0) {
            $edits[] = ['type' => 'add', 'text' => $b[$y - 1]];
            $y--;
        }

        $edits = array_reverse($edits);
        $merged = [];

        foreach ($edits as $edit) {
            $last = array_key_last($merged);
            if ($last !== null && $merged[$last]['type'] === $edit['type']) {
                $merged[$last]['text'] .= $edit['text'];
            } else {
                $merged[] = $edit;
            }
        }

        return $merged;
    }

    /** @return array{blocks:array<int,array<string,mixed>>,counts:array<string,int>} */
    private function compareBlocks(array $oldBlocks, array $newBlocks): array
    {
        $oldMap = [];
        foreach ($oldBlocks as $index => $block) {
            if (! is_array($block)) {
                continue;
            }
            $oldMap[$this->blockKey($block, (int) $index)] = ['index' => (int) $index, 'block' => $block];
        }

        $newMap = [];
        foreach ($newBlocks as $index => $block) {
            if (! is_array($block)) {
                continue;
            }
            $newMap[$this->blockKey($block, (int) $index)] = ['index' => (int) $index, 'block' => $block];
        }

        $entries = [];
        $counts = ['added' => 0, 'removed' => 0, 'changed' => 0, 'moved' => 0];

        foreach ($newMap as $key => $newEntry) {
            $newBlock = $newEntry['block'];
            if (! isset($oldMap[$key])) {
                $entries[] = [
                    'status' => 'added',
                    'label' => $this->blockLabel((string) ($newBlock['type'] ?? 'block')),
                    'new_position' => $newEntry['index'] + 1,
                    'new_text' => $this->blockText($newBlock),
                ];
                $counts['added']++;
                continue;
            }

            $oldEntry = $oldMap[$key];
            $oldBlock = $oldEntry['block'];
            $changes = $this->blockChanges($oldBlock, $newBlock);
            $moved = $oldEntry['index'] !== $newEntry['index'];

            if ($changes !== [] || $moved) {
                $entries[] = [
                    'status' => $changes !== [] ? 'changed' : 'moved',
                    'label' => $this->blockLabel((string) ($newBlock['type'] ?? 'block')),
                    'old_position' => $oldEntry['index'] + 1,
                    'new_position' => $newEntry['index'] + 1,
                    'moved' => $moved,
                    'changes' => $changes,
                ];

                if ($changes !== []) {
                    $counts['changed']++;
                }
                if ($moved) {
                    $counts['moved']++;
                }
            }
        }

        foreach ($oldMap as $key => $oldEntry) {
            if (isset($newMap[$key])) {
                continue;
            }
            $oldBlock = $oldEntry['block'];
            $entries[] = [
                'status' => 'removed',
                'label' => $this->blockLabel((string) ($oldBlock['type'] ?? 'block')),
                'old_position' => $oldEntry['index'] + 1,
                'old_text' => $this->blockText($oldBlock),
            ];
            $counts['removed']++;
        }

        return ['blocks' => $entries, 'counts' => $counts];
    }

    /** @return array<int,array<string,mixed>> */
    private function blockChanges(array $old, array $new): array
    {
        $changes = [];
        $oldType = (string) ($old['type'] ?? '');
        $newType = (string) ($new['type'] ?? '');

        if ($oldType !== $newType) {
            $changes[] = ['label' => 'Blocktyp', 'kind' => 'value', 'old' => $this->blockLabel($oldType), 'new' => $this->blockLabel($newType)];
            return $changes;
        }

        if ($newType === 'heading') {
            $this->pushValueChange($changes, 'Überschriftsebene', 'H'.((int) ($old['level'] ?? 2)), 'H'.((int) ($new['level'] ?? 2)));
            $this->pushTextChange($changes, 'Text', (string) ($old['text'] ?? ''), (string) ($new['text'] ?? ''));
        } elseif ($newType === 'paragraph') {
            $this->pushTextChange($changes, 'Text', (string) ($old['text'] ?? ''), (string) ($new['text'] ?? ''));
        } elseif ($newType === 'steps' || $newType === 'list') {
            $this->pushTextChange(
                $changes,
                $newType === 'steps' ? 'Schritte' : 'Liste',
                implode("\n", array_map('strval', (array) ($old['items'] ?? []))),
                implode("\n", array_map('strval', (array) ($new['items'] ?? []))),
            );
        } elseif ($newType === 'notice' || $newType === 'warning') {
            $this->pushTextChange($changes, 'Titel', (string) ($old['title'] ?? ''), (string) ($new['title'] ?? ''));
            $this->pushTextChange($changes, 'Text', (string) ($old['text'] ?? ''), (string) ($new['text'] ?? ''));
        } elseif ($newType === 'image') {
            $this->pushValueChange(
                $changes,
                'Bild',
                (int) ($old['media_id'] ?? 0) > 0 ? 'Bild #'.(int) $old['media_id'] : 'Kein Bild',
                (int) ($new['media_id'] ?? 0) > 0 ? 'Bild #'.(int) $new['media_id'] : 'Kein Bild',
            );
            $this->pushTextChange($changes, 'Bildunterschrift', (string) ($old['caption'] ?? ''), (string) ($new['caption'] ?? ''));
        }

        return $changes;
    }

    private function blockKey(array $block, int $index): string
    {
        $id = trim((string) ($block['id'] ?? ''));
        return $id !== '' ? 'id:'.$id : 'legacy:'.$index;
    }

    private function blockLabel(string $type): string
    {
        return match ($type) {
            'heading' => 'Überschrift',
            'paragraph' => 'Textabschnitt',
            'steps' => 'Schritte',
            'list' => 'Liste',
            'image' => 'Bild',
            'notice' => 'Hinweis',
            'warning' => 'Warnung',
            default => ucfirst($type ?: 'Block'),
        };
    }

    private function blockText(array $block): string
    {
        $type = (string) ($block['type'] ?? '');
        return match ($type) {
            'heading', 'paragraph' => (string) ($block['text'] ?? ''),
            'steps', 'list' => implode("\n", array_map('strval', (array) ($block['items'] ?? []))),
            'notice', 'warning' => trim((string) ($block['title'] ?? '')."\n".(string) ($block['text'] ?? '')),
            'image' => trim('Bild #'.(int) ($block['media_id'] ?? 0).' '.(string) ($block['caption'] ?? '')),
            default => json_encode($block, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '',
        };
    }

    /** @param array<int,array<string,mixed>> $changes */
    private function pushTextChange(array &$changes, string $label, string $old, string $new): void
    {
        if ($old === $new) {
            return;
        }

        $changes[] = [
            'label' => $label,
            'kind' => 'text',
            'old' => $old,
            'new' => $new,
            'segments' => $this->textDiff($old, $new),
        ];
    }

    /** @param array<int,array<string,mixed>> $changes */
    private function pushValueChange(array &$changes, string $label, string $old, string $new): void
    {
        if ($old === $new) {
            return;
        }

        $changes[] = [
            'label' => $label,
            'kind' => 'value',
            'old' => $old,
            'new' => $new,
        ];
    }

    /** @return array<int,string> */
    private function tokenize(string $text): array
    {
        $tokens = preg_split('/(\s+|[[:punct:]])/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        return is_array($tokens) ? array_values($tokens) : [$text];
    }
}
