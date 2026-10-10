<?php

namespace App\Services\Maps;

use App\Models\FeedPost;
use App\Models\HntMap;
use App\Models\HntMapCashSpotSubmission;
use App\Models\HntMapMarker;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CashSpotFeedAnnouncement
{
    public function publish(HntMapCashSpotSubmission $submission, HntMap $map, HntMapMarker $marker): void
    {
        if ($submission->status !== HntMapCashSpotSubmission::STATUS_APPROVED ||
            $submission->announcement_feed_post_id !== null) {
            return;
        }

        // Never invent a user. Approval and publication run inside the same transaction.
        $author = User::query()
            ->whereKey(2)
            ->where('username', 'hntrocks')
            ->where('status', 'active')
            ->first();

        if (! $author) {
            throw new RuntimeException('Official HNT.rocks account (user #2) is unavailable.');
        }

        $post = FeedPost::query()->create([
            'user_id' => $author->id,
            'body' => "Neuer Kassenspot gefunden!\n".$this->mapUrl($map, $submission, $marker),
            'source_language' => 'de',
            'visibility' => 'public',
            'status' => 'published',
        ]);

        $submission->forceFill(['announcement_feed_post_id' => $post->id])->save();
    }

    public function previewForPost(FeedPost $post, ?string $locale = null): ?array
    {
        // Ordinary posts never trigger cash-spot queries or special rendering.
        if ((int) $post->user_id !== 2 ||
            ! str_starts_with((string) $post->body, 'Neuer Kassenspot gefunden!')) {
            return null;
        }

        $submission = HntMapCashSpotSubmission::query()
            ->with(['map', 'marker'])
            ->where('announcement_feed_post_id', $post->id)
            ->where('status', HntMapCashSpotSubmission::STATUS_APPROVED)
            ->first();

        if (! $submission?->map || ! $submission->marker ||
            $submission->marker->status !== 'approved' ||
            $submission->marker->type !== 'cash') {
            return null;
        }

        $map = $submission->map;
        $marker = $submission->marker;
        $compound = $map->markers()
            ->where('type', 'compound')
            ->where('status', 'approved')
            ->orderByRaw('POW(x - ?, 2) + POW(y - ?, 2)', [(float) $marker->x, (float) $marker->y])
            ->first(['label_de', 'label_en']);

        $isGerman = strtolower(substr((string) ($locale ?: app()->getLocale()), 0, 2)) === 'de';
        $location = trim((string) ($isGerman
            ? ($compound?->label_de ?: $compound?->label_en)
            : ($compound?->label_en ?: $compound?->label_de)));
        $location = $location !== '' ? $location : (string) $map->name;

        $image = null;
        $publicPath = (string) $submission->public_path;
        if (str_starts_with($publicPath, 'maps/cash-spots/') &&
            ! str_contains($publicPath, '..') &&
            Storage::disk('public')->exists($publicPath)) {
            $image = Storage::disk('public')->url($publicPath);
        } elseif ($map->image_path && is_file(public_path(ltrim((string) $map->image_path, '/')))) {
            $image = '/'.ltrim((string) $map->image_path, '/');
        }

        return [
            'url' => $this->mapUrl($map, $submission, $marker),
            'image_url' => $image,
            'title' => $isGerman ? 'Neuer Kassenspot gefunden!' : 'New cash spot discovered!',
            'location' => $location,
            'map_name' => (string) $map->name,
            'coordinates' => 'X: '.$this->format((float) $marker->x).' · Y: '.$this->format((float) $marker->y),
            'marker_id' => (string) $marker->id,
        ];
    }

    private function mapUrl(HntMap $map, HntMapCashSpotSubmission $submission, HntMapMarker $marker): string
    {
        return '/maps/'.rawurlencode($map->slug).'?'.http_build_query([
            'cash_marker' => $marker->id,
            'x' => $this->format((float) $marker->x),
            'y' => $this->format((float) $marker->y),
            'cash_spot' => $submission->id,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    private function format(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
