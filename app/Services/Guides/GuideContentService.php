<?php

namespace App\Services\Guides;

use App\Models\GuideMedia;
use App\Models\GuideRevision;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GuideContentService
{
    public const BLOCK_TYPES = ['heading', 'paragraph', 'steps', 'list', 'image', 'notice', 'warning'];

    /**
     * @param  array<int, mixed>  $blocks
     * @return array<int, array<string, mixed>>
     */
    public function sanitizeBlocks(array $blocks): array
    {
        return collect($blocks)
            ->take(60)
            ->map(fn (mixed $block, int $index): ?array => $this->sanitizeBlock($block, $index))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $tags
     * @return array<int, string>
     */
    public function sanitizeTags(array $tags): array
    {
        return collect($tags)
            ->map(fn (mixed $tag): string => Str::of((string) $tag)->squish()->limit(30, '')->toString())
            ->filter()
            ->unique(fn (string $tag): string => mb_strtolower($tag))
            ->take(8)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     */
    public function readingTime(array $blocks): int
    {
        $words = collect($blocks)
            ->flatMap(function (array $block): array {
                return match ($block['type'] ?? null) {
                    'heading', 'paragraph' => [(string) ($block['text'] ?? '')],
                    'steps', 'list' => (array) ($block['items'] ?? []),
                    'notice', 'warning' => [(string) ($block['title'] ?? ''), (string) ($block['text'] ?? '')],
                    'image' => [(string) ($block['caption'] ?? '')],
                    default => [],
                };
            })
            ->sum(fn (string $text): int => str_word_count(strip_tags($text)));

        return max(1, (int) ceil($words / 200));
    }

    public function assertReadyForSubmission(GuideRevision $revision): void
    {
        $errors = [];

        if (mb_strlen(trim($revision->title)) < 8) {
            $errors['title'][] = __('guides.validation.title_required');
        }
        if (mb_strlen(trim($revision->summary)) < 20) {
            $errors['summary'][] = __('guides.validation.summary_required');
        }
        if (! $revision->category_id) {
            $errors['category_id'][] = __('guides.validation.category_required');
        }
        if (! $revision->cover_media_id) {
            $errors['cover_media_id'][] = __('guides.validation.cover_required');
        }
        if (! in_array($revision->language, ['de', 'en'], true)) {
            $errors['language'][] = __('guides.validation.language_required');
        }
        if (! in_array($revision->difficulty, ['beginner', 'advanced', 'expert'], true)) {
            $errors['difficulty'][] = __('guides.validation.difficulty_required');
        }
        if (! in_array($revision->platform, ['all', 'pc', 'playstation', 'xbox'], true)) {
            $errors['platform'][] = __('guides.validation.platform_required');
        }

        $blocks = $this->sanitizeBlocks((array) $revision->content_blocks);
        if (count($blocks) < 3) {
            $errors['content_blocks'][] = __('guides.validation.blocks_required');
        } elseif (collect($blocks)->contains(fn (array $block): bool => ! $this->blockIsComplete($block))) {
            $errors['content_blocks'][] = __('guides.validation.blocks_incomplete');
        }

        $mediaIds = collect($blocks)
            ->where('type', 'image')
            ->pluck('media_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->push((int) $revision->cover_media_id)
            ->filter()
            ->unique()
            ->values();

        if ($mediaIds->isNotEmpty()) {
            $ownedMediaCount = GuideMedia::query()
                ->where('guide_id', $revision->guide_id)
                ->whereIn('id', $mediaIds)
                ->count();

            if ($ownedMediaCount !== $mediaIds->count()) {
                $errors['content_blocks'][] = __('guides.validation.media_invalid');
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function sanitizeBlock(mixed $block, int $index): ?array
    {
        if (! is_array($block)) {
            return null;
        }

        $type = (string) ($block['type'] ?? '');
        if (! in_array($type, self::BLOCK_TYPES, true)) {
            throw ValidationException::withMessages([
                "content_blocks.{$index}.type" => __('guides.validation.block_type_invalid'),
            ]);
        }

        $base = [
            'id' => Str::of((string) ($block['id'] ?? Str::uuid()))->replaceMatches('/[^a-zA-Z0-9_-]/', '')->limit(80, '')->toString(),
            'type' => $type,
        ];

        return match ($type) {
            'heading' => $base + [
                'level' => max(2, min(4, (int) ($block['level'] ?? 2))),
                'text' => $this->text($block['text'] ?? '', 180),
            ],
            'paragraph' => $base + [
                'text' => $this->text($block['text'] ?? '', 5000),
            ],
            'steps', 'list' => $base + [
                'items' => collect(Arr::wrap($block['items'] ?? []))
                    ->map(fn (mixed $item): string => $this->text($item, 800))
                    ->filter()
                    ->take(30)
                    ->values()
                    ->all(),
            ],
            'image' => $base + [
                'media_id' => max(0, (int) ($block['media_id'] ?? 0)),
                'caption' => $this->text($block['caption'] ?? '', 240),
            ],
            'notice', 'warning' => $base + [
                'title' => $this->text($block['title'] ?? '', 120),
                'text' => $this->text($block['text'] ?? '', 1500),
            ],
        };
    }

    private function text(mixed $value, int $limit): string
    {
        return Str::of((string) $value)
            ->replace(["\r\n", "\r"], "\n")
            ->trim()
            ->limit($limit, '')
            ->toString();
    }

    /** @param array<string, mixed> $block */
    private function blockIsComplete(array $block): bool
    {
        return match ($block['type'] ?? null) {
            'heading', 'paragraph' => trim((string) ($block['text'] ?? '')) !== '',
            'steps', 'list' => collect((array) ($block['items'] ?? []))
                ->contains(fn ($item): bool => trim((string) $item) !== ''),
            'image' => (int) ($block['media_id'] ?? 0) > 0,
            'notice', 'warning' => trim((string) ($block['text'] ?? '')) !== '',
            default => false,
        };
    }
}
