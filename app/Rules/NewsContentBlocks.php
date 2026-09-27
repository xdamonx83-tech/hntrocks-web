<?php

namespace App\Rules;

use App\Models\MediaAsset;
use App\Support\NewsContentDocument;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

class NewsContentBlocks implements ValidationRule
{
    private const TEXT_LIMITS = [
        'paragraph' => ['text' => 20000],
        'heading' => ['text' => 240],
        'quote' => ['text' => 6000, 'attribution' => 240],
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        if (NewsContentDocument::isV2($value)) {
            try {
                $assetTypes = NewsContentDocument::validate($value);
            } catch (InvalidArgumentException $exception) {
                $fail($exception->getMessage());
                return;
            }
            $this->validateAssets($assetTypes, $fail);
            return;
        }

        if (! is_array($value) || ! array_is_list($value) || count($value) > 100) {
            $fail('The :attribute field must be a list of at most 100 content blocks.');

            return;
        }

        $assetTypes = [];

        foreach ($value as $index => $block) {
            if (! is_array($block) || ! is_string($block['type'] ?? null)) {
                $fail("The :attribute block at position {$index} must include a supported type.");

                return;
            }

            $type = $block['type'];
            $allowed = match ($type) {
                'paragraph' => ['type', 'text', 'runs'],
                'heading' => ['type', 'text', 'level'],
                'image' => ['type', 'media_id', 'alt', 'caption'],
                'video' => ['type', 'media_id', 'poster_media_id', 'caption'],
                'before_after' => ['type', 'before_media_id', 'after_media_id', 'before_alt', 'after_alt', 'before_label', 'after_label', 'caption'],
                'quote' => ['type', 'text', 'attribution'],
                'list' => ['type', 'style', 'items'],
                default => null,
            };

            if ($allowed === null || array_diff(array_keys($block), $allowed) !== []) {
                $fail("The :attribute block at position {$index} has an unsupported type or field.");

                return;
            }

            if (isset(self::TEXT_LIMITS[$type])) {
                foreach (self::TEXT_LIMITS[$type] as $field => $limit) {
                    if (! is_string($block[$field] ?? null) || trim($block[$field]) === '' || mb_strlen($block[$field]) > $limit) {
                        $fail("The :attribute {$type} block at position {$index} has invalid {$field} text.");

                        return;
                    }
                }
            }

            if ($type === 'paragraph' && array_key_exists('runs', $block)) {
                $runs = $block['runs'];
                if (! is_array($runs) || ! array_is_list($runs) || count($runs) < 1 || count($runs) > 300) {
                    $fail("The :attribute paragraph block at position {$index} has invalid rich text runs.");
                    return;
                }

                $combined = '';
                foreach ($runs as $run) {
                    if (! is_array($run) || array_diff(array_keys($run), ['text', 'bold', 'italic', 'href']) !== []
                        || ! is_string($run['text'] ?? null) || $run['text'] === ''
                        || (isset($run['bold']) && ! is_bool($run['bold']))
                        || (isset($run['italic']) && ! is_bool($run['italic']))) {
                        $fail("The :attribute paragraph block at position {$index} has an invalid rich text run.");
                        return;
                    }

                    if (isset($run['href'])) {
                        $href = $run['href'];
                        $isLocal = is_string($href) && str_starts_with($href, '/') && ! str_starts_with($href, '//');
                        $isWeb = is_string($href) && filter_var($href, FILTER_VALIDATE_URL)
                            && in_array(strtolower((string) parse_url($href, PHP_URL_SCHEME)), ['http', 'https'], true);
                        if (strlen((string) $href) > 2048 || (! $isLocal && ! $isWeb)) {
                            $fail("The :attribute paragraph block at position {$index} has an invalid link.");
                            return;
                        }
                    }
                    $combined .= $run['text'];
                }

                if ($combined !== $block['text']) {
                    $fail("The :attribute paragraph block at position {$index} has rich text that differs from its plain text.");
                    return;
                }
            }

            if ($type === 'heading' && ! in_array($block['level'] ?? null, [2, 3], true)) {
                $fail("The :attribute heading block at position {$index} must use level 2 or 3.");

                return;
            }

            if ($type === 'image') {
                if (! $this->requiredText($block, 'alt', 300) || ! $this->optionalText($block, 'caption', 1000)) {
                    $fail("The :attribute image block at position {$index} needs alternative text and a valid caption.");

                    return;
                }

                $assetTypes[$block['media_id'] ?? 0] = 'image';
            }

            if ($type === 'video') {
                if (! $this->optionalText($block, 'caption', 1000) || ! $this->positiveId($block['media_id'] ?? null)) {
                    $fail("The :attribute video block at position {$index} needs a valid video asset.");

                    return;
                }

                $assetTypes[$block['media_id']] = 'video';

                if (array_key_exists('poster_media_id', $block) && $block['poster_media_id'] !== null) {
                    if (! $this->positiveId($block['poster_media_id'])) {
                        $fail("The :attribute video block at position {$index} has an invalid poster asset.");

                        return;
                    }

                    $assetTypes[$block['poster_media_id']] = 'image';
                }
            }

            if ($type === 'before_after') {
                foreach (['before_alt', 'after_alt'] as $field) {
                    if (! $this->requiredText($block, $field, 300)) {
                        $fail("The :attribute before/after block at position {$index} needs alternative text for both images.");

                        return;
                    }
                }

                foreach (['before_label', 'after_label'] as $field) {
                    if (! $this->requiredText($block, $field, 80)) {
                        $fail("The :attribute before/after block at position {$index} needs labels for both images.");

                        return;
                    }
                }

                if (! $this->optionalText($block, 'caption', 1000)
                    || ! $this->positiveId($block['before_media_id'] ?? null)
                    || ! $this->positiveId($block['after_media_id'] ?? null)) {
                    $fail("The :attribute before/after block at position {$index} needs two valid image assets.");

                    return;
                }

                $assetTypes[$block['before_media_id']] = 'image';
                $assetTypes[$block['after_media_id']] = 'image';
            }

            if ($type === 'quote' && ! $this->optionalText($block, 'attribution', 240)) {
                $fail("The :attribute quote block at position {$index} has an invalid attribution.");

                return;
            }

            if ($type === 'list') {
                $items = $block['items'] ?? null;
                if (! in_array($block['style'] ?? null, ['ordered', 'unordered'], true)
                    || ! is_array($items)
                    || ! array_is_list($items)
                    || count($items) < 1
                    || count($items) > 30) {
                    $fail("The :attribute list block at position {$index} must be an ordered or unordered list of 1 to 30 items.");

                    return;
                }

                foreach ($items as $item) {
                    if (! is_string($item) || trim($item) === '' || mb_strlen($item) > 1000) {
                        $fail("The :attribute list block at position {$index} contains an invalid item.");

                        return;
                    }
                }
            }
        }

        $this->validateAssets($assetTypes, $fail);
    }

    private function validateAssets(array $assetTypes, Closure $fail): void
    {
        if ($assetTypes === []) {
            return;
        }

        $ids = array_map('intval', array_keys($assetTypes));
        $assets = MediaAsset::query()
            ->where('context', 'news')
            ->where('visibility', 'private')
            ->where('status', 'ready')
            ->whereIn('id', $ids)
            ->get(['id', 'type', 'mime_type'])
            ->keyBy('id');

        foreach ($assetTypes as $id => $expectedType) {
            $asset = $assets->get((int) $id);
            $isExpectedType = $asset && ($asset->type === $expectedType || str_starts_with((string) $asset->mime_type, $expectedType.'/'));

            if (! $isExpectedType) {
                $fail('The :attribute field references a missing or incompatible news media asset.');

                return;
            }
        }
    }

    private function requiredText(array $block, string $field, int $limit): bool
    {
        return is_string($block[$field] ?? null)
            && trim($block[$field]) !== ''
            && mb_strlen($block[$field]) <= $limit;
    }

    private function optionalText(array $block, string $field, int $limit): bool
    {
        return ! array_key_exists($field, $block)
            || $block[$field] === null
            || (is_string($block[$field]) && mb_strlen($block[$field]) <= $limit);
    }

    private function positiveId(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false && (int) $value > 0;
    }
}
