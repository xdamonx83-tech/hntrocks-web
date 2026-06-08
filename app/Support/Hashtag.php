<?php

namespace App\Support;

use Illuminate\Support\Str;

class Hashtag
{
    private const HASHTAG_PATTERN = '/(?<![\pL\pN_])#([\pL\pN_][\pL\pN_\-]{0,49})/u';

    public static function normalize(?string $tag): ?string
    {
        $tag = trim((string) $tag);
        $tag = ltrim($tag, '#');
        $tag = trim($tag);

        if ($tag === '') {
            return null;
        }

        $tag = Str::of($tag)->ascii()->lower()->toString();
        $tag = preg_replace('/[^a-z0-9_\-]/', '', $tag) ?: '';
        $tag = trim($tag, '_-');

        return $tag !== '' ? Str::limit($tag, 50, '') : null;
    }

    public static function extract(?string $text): array
    {
        $text = (string) $text;

        if ($text === '') {
            return [];
        }

        preg_match_all(self::HASHTAG_PATTERN, $text, $matches);

        return collect($matches[1] ?? [])
            ->map(fn (string $tag): ?string => self::normalize($tag))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function contains(?string $text, string $normalizedTag): bool
    {
        $normalizedTag = self::normalize($normalizedTag) ?: '';

        if ($normalizedTag === '') {
            return false;
        }

        return in_array($normalizedTag, self::extract($text), true);
    }

    public static function renderText(?string $text): string
    {
        $text = (string) $text;

        if ($text === '') {
            return '';
        }

        $escaped = e($text);
        $linked = preg_replace_callback(self::HASHTAG_PATTERN, function (array $match): string {
            $label = $match[0];
            $tag = self::normalize($match[1]);

            if (! $tag) {
                return $label;
            }

            return '<a class="hnt-hashtag-link" href="'.e(route('hashtags.show', $tag)).'">'.e($label).'</a>';
        }, $escaped);

        return nl2br($linked ?? $escaped);
    }

    public static function labelsForText(?string $text, int $limit = 8): array
    {
        $limit = max(1, min(20, $limit));

        return collect(self::extract($text))
            ->take($limit)
            ->map(fn (string $tag): array => [
                'tag' => $tag,
                'label' => '#'.$tag,
                'url' => route('hashtags.show', $tag),
            ])
            ->values()
            ->all();
    }
}
